<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('services.rag.enabled', true);
        config()->set('services.rag.url', 'http://rag.test');
    }

    public function test_teacher_can_upload_and_extract_a_pdf_for_their_subject(): void
    {
        [$teacherUser, $subject] = $this->teacherSubject('A');
        Http::fake(['http://rag.test/extract' => Http::response($this->extractedPayload(), 200)]);

        $response = $this->actingAs($teacherUser)->post(route('teacher.modules.store'), [
            'subject_id' => $subject->id,
            'title' => 'Database Normalization',
            'pdf' => UploadedFile::fake()->create('normalization.pdf', 120, 'application/pdf'),
        ]);

        $module = LearningModule::query()->sole();

        $response->assertRedirect(route('teacher.modules.index', ['subject' => $subject->id]))->assertSessionHas('status');
        $this->assertSame('ready', $module->processing_status);
        $this->assertSame(2, $module->chunks()->count());
        $this->assertSame(1, $module->chunks()->first()->page_number);
        Storage::disk('local')->assertExists($module->file_path);
        Http::assertSentCount(1);

        $this->actingAs($teacherUser)
            ->get(route('teacher.modules.index', ['subject' => $subject->id]))
            ->assertOk()
            ->assertSee('Database Normalization')
            ->assertSee('First page normalization content.');
    }

    public function test_failed_extraction_is_visible_and_can_be_retried(): void
    {
        [$teacherUser, $subject] = $this->teacherSubject('B');
        Http::fake([
            'http://rag.test/extract' => Http::sequence()
                ->push(['message' => 'No selectable text was found.'], 422)
                ->push($this->extractedPayload(), 200),
        ]);

        $this->actingAs($teacherUser)->post(route('teacher.modules.store'), [
            'subject_id' => $subject->id,
            'title' => 'Scanned Lesson',
            'pdf' => UploadedFile::fake()->create('scan.pdf', 120, 'application/pdf'),
        ])->assertSessionHasErrors('module');

        $module = LearningModule::query()->sole();
        $this->assertSame('failed', $module->processing_status);
        $this->assertSame('No selectable text was found.', $module->processing_error);

        $this->actingAs($teacherUser)
            ->post(route('teacher.modules.retry', $module))
            ->assertSessionHas('status');

        $this->assertSame('ready', $module->fresh()->processing_status);
        $this->assertDatabaseCount('module_chunks', 2);
    }

    public function test_teacher_cannot_manage_another_teachers_module(): void
    {
        [$owner, $subject] = $this->teacherSubject('C');
        [$outsider] = $this->teacherSubject('D');
        $module = $this->storedModule($owner, $subject);

        $this->actingAs($outsider)->post(route('teacher.modules.retry', $module))->assertNotFound();
        $this->actingAs($outsider)->get(route('teacher.modules.download', $module))->assertNotFound();
        $this->actingAs($outsider)->delete(route('teacher.modules.destroy', $module))->assertNotFound();
        $this->assertDatabaseHas('modules', ['id' => $module->id]);
    }

    public function test_pdf_upload_is_validated_and_must_target_an_owned_subject(): void
    {
        [$teacherUser, $subject] = $this->teacherSubject('E');
        [, $outsideSubject] = $this->teacherSubject('F');

        $this->actingAs($teacherUser)->post(route('teacher.modules.store'), [
            'subject_id' => $outsideSubject->id,
            'title' => 'Unauthorized module',
            'pdf' => UploadedFile::fake()->create('lesson.pdf', 50, 'application/pdf'),
        ])->assertForbidden();

        $this->actingAs($teacherUser)->post(route('teacher.modules.store'), [
            'subject_id' => $subject->id,
            'title' => '',
            'pdf' => UploadedFile::fake()->create('lesson.txt', 50, 'text/plain'),
        ])->assertSessionHasErrors(['title', 'pdf']);

        $this->assertDatabaseCount('modules', 0);
    }

    public function test_owner_can_download_and_delete_private_module(): void
    {
        [$teacherUser, $subject] = $this->teacherSubject('G');
        $module = $this->storedModule($teacherUser, $subject);

        $this->actingAs($teacherUser)
            ->get(route('teacher.modules.download', $module))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($teacherUser)
            ->delete(route('teacher.modules.destroy', $module))
            ->assertRedirect(route('teacher.modules.index', ['subject' => $subject->id]));

        Storage::disk('local')->assertMissing($module->file_path);
        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }

    /** @return array{User, Subject} */
    private function teacherSubject(string $suffix): array
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $user->id, 'employee_number' => "T-MODULE-{$suffix}"]);
        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => "IT-MODULE-{$suffix}",
            'title' => 'Information Technology',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
            'is_active' => true,
        ]);

        return [$user, $subject];
    }

    private function storedModule(User $teacherUser, Subject $subject): LearningModule
    {
        $path = "modules/{$teacherUser->teacher->id}/{$subject->id}/stored.pdf";
        Storage::disk('local')->put($path, '%PDF-private');

        return LearningModule::create([
            'subject_id' => $subject->id,
            'teacher_id' => $teacherUser->teacher->id,
            'title' => 'Private module',
            'original_filename' => 'private.pdf',
            'stored_filename' => 'stored.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 12,
            'processing_status' => 'ready',
            'uploaded_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function extractedPayload(): array
    {
        return [
            'page_count' => 2,
            'character_count' => 48,
            'chunks' => [
                ['chunk_index' => 0, 'page_number' => 1, 'content' => 'First page normalization content.', 'token_count' => 4],
                ['chunk_index' => 1, 'page_number' => 2, 'content' => 'Second page dependency content.', 'token_count' => 4],
            ],
        ];
    }
}
