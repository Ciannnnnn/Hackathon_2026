<?php

namespace Tests\Feature;

use App\Contracts\GenerativeAiProvider;
use App\Data\AiResult;
use App\Exceptions\AiProviderException;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Enrollment;
use App\Models\LearningModule;
use App\Models\ModuleChunk;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTutorTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_receives_a_grounded_answer_from_their_subject_modules(): void
    {
        [$studentUser, $student, $subject] = $this->studentSubject('A');
        $source = $this->moduleWithChunk($subject, 'Normalization Module', 'Normalization reduces repeated data and update anomalies.', 4);
        [, , $otherSubject] = $this->studentSubject('B');
        $this->moduleWithChunk($otherSubject, 'Private Other Subject', 'SECRET unrelated normalization content.', 9);
        $provider = $this->fakeProvider('Normalization organizes tables to reduce repeated data.');

        $response = $this->actingAs($studentUser)->post(route('student.tutor.store'), [
            'subject_id' => $subject->id,
            'question' => 'Why is normalization useful?',
        ]);

        $conversation = ChatConversation::query()->sole();
        $assistant = ChatMessage::query()->where('role', 'assistant')->sole();

        $response->assertRedirect(route('student.tutor.index', [
            'subject' => $subject->id,
            'conversation' => $conversation->id,
        ]))->assertSessionHas('status');
        $this->assertSame($student->id, $conversation->student_id);
        $this->assertDatabaseCount('chat_messages', 2);
        $this->assertSame($source->id, $assistant->retrieved_context[0]['chunk_id']);
        $this->assertSame(4, $assistant->retrieved_context[0]['page']);
        $this->assertStringContainsString('Normalization reduces repeated data', $provider->lastPrompt);
        $this->assertStringNotContainsString('SECRET unrelated', $provider->lastPrompt);
        $this->assertStringContainsString('untrusted reference text', $provider->lastSystem);

        $this->actingAs($studentUser)
            ->get(route('student.tutor.index', ['subject' => $subject->id, 'conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Normalization organizes tables')
            ->assertSee('Normalization Module')
            ->assertSee('page 4');
    }

    public function test_unmatched_question_is_saved_as_general_guidance_without_fake_sources(): void
    {
        [$studentUser, , $subject] = $this->studentSubject('C');
        $this->moduleWithChunk($subject, 'Database Module', 'Relational keys connect database tables.', 2);
        $provider = $this->fakeProvider('Photosynthesis converts light energy into chemical energy.');

        $this->actingAs($studentUser)->post(route('student.tutor.store'), [
            'subject_id' => $subject->id,
            'question' => 'How does photosynthesis work?',
        ])->assertSessionHas('status', 'No matching module passage was found; the answer is labeled as general guidance.');

        $assistant = ChatMessage::query()->where('role', 'assistant')->sole();
        $this->assertNull($assistant->retrieved_context);
        $this->assertStringContainsString('Teacher-material context: None', $provider->lastPrompt);
    }

    public function test_follow_up_question_includes_only_the_students_recent_conversation(): void
    {
        [$studentUser, $student, $subject] = $this->studentSubject('D');
        $provider = $this->fakeProvider('A focused follow-up answer.');
        $conversation = ChatConversation::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'title' => 'Keys',
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'What is a primary key?']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'It uniquely identifies a row.']);

        $this->actingAs($studentUser)->post(route('student.tutor.store'), [
            'subject_id' => $subject->id,
            'conversation_id' => $conversation->id,
            'question' => 'Can you give an example?',
        ])->assertRedirect();

        $this->assertStringContainsString('It uniquely identifies a row.', $provider->lastPrompt);
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseCount('chat_messages', 4);
    }

    public function test_student_cannot_use_an_unenrolled_subject_or_another_students_conversation(): void
    {
        [$studentUser] = $this->studentSubject('E');
        [, $otherStudent, $otherSubject] = $this->studentSubject('F');
        $conversation = ChatConversation::create([
            'student_id' => $otherStudent->id,
            'subject_id' => $otherSubject->id,
            'title' => 'Private chat',
        ]);

        $this->actingAs($studentUser)->post(route('student.tutor.store'), [
            'subject_id' => $otherSubject->id,
            'question' => 'Show private content',
        ])->assertForbidden();

        $this->actingAs($studentUser)
            ->get(route('student.tutor.index', ['subject' => $otherSubject->id, 'conversation' => $conversation->id]))
            ->assertNotFound();

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_ai_outage_uses_transparent_fallback_and_preserves_retrieved_sources(): void
    {
        [$studentUser, , $subject] = $this->studentSubject('G');
        $this->moduleWithChunk($subject, 'Transactions Module', 'A transaction groups database operations into one unit.', 6);
        config()->set('services.gemini.demo_fallback', true);
        $this->app->instance(GenerativeAiProvider::class, new class implements GenerativeAiProvider
        {
            public function generateText(string $systemInstruction, string $prompt): AiResult
            {
                throw new AiProviderException('Provider unavailable.');
            }

            public function generateJson(string $systemInstruction, string $prompt, array $schema): AiResult
            {
                throw new AiProviderException('Provider unavailable.');
            }
        });

        $this->actingAs($studentUser)->post(route('student.tutor.store'), [
            'subject_id' => $subject->id,
            'question' => 'What is a transaction?',
        ])->assertSessionHas('status', 'The AI provider was unavailable; relevant teacher sources were saved for review.');

        $assistant = ChatMessage::query()->where('role', 'assistant')->sole();
        $this->assertStringContainsString('temporarily unavailable', $assistant->content);
        $this->assertSame('Transactions Module', $assistant->retrieved_context[0]['module_title']);
    }

    /** @return array{User, Student, Subject} */
    private function studentSubject(string $suffix): array
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_number' => "T-TUTOR-{$suffix}"]);
        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => "IT-TUTOR-{$suffix}",
            'title' => 'Database Systems',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
            'is_active' => true,
        ]);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'student_number' => "S-TUTOR-{$suffix}",
            'grade_level' => '2nd Year',
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        return [$studentUser, $student, $subject];
    }

    private function moduleWithChunk(Subject $subject, string $title, string $content, int $page): ModuleChunk
    {
        $module = LearningModule::create([
            'subject_id' => $subject->id,
            'teacher_id' => $subject->teacher_id,
            'title' => $title,
            'original_filename' => 'lesson.pdf',
            'stored_filename' => "lesson-{$subject->id}.pdf",
            'file_path' => "modules/lesson-{$subject->id}.pdf",
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 100,
            'processing_status' => 'ready',
            'uploaded_at' => now(),
        ]);

        return ModuleChunk::create([
            'module_id' => $module->id,
            'chunk_index' => 0,
            'page_number' => $page,
            'content' => $content,
            'token_count' => str_word_count($content),
        ]);
    }

    private function fakeProvider(string $answer): object
    {
        $provider = new class($answer) implements GenerativeAiProvider
        {
            public string $lastSystem = '';

            public string $lastPrompt = '';

            public function __construct(private readonly string $answer) {}

            public function generateText(string $systemInstruction, string $prompt): AiResult
            {
                $this->lastSystem = $systemInstruction;
                $this->lastPrompt = $prompt;

                return new AiResult($this->answer, 'test', 'test-v1');
            }

            public function generateJson(string $systemInstruction, string $prompt, array $schema): AiResult
            {
                return new AiResult([], 'test', 'test-v1');
            }
        };

        $this->app->instance(GenerativeAiProvider::class, $provider);

        return $provider;
    }
}
