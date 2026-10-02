<?php

namespace Tests\Feature;

use App\Contracts\GenerativeAiProvider;
use App\Data\AiResult;
use App\Jobs\GenerateGroundedQuiz;
use App\Models\Enrollment;
use App\Models\GeneratedQuiz;
use App\Models\LearningModule;
use App\Models\ModuleChunk;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GeneratedQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_generates_a_grounded_draft_and_can_publish_it(): void
    {
        [$teacherUser, , $subject] = $this->teacherSubject('A');
        [$module, $chunk] = $this->moduleWithChunk($subject, 'Normalization turns repeated data into related tables.');
        $provider = $this->quizProvider($chunk->id);
        $this->app->instance(GenerativeAiProvider::class, $provider);

        $response = $this->actingAs($teacherUser)->post(route('teacher.quizzes.store'), [
            'subject_id' => $subject->id,
            'module_id' => $module->id,
            'title' => 'Normalization Review',
            'topic' => 'Normalization',
            'difficulty' => 'medium',
            'question_count' => 3,
        ]);

        $quiz = GeneratedQuiz::query()->with('questions')->sole();
        $response->assertRedirect(route('teacher.quizzes.index', ['subject' => $subject->id]));
        $this->assertFalse($quiz->is_published);
        $this->assertCount(3, $quiz->questions);
        $this->assertTrue($quiz->questions->every(fn ($question): bool => $question->source_chunk_id === $chunk->id));
        $this->assertStringContainsString("[source_chunk_id={$chunk->id}; page=4]", $provider->prompt);

        $this->actingAs($teacherUser)->patch(route('teacher.quizzes.publish', $quiz))->assertRedirect();
        $this->assertTrue($quiz->fresh()->is_published);
    }

    public function test_quiz_is_not_saved_when_ai_cites_an_unavailable_source(): void
    {
        [$teacherUser, , $subject] = $this->teacherSubject('B');
        [$module] = $this->moduleWithChunk($subject, 'A primary key uniquely identifies a row.');
        $this->app->instance(GenerativeAiProvider::class, $this->quizProvider(999999));

        $response = $this->actingAs($teacherUser)->post(route('teacher.quizzes.store'), [
            'subject_id' => $subject->id,
            'module_id' => $module->id,
            'title' => 'Keys Review',
            'topic' => 'Keys',
            'difficulty' => 'easy',
            'question_count' => 3,
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('teacher.quizzes.index', ['subject' => $subject->id]));
        $this->assertDatabaseCount('generated_quizzes', 0);
        $this->assertDatabaseCount('generated_quiz_questions', 0);
    }

    public function test_quiz_generation_uses_the_deferred_connection_without_a_worker(): void
    {
        Queue::fake();
        [$teacherUser, , $subject] = $this->teacherSubject('QUEUE');
        [$module] = $this->moduleWithChunk($subject, 'Queued generation should not block the web request.');

        $response = $this->actingAs($teacherUser)->post(route('teacher.quizzes.store'), [
            'subject_id' => $subject->id,
            'module_id' => $module->id,
            'title' => 'Queued Review',
            'topic' => 'Queues',
            'difficulty' => 'medium',
            'question_count' => 3,
        ]);

        $response->assertRedirect(route('teacher.quizzes.index', ['subject' => $subject->id]));
        $response->assertSessionHas('status', fn (string $message): bool => str_contains($message, 'Creating Queued Review'));
        $response->assertSessionHas('pending_quiz_title', 'Queued Review');
        $this->assertDatabaseCount('generated_quizzes', 0);
        Queue::assertPushed(GenerateGroundedQuiz::class, fn (GenerateGroundedQuiz $job): bool => $job->module->is($module)
            && $job->creator->is($teacherUser)
            && $job->data['is_published'] === false
            && $job->connection === 'deferred');
    }

    public function test_enrolled_student_can_take_a_published_quiz_and_review_results(): void
    {
        [$teacherUser, , $subject] = $this->teacherSubject('C');
        [$module, $chunk] = $this->moduleWithChunk($subject, 'A transaction is an atomic unit of work.');
        [$studentUser, $student] = $this->enrollStudent($subject, 'C');
        $quiz = GeneratedQuiz::create([
            'subject_id' => $subject->id,
            'module_id' => $module->id,
            'created_by_user_id' => $teacherUser->id,
            'title' => 'Transactions Check',
            'topic' => 'Transactions',
            'difficulty' => 'medium',
            'question_count' => 2,
            'is_published' => true,
        ]);
        $questions = $quiz->questions()->createMany([
            ['position' => 1, 'question_type' => 'multiple_choice', 'question_text' => 'What does atomic mean?', 'choices' => ['All or nothing', 'Optional'], 'correct_answer' => 'All or nothing', 'explanation' => 'Atomic work fully succeeds or rolls back.', 'source_chunk_id' => $chunk->id],
            ['position' => 2, 'question_type' => 'true_false', 'question_text' => 'Transactions are units of work.', 'choices' => ['True', 'False'], 'correct_answer' => 'True', 'explanation' => 'That is the module definition.', 'source_chunk_id' => $chunk->id],
        ]);

        $this->actingAs($studentUser)->get(route('student.quizzes.index'))->assertOk()->assertSee('Transactions Check');
        $response = $this->actingAs($studentUser)->post(route('student.quizzes.submit', $quiz), [
            'answers' => [$questions[0]->id => 'All or nothing', $questions[1]->id => 'False'],
        ]);

        $attempt = $student->quizAttempts()->with('answers')->sole();
        $response->assertRedirect(route('student.quizzes.results', $attempt));
        $this->assertSame('1.00', $attempt->score);
        $this->assertSame('2.00', $attempt->max_score);
        $this->assertSame(['Transactions'], $attempt->weak_topics);
        $this->assertTrue($attempt->answers[0]->is_correct);
        $this->assertFalse($attempt->answers[1]->is_correct);
        $this->actingAs($studentUser)->get(route('student.quizzes.results', $attempt))->assertOk()->assertSee('50%')->assertSee('page 4');
    }

    public function test_drafts_and_other_students_results_are_private(): void
    {
        [$teacherUser, , $subject] = $this->teacherSubject('D');
        [$module] = $this->moduleWithChunk($subject, 'Private lesson content.');
        [$studentUser] = $this->enrollStudent($subject, 'D1');
        [, $otherStudent] = $this->enrollStudent($subject, 'D2');
        $quiz = GeneratedQuiz::create([
            'subject_id' => $subject->id,
            'module_id' => $module->id,
            'created_by_user_id' => $teacherUser->id,
            'title' => 'Draft Quiz',
            'topic' => 'Privacy',
            'difficulty' => 'easy',
            'question_count' => 1,
            'is_published' => false,
        ]);

        $this->actingAs($studentUser)->get(route('student.quizzes.show', $quiz))->assertNotFound();

        $attempt = $quiz->attempts()->create([
            'student_id' => $otherStudent->id,
            'score' => 0,
            'max_score' => 1,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $this->actingAs($studentUser)->get(route('student.quizzes.results', $attempt))->assertNotFound();
    }

    /** @return array{User, Teacher, Subject} */
    private function teacherSubject(string $suffix): array
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $user->id, 'employee_number' => "T-QUIZ-{$suffix}"]);
        $subject = Subject::create(['teacher_id' => $teacher->id, 'code' => "IT-QUIZ-{$suffix}", 'title' => 'Database Systems', 'school_year' => '2026-2027', 'term' => '1st Semester', 'is_active' => true]);

        return [$user, $teacher, $subject];
    }

    /** @return array{LearningModule, ModuleChunk} */
    private function moduleWithChunk(Subject $subject, string $content): array
    {
        $module = LearningModule::create(['subject_id' => $subject->id, 'teacher_id' => $subject->teacher_id, 'title' => 'Database Module', 'original_filename' => 'module.pdf', 'stored_filename' => "quiz-module-{$subject->id}.pdf", 'file_path' => "modules/quiz-module-{$subject->id}.pdf", 'mime_type' => 'application/pdf', 'file_size_bytes' => 1024, 'processing_status' => 'ready', 'uploaded_at' => now()]);
        $chunk = $module->chunks()->create(['chunk_index' => 0, 'page_number' => 4, 'content' => $content, 'token_count' => 12]);

        return [$module, $chunk];
    }

    /** @return array{User, Student} */
    private function enrollStudent(Subject $subject, string $suffix): array
    {
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $user->id, 'student_number' => "S-QUIZ-{$suffix}", 'grade_level' => '2nd Year']);
        Enrollment::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'status' => 'active', 'enrolled_at' => now()]);

        return [$user, $student];
    }

    private function quizProvider(int $sourceChunkId): GenerativeAiProvider
    {
        return new class($sourceChunkId) implements GenerativeAiProvider
        {
            public string $prompt = '';

            public function __construct(private readonly int $sourceChunkId) {}

            public function generateText(string $systemInstruction, string $prompt): AiResult
            {
                return new AiResult('Unused', 'fake', 'fake-v1');
            }

            public function generateJson(string $systemInstruction, string $prompt, array $schema): AiResult
            {
                $this->prompt = $prompt;
                $questions = collect(range(1, 3))->map(fn (int $position): array => [
                    'question' => "Grounded question {$position}?",
                    'type' => 'multiple_choice',
                    'choices' => ['Correct answer', 'Incorrect answer'],
                    'correct_answer' => 'Correct answer',
                    'explanation' => 'The module supports this answer.',
                    'source_chunk_id' => $this->sourceChunkId,
                ])->all();

                return new AiResult(['questions' => $questions], 'fake', 'fake-v1');
            }
        };
    }
}
