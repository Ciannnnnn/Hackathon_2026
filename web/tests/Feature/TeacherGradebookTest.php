<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\StudentSupportAnalysis;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherGradebookTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_record_grades_for_an_enrolled_student(): void
    {
        [$teacherUser, $subject, $student] = $this->gradebookFixture('A');
        StudentPerformance::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'snapshot_date' => now()->subDay()->toDateString(),
            'attendance_rate' => 90,
            'quiz_average' => 70,
            'assignment_average' => 74,
            'activity_score' => 72,
            'late_submissions' => 0,
            'missing_submissions' => 0,
            'performance_trend' => 0,
        ]);

        $response = $this->actingAs($teacherUser)->post(route('teacher.grades.store', $student), [
            'subject_id' => $subject->id,
            'snapshot_date' => now()->toDateString(),
            'attendance_rate' => 92,
            'quiz_average' => 82,
            'assignment_average' => 88,
            'activity_score' => 85,
            'late_submissions' => 1,
            'missing_submissions' => 0,
            'weak_topics' => 'SQL Joins, Normalization',
        ]);

        $performance = StudentPerformance::query()->latest('snapshot_date')->firstOrFail();

        $response->assertRedirect(route('teacher.grades.index', ['subject' => $subject->id]))->assertSessionHas('status');
        $this->assertSame(85.0, $performance->overall_grade);
        $this->assertSame('13.00', $performance->performance_trend);
        $this->assertDatabaseHas('student_support_analysis', ['performance_id' => $performance->id]);
        $this->assertSame(['SQL Joins', 'Normalization'], StudentSupportAnalysis::query()->latest('id')->firstOrFail()->weak_topics);
    }

    public function test_teacher_only_sees_their_subjects_and_enrolled_students(): void
    {
        [$teacherUser, $subject, $student] = $this->gradebookFixture('B');
        [, $otherSubject, $otherStudent] = $this->gradebookFixture('C');

        $this->actingAs($teacherUser)
            ->get(route('teacher.grades.index'))
            ->assertOk()
            ->assertSee($subject->code)
            ->assertSee($student->user->full_name)
            ->assertDontSee($otherSubject->code)
            ->assertDontSee($otherStudent->user->full_name);
    }

    public function test_teacher_cannot_grade_a_student_outside_their_class(): void
    {
        [$teacherUser, $subject] = $this->gradebookFixture('D');
        [, , $outsideStudent] = $this->gradebookFixture('E');

        $this->actingAs($teacherUser)->post(route('teacher.grades.store', $outsideStudent), [
            'subject_id' => $subject->id,
            'snapshot_date' => now()->toDateString(),
            'attendance_rate' => 90,
            'quiz_average' => 90,
            'assignment_average' => 90,
            'activity_score' => 90,
            'late_submissions' => 0,
            'missing_submissions' => 0,
        ])->assertForbidden();

        $this->assertDatabaseCount('student_performance', 0);
    }

    public function test_grade_inputs_are_validated(): void
    {
        [$teacherUser, $subject, $student] = $this->gradebookFixture('F');

        $this->actingAs($teacherUser)->post(route('teacher.grades.store', $student), [
            'subject_id' => $subject->id,
            'snapshot_date' => now()->addDay()->toDateString(),
            'attendance_rate' => 101,
            'quiz_average' => -1,
            'assignment_average' => 80,
            'activity_score' => 80,
            'late_submissions' => 0,
            'missing_submissions' => 0,
        ])->assertSessionHasErrors(['snapshot_date', 'attendance_rate', 'quiz_average']);
    }

    /** @return array{User, Subject, Student} */
    private function gradebookFixture(string $suffix): array
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_number' => "T-GRADE-{$suffix}"]);
        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => "IT-GRADE-{$suffix}",
            'title' => 'Database Management',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
        ]);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'student_number' => "S-GRADE-{$suffix}", 'grade_level' => '2nd Year']);
        Enrollment::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'status' => 'active', 'enrolled_at' => now()]);

        return [$teacherUser, $subject, $student];
    }
}
