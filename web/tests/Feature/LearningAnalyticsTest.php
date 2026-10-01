<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GeneratedQuiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\StudentSupportAnalysis;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_analytics_are_scoped_to_their_subjects(): void
    {
        [$teacherUser, $subject] = $this->teacherSubject('OWN');
        [$otherTeacher, $otherSubject] = $this->teacherSubject('OTHER');
        [$student] = $this->studentIn($subject, 'Alex', 'S-AN-001');
        [$outsideStudent] = $this->studentIn($otherSubject, 'Private', 'S-AN-002');
        $performance = $this->performance($student, $subject, 68, 60, 72);
        $this->analysis($performance, 'HIGH', ['Database Normalization']);
        $this->performance($outsideStudent, $otherSubject, 99, 99, 99);

        $response = $this->actingAs($teacherUser)->get(route('teacher.analytics'));

        $response->assertOk()
            ->assertSee('Learning analytics')
            ->assertSee('Alex')
            ->assertSee('Database Normalization')
            ->assertDontSee('Private')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['enrolled'] === 1
                && $metrics['assessed'] === 1
                && $metrics['support_needed'] === 1);

        $this->actingAs($teacherUser)
            ->get(route('teacher.analytics', ['subject' => $otherSubject->id]))
            ->assertOk()
            ->assertViewHas('selectedSubject', fn (Subject $selected): bool => $selected->is($subject));
        $this->assertNotNull($otherTeacher);
    }

    public function test_student_progress_combines_performance_and_generated_quiz_attempts(): void
    {
        [$teacherUser, $subject] = $this->teacherSubject('STUDENT');
        [$student, $studentUser] = $this->studentIn($subject, 'Alex', 'S-AN-003');
        $performance = $this->performance($student, $subject, 80, 70, 75);
        $this->analysis($performance, 'MODERATE', ['SQL Joins']);
        $quiz = GeneratedQuiz::create([
            'subject_id' => $subject->id,
            'created_by_user_id' => $teacherUser->id,
            'title' => 'Join Practice',
            'topic' => 'SQL Joins',
            'difficulty' => 'medium',
            'question_count' => 2,
            'is_published' => true,
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => 1,
            'max_score' => 2,
            'weak_topics' => ['SQL Joins'],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($studentUser)->get(route('student.progress'))
            ->assertOk()
            ->assertSee('My progress')
            ->assertSee('Join Practice')
            ->assertSee('SQL Joins')
            ->assertSee('50%')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['attempts'] === 1
                && $metrics['quiz_average'] === 50.0
                && $metrics['support_level'] === 'MODERATE');
    }

    public function test_analytics_and_operational_pages_enforce_roles(): void
    {
        [$teacherUser, $subject] = $this->teacherSubject('ROLES');
        [, $studentUser] = $this->studentIn($subject, 'Learner', 'S-AN-004');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($studentUser)->get(route('teacher.analytics'))->assertForbidden();
        $this->actingAs($teacherUser)->get(route('student.progress'))->assertForbidden();
        $this->actingAs($teacherUser)->get(route('admin.health'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.health'))->assertOk()->assertSee('System health');
    }

    /** @return array{User, Subject} */
    private function teacherSubject(string $suffix): array
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $user->id, 'employee_number' => "T-AN-{$suffix}"]);
        $subject = Subject::create(['teacher_id' => $teacher->id, 'code' => "IT-AN-{$suffix}", 'title' => 'Analytics Subject', 'school_year' => '2026-2027', 'term' => '1st Semester', 'is_active' => true]);

        return [$user, $subject];
    }

    /** @return array{Student, User} */
    private function studentIn(Subject $subject, string $firstName, string $number): array
    {
        $user = User::factory()->create(['role' => 'student', 'first_name' => $firstName]);
        $student = Student::create(['user_id' => $user->id, 'student_number' => $number, 'grade_level' => '2nd Year']);
        Enrollment::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'status' => 'active', 'enrolled_at' => now()]);

        return [$student, $user];
    }

    private function performance(Student $student, Subject $subject, float $attendance, float $quiz, float $assignment): StudentPerformance
    {
        return StudentPerformance::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'snapshot_date' => '2026-10-01',
            'attendance_rate' => $attendance,
            'quiz_average' => $quiz,
            'assignment_average' => $assignment,
            'late_submissions' => 1,
            'missing_submissions' => 1,
            'activity_score' => 70,
            'performance_trend' => -2,
        ]);
    }

    /** @param list<string> $weakTopics */
    private function analysis(StudentPerformance $performance, string $level, array $weakTopics): void
    {
        StudentSupportAnalysis::create([
            'performance_id' => $performance->id,
            'support_level' => $level,
            'confidence' => 0.85,
            'weak_topics' => $weakTopics,
            'ai_summary' => 'Focused review is recommended.',
            'model_version' => 'test-v1',
            'analysis_source' => 'ml_ai',
            'analyzed_at' => now(),
        ]);
    }
}
