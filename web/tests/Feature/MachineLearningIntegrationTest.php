<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AcademicSupportAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MachineLearningIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ml_prediction_is_validated_and_saved_with_the_performance_analysis(): void
    {
        config([
            'services.ml.enabled' => true,
            'services.ml.url' => 'http://ml-service.test',
        ]);
        Http::fake([
            'http://ml-service.test/predict' => Http::response([
                'support_level' => 'HIGH',
                'confidence' => 0.87,
                'probabilities' => ['LOW' => 0.03, 'MODERATE' => 0.10, 'HIGH' => 0.87],
                'model_version' => 'random-forest-v1',
            ]),
        ]);
        $performance = $this->createPerformance();

        $analysis = app(AcademicSupportAssessmentService::class)
            ->assess($performance, ['Database Normalization']);

        $this->assertSame('HIGH', $analysis->support_level->value);
        $this->assertSame('0.8700', $analysis->confidence);
        $this->assertSame('ml_only', $analysis->analysis_source);
        $this->assertSame('random-forest-v1', $analysis->model_version);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://ml-service.test/predict'
            && $request['attendance_rate'] === 68.0
            && $request['missing_submissions'] === 2
            && $request['performance_trend'] === -12.0
        );
    }

    public function test_rules_fallback_keeps_assessment_available_when_ml_service_fails(): void
    {
        config([
            'services.ml.enabled' => true,
            'services.ml.url' => 'http://ml-service.test',
        ]);
        Http::fake(['*' => Http::response(['message' => 'Model unavailable'], 503)]);
        $performance = $this->createPerformance();

        $analysis = app(AcademicSupportAssessmentService::class)->assess($performance);

        $this->assertSame('HIGH', $analysis->support_level->value);
        $this->assertNull($analysis->confidence);
        $this->assertSame('rules_fallback', $analysis->analysis_source);
        $this->assertSame('phase5-rules-v1', $analysis->model_version);
    }

    private function createPerformance(): StudentPerformance
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => fake()->unique()->numerify('T-ML-####'),
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'student_number' => fake()->unique()->numerify('S-ML-####'),
            'grade_level' => '2nd Year',
        ]);
        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => fake()->unique()->bothify('ML-###'),
            'title' => 'Machine Learning Integration Test',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
        ]);

        return StudentPerformance::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'snapshot_date' => now()->toDateString(),
            'attendance_rate' => 68,
            'quiz_average' => 59,
            'assignment_average' => 71,
            'late_submissions' => 4,
            'missing_submissions' => 2,
            'activity_score' => 42,
            'performance_trend' => -12,
        ]);
    }
}
