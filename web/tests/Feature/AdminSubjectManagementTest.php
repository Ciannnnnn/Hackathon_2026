<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_subject_and_enroll_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = $this->createTeacher('T-SUB-001');
        $student = $this->createStudent('S-SUB-001');

        $this->actingAs($admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertSee('Subjects and enrollments')
            ->assertSee($teacher->user->full_name)
            ->assertSee($student->user->full_name);

        $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
            'teacher_id' => $teacher->id,
            'code' => 'it 401',
            'title' => 'Systems Integration',
            'description' => 'Integration concepts and practice.',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
            'is_active' => '1',
            'student_ids' => [$student->id],
        ]);

        $subject = Subject::query()->sole();

        $response->assertRedirect(route('admin.subjects.index'))->assertSessionHas('status');
        $this->assertSame('IT 401', $subject->code);
        $this->assertSame($teacher->id, $subject->teacher_id);
        $this->assertDatabaseHas('enrollments', [
            'subject_id' => $subject->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);
    }

    public function test_administrator_can_manage_enrollment_and_subject_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = $this->createTeacher('T-SUB-002');
        $first = $this->createStudent('S-SUB-002');
        $second = $this->createStudent('S-SUB-003');
        $subject = $this->createSubject($teacher);
        Enrollment::create(['student_id' => $first->id, 'subject_id' => $subject->id, 'status' => 'active', 'enrolled_at' => now()]);

        $this->actingAs($admin)
            ->put(route('admin.subjects.enrollments.update', $subject), ['student_ids' => [$second->id]])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('enrollments', ['student_id' => $first->id, 'subject_id' => $subject->id, 'status' => 'dropped']);
        $this->assertDatabaseHas('enrollments', ['student_id' => $second->id, 'subject_id' => $subject->id, 'status' => 'active']);

        $this->actingAs($admin)->patch(route('admin.subjects.status', $subject))->assertSessionHas('status');
        $this->assertFalse($subject->fresh()->is_active);
    }

    public function test_subject_offering_must_be_unique_and_non_admins_are_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = $this->createTeacher('T-SUB-003');
        $this->createSubject($teacher);

        $this->actingAs($admin)->post(route('admin.subjects.store'), [
            'teacher_id' => $teacher->id,
            'code' => 'IT 401',
            'title' => 'Duplicate',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
            'is_active' => '1',
        ])->assertSessionHasErrors('code');

        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacherUser)->get(route('admin.subjects.index'))->assertForbidden();
        $this->assertDatabaseCount('subjects', 1);
    }

    private function createTeacher(string $number): Teacher
    {
        $user = User::factory()->create(['role' => 'teacher']);

        return Teacher::create(['user_id' => $user->id, 'employee_number' => $number]);
    }

    private function createStudent(string $number): Student
    {
        $user = User::factory()->create(['role' => 'student']);

        return Student::create(['user_id' => $user->id, 'student_number' => $number, 'grade_level' => '2nd Year']);
    }

    private function createSubject(Teacher $teacher): Subject
    {
        return Subject::create([
            'teacher_id' => $teacher->id,
            'code' => 'IT 401',
            'title' => 'Systems Integration',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
            'is_active' => true,
        ]);
    }
}
