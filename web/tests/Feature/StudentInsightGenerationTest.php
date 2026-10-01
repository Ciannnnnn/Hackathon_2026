<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Recommendation;
use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\StudentSupportAnalysis;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentInsightGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_generate_and_persist_ai_analysis_recommendations_and_study_plan(): void
    {
        [$teacherUser, $student, $subject, $analysis] = $this->academicContext();
        $oldPlan = StudyPlan::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'support_analysis_id' => $analysis->id,
            'title' => 'Previous AI Plan',
            'start_date' => today()->subWeek(),
            'end_date' => today()->subDay(),
            'status' => 'active',
            'generated_by' => 'ai',
        ]);
        Recommendation::create([
            'support_analysis_id' => $analysis->id,
            'recommendation_text' => 'Previous pending AI recommendation.',
            'priority' => 'high',
            'status' => 'pending',
            'created_by' => 'ai',
        ]);
        Recommendation::create([
            'support_analysis_id' => $analysis->id,
            'recommendation_text' => 'Teacher consultation remains scheduled.',
            'priority' => 'medium',
            'status' => 'in_progress',
            'created_by' => 'teacher',
        ]);
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.fallback_models' => [],
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
        ]);
        Http::fakeSequence()
            ->push($this->geminiResponse([
                'summary' => 'Quiz performance and attendance show that focused normalization support is recommended.',
                'weak_topics' => ['Database Normalization', 'SQL Joins'],
                'recommended_actions' => [
                    'Review first, second, and third normal forms.',
                    'Complete five normalization exercises and review mistakes.',
                ],
            ]))
            ->push($this->geminiResponse([
                'title' => '7-Day Database Normalization Plan',
                'items' => collect(range(1, 7))->map(fn (int $day): array => [
                    'day' => $day,
                    'topic' => "Normalization Day {$day}",
                    'task' => "Complete focused learning task {$day}.",
                ])->all(),
            ]));

        $response = $this->actingAs($teacherUser)->post(
            route('teacher.students.insights.store', $student),
            ['subject_id' => $subject->id],
        );

        $response
            ->assertRedirect(route('teacher.students.show', ['student' => $student, 'subject' => $subject->id]))
            ->assertSessionHas('status', fn (string $message): bool => str_contains($message, 'Gemini analysis'));
        $this->assertSame('ml_ai', $analysis->fresh()->analysis_source);
        $this->assertSame(['Database Normalization', 'SQL Joins'], $analysis->fresh()->weak_topics);
        $this->assertDatabaseCount('recommendations', 3);
        $this->assertDatabaseHas('recommendations', [
            'support_analysis_id' => $analysis->id,
            'recommendation_text' => 'Review first, second, and third normal forms.',
            'created_by' => 'ai',
        ]);
        $this->assertDatabaseHas('study_plans', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'title' => '7-Day Database Normalization Plan',
            'status' => 'active',
        ]);
        $this->assertSame('archived', $oldPlan->fresh()->status);
        $this->assertDatabaseHas('recommendations', [
            'support_analysis_id' => $analysis->id,
            'recommendation_text' => 'Teacher consultation remains scheduled.',
            'created_by' => 'teacher',
        ]);
        $this->assertDatabaseMissing('recommendations', [
            'recommendation_text' => 'Previous pending AI recommendation.',
        ]);
        $this->assertDatabaseCount('study_plan_items', 7);
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            $body = json_encode($request->data());

            return ! str_contains($body, 'Alex Santos')
                && ! str_contains($body, 'alex@example.test')
                && ! str_contains($body, 'S-INSIGHT-001');
        });
    }

    public function test_demo_fallback_still_saves_useful_insights_when_gemini_is_not_configured(): void
    {
        [$teacherUser, $student, $subject, $analysis] = $this->academicContext();
        config([
            'services.gemini.key' => null,
            'services.gemini.demo_fallback' => true,
        ]);
        Http::preventStrayRequests();

        $this->actingAs($teacherUser)
            ->post(route('teacher.students.insights.store', $student), ['subject_id' => $subject->id])
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $message): bool => str_contains($message, 'fallback'));

        $this->assertDatabaseHas('student_support_analysis', [
            'id' => $analysis->id,
            'analysis_source' => 'ml_only',
        ]);
        $this->assertDatabaseCount('recommendations', 3);
        $this->assertDatabaseHas('study_plans', [
            'student_id' => $student->id,
            'title' => '7-Day Focused Review Plan',
            'status' => 'active',
        ]);
        $this->assertDatabaseCount('study_plan_items', 7);
        Http::assertNothingSent();
    }

    public function test_teacher_cannot_generate_insights_for_a_student_outside_their_class(): void
    {
        [, $student, $subject] = $this->academicContext();
        $otherTeacherUser = User::factory()->create(['role' => 'teacher']);
        Teacher::create([
            'user_id' => $otherTeacherUser->id,
            'employee_number' => 'T-INSIGHT-OTHER',
        ]);
        Http::preventStrayRequests();

        $this->actingAs($otherTeacherUser)
            ->post(route('teacher.students.insights.store', $student), ['subject_id' => $subject->id])
            ->assertForbidden();

        $this->assertDatabaseCount('study_plans', 0);
        Http::assertNothingSent();
    }

    public function test_performance_snapshot_is_required_before_generating_insights(): void
    {
        [$teacherUser, $subject] = $this->teacherAndSubject();
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'student_number' => 'S-INSIGHT-EMPTY',
            'grade_level' => '2nd Year',
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($teacherUser)
            ->from(route('teacher.students.show', $student))
            ->post(route('teacher.students.insights.store', $student), ['subject_id' => $subject->id])
            ->assertRedirect(route('teacher.students.show', $student))
            ->assertSessionHasErrors('analysis');
    }

    /** @return array{User, Student, Subject, StudentSupportAnalysis} */
    private function academicContext(): array
    {
        [$teacherUser, $subject] = $this->teacherAndSubject();
        $studentUser = User::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'email' => 'alex@example.test',
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'student_number' => 'S-INSIGHT-001',
            'grade_level' => '2nd Year',
            'program' => 'BS Information Technology',
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $performance = StudentPerformance::create([
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
        $analysis = StudentSupportAnalysis::create([
            'performance_id' => $performance->id,
            'support_level' => 'HIGH',
            'confidence' => 0.87,
            'weak_topics' => ['Database Normalization'],
            'ai_summary' => 'An ML assessment is ready for AI enrichment.',
            'model_version' => 'random-forest-v1',
            'analysis_source' => 'ml_only',
            'analyzed_at' => now(),
        ]);

        return [$teacherUser, $student, $subject, $analysis];
    }

    /** @return array{User, Subject} */
    private function teacherAndSubject(): array
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'employee_number' => fake()->unique()->numerify('T-INSIGHT-####'),
        ]);
        $subject = Subject::create([
            'teacher_id' => $teacher->id,
            'code' => fake()->unique()->bothify('AI-###'),
            'title' => 'Database Management Systems',
            'school_year' => '2026-2027',
            'term' => '1st Semester',
        ]);

        return [$teacherUser, $subject];
    }

    /** @param array<string, mixed> $content @return array<string, mixed> */
    private function geminiResponse(array $content): array
    {
        return [
            'candidates' => [[
                'content' => ['parts' => [['text' => json_encode($content, JSON_THROW_ON_ERROR)]]],
                'finishReason' => 'STOP',
            ]],
            'modelVersion' => 'gemini-test-model-001',
        ];
    }
}
