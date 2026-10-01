<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardDataService
{
    /** @return array<string, mixed> */
    public function teacher(User $user): array
    {
        $teacher = $user->teacher()->first();

        if (! $teacher) {
            return $this->emptyTeacherDashboard();
        }

        $subjects = $teacher->subjects()->where('is_active', true)->orderBy('code')->get();
        $subjectIds = $subjects->pluck('id');

        $students = $subjectIds->isEmpty()
            ? collect()
            : Student::query()
                ->with('user')
                ->whereHas('enrollments', fn ($query) => $query
                    ->whereIn('subject_id', $subjectIds)
                    ->where('status', 'active'))
                ->orderBy('id')
                ->get();

        $performances = $subjectIds->isEmpty()
            ? collect()
            : StudentPerformance::query()
                ->with(['student.user', 'subject', 'supportAnalysis'])
                ->whereIn('subject_id', $subjectIds)
                ->orderBy('snapshot_date')
                ->get();

        $latestByStudent = $performances
            ->sortByDesc(fn (StudentPerformance $performance) => $performance->snapshot_date->getTimestamp())
            ->unique('student_id')
            ->keyBy('student_id');

        $studentRows = $students->map(function ($student) use ($latestByStudent): array {
            $performance = $latestByStudent->get($student->id);
            $analysis = $performance?->supportAnalysis;
            $supportLevel = $analysis?->support_level?->value ?? 'UNASSESSED';
            $trend = (float) ($performance?->performance_trend ?? 0);

            return [
                'id' => $student->id,
                'name' => $student->user->full_name,
                'student_number' => $student->student_number,
                'attendance' => $performance ? (float) $performance->attendance_rate : null,
                'quiz_average' => $performance ? (float) $performance->quiz_average : null,
                'assignment_average' => $performance ? (float) $performance->assignment_average : null,
                'late_submissions' => $performance?->late_submissions ?? 0,
                'missing_submissions' => $performance?->missing_submissions ?? 0,
                'trend' => $trend,
                'trend_label' => $this->trendLabel($trend),
                'support_level' => $supportLevel,
                'weak_topics' => $analysis?->weak_topics ?? [],
            ];
        })->sortBy(fn (array $student) => match ($student['support_level']) {
            'HIGH' => 0,
            'MODERATE' => 1,
            'LOW' => 2,
            default => 3,
        })->values();

        $assessedRows = $studentRows->whereNotNull('quiz_average');
        $supportDistribution = [
            'LOW' => $studentRows->where('support_level', 'LOW')->count(),
            'MODERATE' => $studentRows->where('support_level', 'MODERATE')->count(),
            'HIGH' => $studentRows->where('support_level', 'HIGH')->count(),
            'UNASSESSED' => $studentRows->where('support_level', 'UNASSESSED')->count(),
        ];

        $trendSeries = $performances
            ->groupBy(fn (StudentPerformance $performance) => $performance->snapshot_date->format('M d'))
            ->map(fn (Collection $items, string $label): array => [
                'label' => $label,
                'quiz' => round((float) $items->avg('quiz_average'), 1),
                'assignment' => round((float) $items->avg('assignment_average'), 1),
            ])->values();

        $weakTopics = $performances
            ->pluck('supportAnalysis')
            ->filter()
            ->flatMap(fn ($analysis) => $analysis->weak_topics ?? [])
            ->countBy()
            ->sortDesc()
            ->take(5);

        return [
            'metrics' => [
                'total_students' => $studentRows->count(),
                'support_needed' => $supportDistribution['HIGH'] + $supportDistribution['MODERATE'],
                'average_performance' => $assessedRows->isEmpty() ? 0 : round((float) $assessedRows->avg(
                    fn (array $row) => ($row['quiz_average'] + $row['assignment_average']) / 2,
                ), 1),
                'average_attendance' => $assessedRows->isEmpty() ? 0 : round((float) $assessedRows->avg('attendance'), 1),
            ],
            'subjects' => $subjects->map(fn ($subject): array => [
                'code' => $subject->code,
                'title' => $subject->title,
            ])->values(),
            'students' => $studentRows,
            'support_chart' => [
                'labels' => array_keys($supportDistribution),
                'values' => array_values($supportDistribution),
            ],
            'performance_chart' => [
                'labels' => $trendSeries->pluck('label')->all(),
                'quiz' => $trendSeries->pluck('quiz')->all(),
                'assignment' => $trendSeries->pluck('assignment')->all(),
            ],
            'weak_topics' => $weakTopics->map(fn (int $count, string $topic): array => [
                'topic' => $topic,
                'count' => $count,
            ])->values(),
            'recent_activity' => $performances->sortByDesc('snapshot_date')->take(5)->map(
                fn (StudentPerformance $performance): array => [
                    'student' => $performance->student->user->full_name,
                    'subject' => $performance->subject->code,
                    'date' => $performance->snapshot_date->format('M d, Y'),
                    'quiz_average' => (float) $performance->quiz_average,
                ],
            )->values(),
        ];
    }

    /** @return array<string, mixed> */
    public function student(User $user): array
    {
        $student = $user->student()->with(['user', 'subjects'])->first();

        if (! $student) {
            return $this->emptyStudentDashboard();
        }

        $performances = StudentPerformance::query()
            ->with(['subject', 'supportAnalysis'])
            ->where('student_id', $student->id)
            ->orderBy('snapshot_date')
            ->get();

        $latestBySubject = $performances
            ->sortByDesc(fn (StudentPerformance $performance) => $performance->snapshot_date->getTimestamp())
            ->unique('subject_id');

        $priorityAnalysis = $latestBySubject
            ->pluck('supportAnalysis')
            ->filter()
            ->sortByDesc(fn ($analysis) => match ($analysis->support_level->value) {
                'HIGH' => 3,
                'MODERATE' => 2,
                default => 1,
            })->first();

        $latestPerformance = $performances->sortByDesc('snapshot_date')->first();
        $overallScore = $latestBySubject->isEmpty() ? 0 : round((float) $latestBySubject->avg(
            fn (StudentPerformance $performance) => $performance->overall_grade,
        ), 1);

        $studyPlan = $this->studyPlan($student->id);
        $recentQuizzes = $this->recentQuizzes($student->id);

        return [
            'student' => [
                'name' => $user->full_name,
                'student_number' => $student->student_number,
                'program' => $student->program,
            ],
            'status' => [
                'overall_score' => $overallScore,
                'label' => $this->academicStatus($overallScore),
                'support_level' => $priorityAnalysis?->support_level?->value ?? 'UNASSESSED',
                'summary' => $priorityAnalysis?->ai_summary ?? 'Complete an assessment to unlock personalized academic insights.',
                'weak_topics' => $latestBySubject
                    ->pluck('supportAnalysis')
                    ->filter()
                    ->flatMap(fn ($analysis) => $analysis->weak_topics ?? [])
                    ->unique()
                    ->values(),
            ],
            'current' => [
                'attendance' => $latestPerformance ? (float) $latestPerformance->attendance_rate : 0,
                'quiz_average' => $latestPerformance ? (float) $latestPerformance->quiz_average : 0,
                'assignment_average' => $latestPerformance ? (float) $latestPerformance->assignment_average : 0,
                'overall_grade' => $latestPerformance?->overall_grade ?? 0,
                'trend' => $latestPerformance ? (float) $latestPerformance->performance_trend : 0,
            ],
            'subjects' => $student->subjects->map(fn ($subject): array => [
                'code' => $subject->code,
                'title' => $subject->title,
            ])->values(),
            'progress_chart' => [
                'labels' => $performances->map(
                    fn (StudentPerformance $performance) => $performance->subject->code.' · '.$performance->snapshot_date->format('M d'),
                )->all(),
                'quiz' => $performances->map(fn (StudentPerformance $performance) => (float) $performance->quiz_average)->all(),
                'assignment' => $performances->map(fn (StudentPerformance $performance) => (float) $performance->assignment_average)->all(),
            ],
            'study_plan' => $studyPlan,
            'recent_quizzes' => $recentQuizzes,
        ];
    }

    /** @return array{title: string|null, items: Collection<int, object>} */
    private function studyPlan(int $studentId): array
    {
        if (! Schema::hasTable('study_plans') || ! Schema::hasTable('study_plan_items')) {
            return ['title' => null, 'items' => collect()];
        }

        $plan = DB::table('study_plans')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->first();

        if (! $plan) {
            return ['title' => null, 'items' => collect()];
        }

        return [
            'title' => $plan->title,
            'items' => DB::table('study_plan_items')
                ->where('study_plan_id', $plan->id)
                ->orderBy('day_number')
                ->limit(7)
                ->get(),
        ];
    }

    /** @return Collection<int, object> */
    private function recentQuizzes(int $studentId): Collection
    {
        $legacyResults = Schema::hasTable('quiz_results')
            ? DB::table('quiz_results')
                ->join('subjects', 'subjects.id', '=', 'quiz_results.subject_id')
                ->where('quiz_results.student_id', $studentId)
                ->get([
                    'quiz_results.topic',
                    'quiz_results.score',
                    'quiz_results.max_score',
                    'quiz_results.taken_at',
                    'subjects.code as subject_code',
                ])
            : collect();

        $generatedResults = Schema::hasTable('quiz_attempts') && Schema::hasTable('generated_quizzes')
            ? DB::table('quiz_attempts')
                ->join('generated_quizzes', 'generated_quizzes.id', '=', 'quiz_attempts.quiz_id')
                ->join('subjects', 'subjects.id', '=', 'generated_quizzes.subject_id')
                ->where('quiz_attempts.student_id', $studentId)
                ->whereNotNull('quiz_attempts.completed_at')
                ->get([
                    'generated_quizzes.title as topic',
                    'quiz_attempts.score',
                    'quiz_attempts.max_score',
                    'quiz_attempts.completed_at as taken_at',
                    'subjects.code as subject_code',
                ])
            : collect();

        return $legacyResults->concat($generatedResults)
            ->sortByDesc('taken_at')
            ->take(5)
            ->values();
    }

    /** @return array<string, mixed> */
    private function emptyTeacherDashboard(): array
    {
        return [
            'metrics' => [
                'total_students' => 0,
                'support_needed' => 0,
                'average_performance' => 0,
                'average_attendance' => 0,
            ],
            'subjects' => collect(),
            'students' => collect(),
            'support_chart' => ['labels' => ['LOW', 'MODERATE', 'HIGH', 'UNASSESSED'], 'values' => [0, 0, 0, 0]],
            'performance_chart' => ['labels' => [], 'quiz' => [], 'assignment' => []],
            'weak_topics' => collect(),
            'recent_activity' => collect(),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyStudentDashboard(): array
    {
        return [
            'student' => ['name' => '', 'student_number' => 'Not linked', 'program' => null],
            'status' => [
                'overall_score' => 0,
                'label' => 'Awaiting data',
                'support_level' => 'UNASSESSED',
                'summary' => 'Your student profile has not been linked yet.',
                'weak_topics' => collect(),
            ],
            'current' => ['attendance' => 0, 'quiz_average' => 0, 'assignment_average' => 0, 'overall_grade' => 0, 'trend' => 0],
            'subjects' => collect(),
            'progress_chart' => ['labels' => [], 'quiz' => [], 'assignment' => []],
            'study_plan' => ['title' => null, 'items' => collect()],
            'recent_quizzes' => collect(),
        ];
    }

    private function trendLabel(float $trend): string
    {
        return match (true) {
            $trend >= 2 => 'Improving',
            $trend <= -2 => 'Declining',
            default => 'Stable',
        };
    }

    private function academicStatus(float $score): string
    {
        return match (true) {
            $score >= 85 => 'Strong progress',
            $score >= 75 => 'On track',
            $score > 0 => 'Needs focus',
            default => 'Awaiting data',
        };
    }
}
