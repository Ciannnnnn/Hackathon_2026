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

class TeacherStudentAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_filter_their_student_roster_and_open_an_analysis(): void
    {
        [$teacherUser, $subject] = $this->createTeacherAndSubject('T-PH5-001', 'IT-PH5-A');
        $alex = $this->createStudentInSubject($subject, 'Alex', 'Santos', 'S-PH5-001');
        $bea = $this->createStudentInSubject($subject, 'Bea', 'Cruz', 'S-PH5-002');

        $performance = $this->createPerformance($alex, $subject, [
            'attendance_rate' => 68,
            'quiz_average' => 59,
            'assignment_average' => 71,
            'missing_submissions' => 2,
            'activity_score' => 42,
            'performance_trend' => -12,
        ]);
        $this->createAnalysis($performance, 'HIGH', ['Database Normalization']);

        $this->actingAs($teacherUser)
            ->get(route('teacher.students.index', ['search' => 'Alex', 'support' => 'HIGH']))
            ->assertOk()
            ->assertSee('Alex Santos')
            ->assertDontSee('Bea Cruz')
            ->assertSee('IT-PH5-A');

        $this->actingAs($teacherUser)
            ->get(route('teacher.students.show', $alex))
            ->assertOk()
            ->assertSee('Alex Santos')
            ->assertSee('Database Normalization')
            ->assertSee('Performance history')
            ->assertSee('Record performance snapshot');

        $this->assertNotNull($bea);
    }

    public function test_teacher_cannot_view_a_student_outside_their_classes(): void
    {
        [$teacherUser] = $this->createTeacherAndSubject('T-PH5-002', 'IT-PH5-B');
        [, $otherSubject] = $this->createTeacherAndSubject('T-PH5-003', 'IT-PH5-C');
        $otherStudent = $this->createStudentInSubject($otherSubject, 'Outside', 'Learner', 'S-PH5-003');

        $this->actingAs($teacherUser)
            ->get(route('teacher.students.show', $otherStudent))
            ->assertNotFound();
    }

    public function test_teacher_can_save_a_snapshot_and_receive_a_provisional_support_level(): void
    {
        [$teacherUser, $subject] = $this->createTeacherAndSubject('T-PH5-004', 'IT-PH5-D');
        $student = $this->createStudentInSubject($subject, 'Alex', 'Santos', 'S-PH5-004');
        $snapshotDate = now()->toDateString();

        $response = $this->actingAs($teacherUser)->post(
            route('teacher.students.performance.store', $student),
            [
                'subject_id' => $subject->id,
                'snapshot_date' => $snapshotDate,
                'attendance_rate' => 68,
                'quiz_average' => 59,
                'assignment_average' => 71,
                'late_submissions' => 4,
                'missing_submissions' => 2,
                'activity_score' => 42,
                'performance_trend' => -12,
                'weak_topics' => 'Database Normalization, SQL Joins, database normalization',
            ],
        );

        $performance = StudentPerformance::query()->sole();
        $analysis = StudentSupportAnalysis::query()->sole();

        $response
            ->assertRedirect(route('teacher.students.show', ['student' => $student, 'subject' => $subject->id]))
            ->assertSessionHas('status');
        $this->assertSame('HIGH', $analysis->support_level->value);
        $this->assertSame('rules_fallback', $analysis->analysis_source);
        $this->assertSame(['Database Normalization', 'SQL Joins'], $analysis->weak_topics);
        $this->assertSame($performance->id, $analysis->performance_id);
    }

    public function test_performance_snapshot_inputs_are_validated(): void
    {
        [$teacherUser, $subject] = $this->createTeacherAndSubject('T-PH5-005', 'IT-PH5-E');
        $student = $this->createStudentInSubject($subject, 'Validation', 'Student', 'S-PH5-005');

        $this->actingAs($teacherUser)
            ->from(route('teacher.students.show', $student))
            ->post(route('teacher.students.performance.store', $student), [
                'subject_id' => $subject->id,
                'snapshot_date' => now()->addDay()->toDateString(),
                'attendance_rate' => 101,
                'quiz_average' => -1,
                'assignment_average' => 80,
                'late_submissions' => 0,
                'missing_submissions' => 0,
                'activity_score' => 70,
                'performance_trend' => 0,
            ])
            ->assertRedirect(route('teacher.students.show', $student))
            ->assertSessionHasErrors(['snapshot_date', 'attendance_rate', 'quiz_average']);

        $this->assertDatabaseCount('student_performance', 0);
    }

    /** @return array{User, Subject} */
    private function createTeacherAndSubject(string $employeeNumber, string $subjectCode): array
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'employee_number' => $employeeNumber,
            'department' => 'Information Technology',
        ]);
        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => $subjectCode,
            'title' => 'Database Management Systems',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
        ]);

        return [$user, $subject];
    }

    private function createStudentInSubject(Subject $subject, string $firstName, string $lastName, string $number): Student
    {
        $user = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => $number,
            'grade_level' => '2nd Year',
            'program' => 'BS Information Technology',
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        return $student;
    }

    /** @param array<string, int> $overrides */
    private function createPerformance(Student $student, Subject $subject, array $overrides = []): StudentPerformance
    {
        return StudentPerformance::create(array_merge([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'snapshot_date' => now()->toDateString(),
            'attendance_rate' => 90,
            'quiz_average' => 90,
            'assignment_average' => 90,
            'late_submissions' => 0,
            'missing_submissions' => 0,
            'activity_score' => 90,
            'performance_trend' => 2,
        ], $overrides));
    }

    /** @param list<string> $weakTopics */
    private function createAnalysis(StudentPerformance $performance, string $level, array $weakTopics): void
    {
        StudentSupportAnalysis::create([
            'performance_id' => $performance->id,
            'support_level' => $level,
            'confidence' => 0.87,
            'weak_topics' => $weakTopics,
            'ai_summary' => 'Targeted academic support is recommended.',
            'model_version' => 'test-v1',
            'analysis_source' => 'ml_ai',
            'analyzed_at' => now(),
        ]);
    }
}
