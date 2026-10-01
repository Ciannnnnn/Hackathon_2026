<?php

namespace Tests\Feature;

use App\Enums\SupportLevel;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\StudentSupportAnalysis;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicCoreDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_models_persist_with_the_expected_relationships_and_casts(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $studentUser = User::factory()->create(['role' => 'student']);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => 'T-TEST-001',
            'department' => 'Information Technology',
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'student_number' => 'S-TEST-001',
            'grade_level' => '2nd Year',
            'program' => 'BS Information Technology',
        ]);

        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => 'IT-TEST',
            'title' => 'Test Subject',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
        ]);

        $student->subjects()->attach($subject, [
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $performance = StudentPerformance::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'snapshot_date' => '2026-09-30',
            'attendance_rate' => 68,
            'quiz_average' => 59,
            'assignment_average' => 71,
            'late_submissions' => 4,
            'missing_submissions' => 2,
            'activity_score' => 42,
            'performance_trend' => -12,
        ]);

        StudentSupportAnalysis::create([
            'performance_id' => $performance->id,
            'support_level' => 'HIGH',
            'confidence' => 0.87,
            'weak_topics' => ['Database Normalization'],
            'ai_summary' => 'Targeted academic support is recommended.',
            'model_version' => 'test-v1',
            'analysis_source' => 'ml_ai',
            'analyzed_at' => now(),
        ]);

        $this->assertSame(UserRole::Teacher, $teacher->user->role);
        $this->assertTrue($student->subjects->contains($subject));
        $this->assertSame('68.00', $performance->attendance_rate);
        $this->assertSame(SupportLevel::High, $performance->supportAnalysis->support_level);
        $this->assertSame(['Database Normalization'], $performance->supportAnalysis->weak_topics);
    }
}
