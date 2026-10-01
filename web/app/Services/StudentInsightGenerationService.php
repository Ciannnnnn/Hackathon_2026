<?php

namespace App\Services;

use App\Data\AiResult;
use App\Models\Recommendation;
use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentInsightGenerationService
{
    public function __construct(
        private readonly AiService $ai,
        private readonly AcademicSupportAssessmentService $assessment,
    ) {}

    /** @return array{analysis: AiResult, plan: AiResult, study_plan: StudyPlan} */
    public function generate(Student $student, int $subjectId): array
    {
        $subject = Subject::query()->findOrFail($subjectId);
        $performance = StudentPerformance::query()
            ->with('supportAnalysis')
            ->where('student_id', $student->id)
            ->where('subject_id', $subjectId)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        if (! $performance) {
            throw ValidationException::withMessages([
                'analysis' => 'Record a performance snapshot before generating AI insights.',
            ]);
        }

        $supportAnalysis = $performance->supportAnalysis
            ?? $this->assessment->assess($performance);
        $academicData = $this->academicData($performance, $subject, $supportAnalysis->support_level->value, $supportAnalysis->weak_topics);
        $analysisResult = $this->ai->analyzeStudentPerformance($academicData);
        $analysisContent = $this->validatedAnalysis($analysisResult);
        $planResult = $this->ai->generateStudyPlan([
            ...$academicData,
            'weak_topics' => $analysisContent['weak_topics'],
            'recommended_actions' => $analysisContent['recommended_actions'],
        ]);
        $planContent = $this->validatedPlan($planResult);

        $studyPlan = DB::transaction(function () use (
            $student,
            $subject,
            $supportAnalysis,
            $analysisResult,
            $analysisContent,
            $planContent,
        ): StudyPlan {
            $source = $supportAnalysis->analysis_source;
            if (! $analysisResult->fallback && in_array($source, ['ml_only', 'ml_ai'], true)) {
                $source = 'ml_ai';
            }

            $supportAnalysis->update([
                'weak_topics' => $analysisContent['weak_topics'],
                'ai_summary' => $analysisContent['summary'],
                'analysis_source' => $source,
                'analyzed_at' => now(),
            ]);

            Recommendation::query()
                ->where('support_analysis_id', $supportAnalysis->id)
                ->where('created_by', 'ai')
                ->whereIn('status', ['pending', 'dismissed'])
                ->delete();

            $priority = match ($supportAnalysis->support_level->value) {
                'HIGH' => 'high',
                'MODERATE' => 'medium',
                default => 'low',
            };

            foreach ($analysisContent['recommended_actions'] as $action) {
                Recommendation::create([
                    'support_analysis_id' => $supportAnalysis->id,
                    'recommendation_text' => $action,
                    'priority' => $priority,
                    'status' => 'pending',
                    'created_by' => 'ai',
                ]);
            }

            StudyPlan::query()
                ->where('student_id', $student->id)
                ->where('subject_id', $subject->id)
                ->where('generated_by', 'ai')
                ->whereIn('status', ['active', 'draft'])
                ->update(['status' => 'archived']);

            $startDate = today();
            $plan = StudyPlan::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'support_analysis_id' => $supportAnalysis->id,
                'title' => $planContent['title'],
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays(6),
                'status' => 'active',
                'generated_by' => 'ai',
            ]);

            foreach ($planContent['items'] as $item) {
                $plan->items()->create([
                    'day_number' => $item['day'],
                    'scheduled_date' => $startDate->copy()->addDays($item['day'] - 1),
                    'topic' => $item['topic'],
                    'task' => $item['task'],
                ]);
            }

            return $plan->load('items');
        });

        return ['analysis' => $analysisResult, 'plan' => $planResult, 'study_plan' => $studyPlan];
    }

    /**
     * @param  list<string>  $weakTopics
     * @return array<string, mixed>
     */
    private function academicData(StudentPerformance $performance, Subject $subject, string $supportLevel, array $weakTopics): array
    {
        return [
            'subject' => ['code' => $subject->code, 'title' => $subject->title],
            'attendance_rate' => (float) $performance->attendance_rate,
            'quiz_average' => (float) $performance->quiz_average,
            'assignment_average' => (float) $performance->assignment_average,
            'late_submissions' => $performance->late_submissions,
            'missing_submissions' => $performance->missing_submissions,
            'activity_score' => (float) $performance->activity_score,
            'performance_trend' => (float) $performance->performance_trend,
            'support_level' => $supportLevel,
            'weak_topics' => $weakTopics,
        ];
    }

    /** @return array{summary: string, weak_topics: list<string>, recommended_actions: list<string>} */
    private function validatedAnalysis(AiResult $result): array
    {
        if (! is_array($result->content)) {
            throw ValidationException::withMessages(['analysis' => 'The AI analysis response was invalid.']);
        }

        $summary = trim((string) ($result->content['summary'] ?? ''));
        $weakTopics = $this->stringList($result->content['weak_topics'] ?? [], 8, 160);
        $actions = $this->stringList($result->content['recommended_actions'] ?? [], 8, 500);

        if ($summary === '' || $actions === []) {
            throw ValidationException::withMessages(['analysis' => 'The AI analysis omitted required educational guidance.']);
        }

        return [
            'summary' => mb_substr($summary, 0, 5000),
            'weak_topics' => $weakTopics,
            'recommended_actions' => $actions,
        ];
    }

    /** @return array{title: string, items: list<array{day: int, topic: string, task: string}>} */
    private function validatedPlan(AiResult $result): array
    {
        if (! is_array($result->content)) {
            throw ValidationException::withMessages(['analysis' => 'The AI study-plan response was invalid.']);
        }

        $title = trim((string) ($result->content['title'] ?? ''));
        $items = collect($result->content['items'] ?? [])
            ->filter(fn ($item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'day' => (int) ($item['day'] ?? 0),
                'topic' => mb_substr(trim((string) ($item['topic'] ?? '')), 0, 160),
                'task' => mb_substr(trim((string) ($item['task'] ?? '')), 0, 2000),
            ])
            ->filter(fn (array $item): bool => in_array($item['day'], range(1, 7), true)
                && $item['topic'] !== ''
                && $item['task'] !== '')
            ->unique('day')
            ->sortBy('day')
            ->values();

        if ($title === '' || $items->count() !== 7) {
            throw ValidationException::withMessages(['analysis' => 'The AI response did not contain a complete seven-day study plan.']);
        }

        return [
            'title' => mb_substr($title, 0, 180),
            'items' => $items->all(),
        ];
    }

    /** @return list<string> */
    private function stringList(mixed $items, int $limit, int $length): array
    {
        return collect(is_array($items) ? $items : [])
            ->filter(fn ($item): bool => is_string($item))
            ->map(fn (string $item): string => mb_substr(trim($item), 0, $length))
            ->filter()
            ->unique(fn (string $item): string => mb_strtolower($item))
            ->take($limit)
            ->values()
            ->all();
    }
}
