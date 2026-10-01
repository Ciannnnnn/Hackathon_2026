<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\StudentPerformance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

class LearningAnalyticsService
{
    /** @return array<string, mixed> */
    public function teacher(User $user, ?int $requestedSubjectId = null): array
    {
        $teacher = $user->teacher;
        $subjects = $teacher?->subjects()->where('is_active', true)->orderBy('code')->get() ?? collect();
        $subject = $subjects->firstWhere('id', $requestedSubjectId) ?? $subjects->first();

        if (! $subject) {
            return $this->emptyTeacher($subjects);
        }

        $performances = StudentPerformance::query()
            ->with(['student.user', 'supportAnalysis'])
            ->where('subject_id', $subject->id)
            ->orderBy('snapshot_date')
            ->get();
        $latest = $performances->sortByDesc('snapshot_date')->unique('student_id')->values();
        $attempts = QuizAttempt::query()
            ->with(['quiz', 'student.user'])
            ->whereHas('quiz', fn ($query) => $query->where('subject_id', $subject->id))
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->get();
        $snapshotSeries = $performances->groupBy(fn (StudentPerformance $performance): string => $performance->snapshot_date->format('M d'));
        $quizSeries = $attempts->groupBy('quiz_id')->map(function (Collection $quizAttempts): array {
            $quiz = $quizAttempts->first()->quiz;

            return [
                'label' => $quiz->title,
                'average' => round((float) $quizAttempts->avg(fn (QuizAttempt $attempt): float => $this->attemptPercentage($attempt)), 1),
                'attempts' => $quizAttempts->count(),
            ];
        })->values();

        $supportDistribution = collect(['LOW' => 0, 'MODERATE' => 0, 'HIGH' => 0, 'UNASSESSED' => 0]);
        $latest->each(function (StudentPerformance $performance) use ($supportDistribution): void {
            $level = $performance->supportAnalysis?->support_level?->value ?? 'UNASSESSED';
            $supportDistribution[$level] = $supportDistribution[$level] + 1;
        });

        $weakTopics = $latest->pluck('supportAnalysis')->filter()
            ->flatMap(fn ($analysis): array => $analysis->weak_topics ?? [])
            ->countBy()->sortDesc()->take(8);

        return [
            'subjects' => $subjects,
            'selectedSubject' => $subject,
            'metrics' => [
                'enrolled' => $subject->students()->wherePivot('status', 'active')->count(),
                'assessed' => $latest->count(),
                'support_needed' => $supportDistribution['MODERATE'] + $supportDistribution['HIGH'],
                'average_grade' => $latest->isEmpty() ? 0 : round((float) $latest->avg('overall_grade'), 1),
                'average_attendance' => $latest->isEmpty() ? 0 : round((float) $latest->avg('attendance_rate'), 1),
                'quiz_attempts' => $attempts->count(),
            ],
            'performanceChart' => [
                'labels' => $snapshotSeries->keys()->all(),
                'quiz' => $snapshotSeries->map(fn (Collection $items): float => round((float) $items->avg('quiz_average'), 1))->values()->all(),
                'assignment' => $snapshotSeries->map(fn (Collection $items): float => round((float) $items->avg('assignment_average'), 1))->values()->all(),
                'attendance' => $snapshotSeries->map(fn (Collection $items): float => round((float) $items->avg('attendance_rate'), 1))->values()->all(),
            ],
            'supportChart' => ['labels' => $supportDistribution->keys()->all(), 'values' => $supportDistribution->values()->all()],
            'quizChart' => ['labels' => $quizSeries->pluck('label')->all(), 'values' => $quizSeries->pluck('average')->all()],
            'quizSeries' => $quizSeries,
            'weakTopics' => $weakTopics->map(fn (int $count, string $topic): array => ['topic' => $topic, 'count' => $count])->values(),
            'students' => $latest->sortBy(fn (StudentPerformance $performance): int => match ($performance->supportAnalysis?->support_level?->value) {
                'HIGH' => 0,
                'MODERATE' => 1,
                'LOW' => 2,
                default => 3,
            })->values(),
        ];
    }

    /** @return array<string, mixed> */
    public function student(User $user, ?int $requestedSubjectId = null): array
    {
        $student = $user->student;
        $subjects = $student?->subjects()->where('subjects.is_active', true)->wherePivot('status', 'active')->orderBy('code')->get() ?? collect();
        $subject = $subjects->firstWhere('id', $requestedSubjectId) ?? $subjects->first();

        if (! $student || ! $subject) {
            return $this->emptyStudent($subjects);
        }

        $performances = StudentPerformance::query()
            ->with('supportAnalysis')
            ->where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->orderBy('snapshot_date')
            ->get();
        $attempts = QuizAttempt::query()
            ->with('quiz')
            ->where('student_id', $student->id)
            ->whereHas('quiz', fn ($query) => $query->where('subject_id', $subject->id))
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->get();
        $latest = $performances->last();
        $quizPercentages = $attempts->map(fn (QuizAttempt $attempt): float => $this->attemptPercentage($attempt));
        $weakTopics = $performances->pluck('supportAnalysis')->filter()
            ->flatMap(fn ($analysis): array => $analysis->weak_topics ?? [])
            ->merge($attempts->flatMap(fn (QuizAttempt $attempt): array => $attempt->weak_topics ?? []))
            ->countBy()->sortDesc();

        return [
            'subjects' => $subjects,
            'selectedSubject' => $subject,
            'metrics' => [
                'current_grade' => $latest?->overall_grade ?? 0,
                'attendance' => $latest ? (float) $latest->attendance_rate : 0,
                'quiz_average' => $quizPercentages->isEmpty() ? 0 : round((float) $quizPercentages->avg(), 1),
                'attempts' => $attempts->count(),
                'trend' => $latest ? (float) $latest->performance_trend : 0,
                'support_level' => $latest?->supportAnalysis?->support_level?->value ?? 'UNASSESSED',
            ],
            'performanceChart' => [
                'labels' => $performances->map(fn (StudentPerformance $performance): string => $performance->snapshot_date->format('M d'))->all(),
                'quiz' => $performances->map(fn (StudentPerformance $performance): float => (float) $performance->quiz_average)->all(),
                'assignment' => $performances->map(fn (StudentPerformance $performance): float => (float) $performance->assignment_average)->all(),
                'activity' => $performances->map(fn (StudentPerformance $performance): float => (float) $performance->activity_score)->all(),
                'attendance' => $performances->map(fn (StudentPerformance $performance): float => (float) $performance->attendance_rate)->all(),
            ],
            'quizChart' => [
                'labels' => $attempts->map(fn (QuizAttempt $attempt): string => $attempt->quiz->title.' · '.$attempt->completed_at->format('M d'))->all(),
                'values' => $quizPercentages->all(),
            ],
            'attempts' => $attempts->sortByDesc('completed_at')->values(),
            'history' => $performances->sortByDesc('snapshot_date')->values(),
            'weakTopics' => $weakTopics->map(fn (int $count, string $topic): array => ['topic' => $topic, 'count' => $count])->values(),
        ];
    }

    private function attemptPercentage(QuizAttempt $attempt): float
    {
        return (float) $attempt->max_score > 0
            ? round(((float) $attempt->score / (float) $attempt->max_score) * 100, 1)
            : 0;
    }

    /** @param Collection<int, Subject> $subjects @return array<string, mixed> */
    private function emptyTeacher(Collection $subjects): array
    {
        return [
            'subjects' => $subjects, 'selectedSubject' => null,
            'metrics' => ['enrolled' => 0, 'assessed' => 0, 'support_needed' => 0, 'average_grade' => 0, 'average_attendance' => 0, 'quiz_attempts' => 0],
            'performanceChart' => ['labels' => [], 'quiz' => [], 'assignment' => [], 'attendance' => []],
            'supportChart' => ['labels' => ['LOW', 'MODERATE', 'HIGH', 'UNASSESSED'], 'values' => [0, 0, 0, 0]],
            'quizChart' => ['labels' => [], 'values' => []], 'quizSeries' => collect(), 'weakTopics' => collect(), 'students' => collect(),
        ];
    }

    /** @param Collection<int, Subject> $subjects @return array<string, mixed> */
    private function emptyStudent(Collection $subjects): array
    {
        return [
            'subjects' => $subjects, 'selectedSubject' => null,
            'metrics' => ['current_grade' => 0, 'attendance' => 0, 'quiz_average' => 0, 'attempts' => 0, 'trend' => 0, 'support_level' => 'UNASSESSED'],
            'performanceChart' => ['labels' => [], 'quiz' => [], 'assignment' => [], 'activity' => [], 'attendance' => []],
            'quizChart' => ['labels' => [], 'values' => []], 'attempts' => collect(), 'history' => collect(), 'weakTopics' => collect(),
        ];
    }
}
