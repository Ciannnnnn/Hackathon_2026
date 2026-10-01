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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_presents_the_edupulse_value_proposition(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Detect learning difficulties early')
            ->assertSee('Personalize learning automatically')
            ->assertSee('Teacher analytics');
    }

    public function test_teacher_dashboard_aggregates_students_and_support_metrics(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => 'T-DASH-001',
            'department' => 'Information Technology',
        ]);
        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => 'IT-DB201',
            'title' => 'Database Management Systems',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
        ]);

        $alex = $this->createStudent('Alex', 'Santos', 'S-DASH-001', $subject->id);
        $bea = $this->createStudent('Bea', 'Cruz', 'S-DASH-002', $subject->id);

        $alexPerformance = $this->createPerformance($alex->id, $subject->id, 68, 59, 71, -12);
        $beaPerformance = $this->createPerformance($bea->id, $subject->id, 96, 95, 94, 4);

        $this->createAnalysis($alexPerformance->id, 'HIGH', ['Database Normalization']);
        $this->createAnalysis($beaPerformance->id, 'LOW', []);

        $response = $this->actingAs($teacherUser)->get(route('teacher.dashboard'));

        $response
            ->assertOk()
            ->assertSee('Student academic pulse')
            ->assertSee('Alex Santos')
            ->assertSee('Database Normalization')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total_students'] === 2 &&
                $metrics['support_needed'] === 1 &&
                $metrics['average_attendance'] === 82.0
            );
    }

    public function test_student_dashboard_displays_current_performance_and_support_context(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => 'T-DASH-002',
        ]);
        $studentUser = User::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'student_number' => 'S-DASH-003',
            'grade_level' => '2nd Year',
            'program' => 'BS Information Technology',
        ]);
        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => 'IT-DB201',
            'title' => 'Database Management Systems',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $performance = $this->createPerformance($student->id, $subject->id, 68, 59, 71, -12);
        $this->createAnalysis($performance->id, 'HIGH', ['Database Normalization']);

        $this->actingAs($studentUser)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Keep moving forward, Alex')
            ->assertSee('Database Normalization')
            ->assertViewHas('current', fn (array $current): bool => $current['attendance'] === 68.0)
            ->assertViewHas('status', fn (array $status): bool => $status['support_level'] === 'HIGH');
    }

    private function createStudent(string $firstName, string $lastName, string $number, int $subjectId): Student
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
            'subject_id' => $subjectId,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        return $student;
    }

    private function createPerformance(
        int $studentId,
        int $subjectId,
        float $attendance,
        float $quiz,
        float $assignment,
        float $trend,
    ): StudentPerformance {
        return StudentPerformance::create([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'snapshot_date' => '2026-09-30',
            'attendance_rate' => $attendance,
            'quiz_average' => $quiz,
            'assignment_average' => $assignment,
            'late_submissions' => $trend < 0 ? 4 : 0,
            'missing_submissions' => $trend < 0 ? 2 : 0,
            'activity_score' => $trend < 0 ? 42 : 93,
            'performance_trend' => $trend,
        ]);
    }

    /** @param list<string> $weakTopics */
    private function createAnalysis(int $performanceId, string $level, array $weakTopics): void
    {
        StudentSupportAnalysis::create([
            'performance_id' => $performanceId,
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
