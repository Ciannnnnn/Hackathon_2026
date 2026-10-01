<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherStudentService
{
    /**
     * @param  array{search?: string|null, subject?: int|null, support?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function roster(User $user, array $filters): array
    {
        $teacher = $user->teacher()->first();

        if (! $teacher) {
            return $this->emptyRoster($filters);
        }

        $subjects = $teacher->subjects()->where('is_active', true)->orderBy('code')->get();
        $subjectIds = $subjects->pluck('id');
        $selectedSubject = isset($filters['subject']) && $subjectIds->contains((int) $filters['subject'])
            ? (int) $filters['subject']
            : null;

        $query = Student::query()
            ->with('user')
            ->whereHas('enrollments', fn ($query) => $query
                ->whereIn('subject_id', $subjectIds)
                ->where('status', 'active'));

        if ($selectedSubject) {
            $query->whereHas('enrollments', fn ($query) => $query
                ->where('subject_id', $selectedSubject)
                ->where('status', 'active'));
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('student_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($query) => $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $students = $subjectIds->isEmpty() ? collect() : $query->orderBy('student_number')->get();
        $studentIds = $students->pluck('id');
        $performances = $studentIds->isEmpty()
            ? collect()
            : StudentPerformance::query()
                ->with(['subject', 'supportAnalysis'])
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $selectedSubject ? [$selectedSubject] : $subjectIds)
                ->orderByDesc('snapshot_date')
                ->orderByDesc('id')
                ->get();

        $latestByStudent = $performances->unique('student_id')->keyBy('student_id');
        $rows = $students->map(function (Student $student) use ($latestByStudent): array {
            $performance = $latestByStudent->get($student->id);
            $level = $performance?->supportAnalysis?->support_level?->value ?? 'UNASSESSED';
            $trend = (float) ($performance?->performance_trend ?? 0);

            return [
                'id' => $student->id,
                'name' => $student->user->full_name,
                'email' => $student->user->email,
                'student_number' => $student->student_number,
                'grade_level' => $student->grade_level,
                'program' => $student->program,
                'subject_code' => $performance?->subject?->code,
                'snapshot_date' => $performance?->snapshot_date,
                'attendance' => $performance ? (float) $performance->attendance_rate : null,
                'quiz_average' => $performance ? (float) $performance->quiz_average : null,
                'assignment_average' => $performance ? (float) $performance->assignment_average : null,
                'activity_score' => $performance ? (float) $performance->activity_score : null,
                'missing_submissions' => $performance?->missing_submissions ?? 0,
                'trend' => $trend,
                'trend_label' => $this->trendLabel($trend),
                'support_level' => $level,
            ];
        });

        $support = strtoupper((string) ($filters['support'] ?? ''));

        if (in_array($support, ['LOW', 'MODERATE', 'HIGH', 'UNASSESSED'], true)) {
            $rows = $rows->where('support_level', $support);
        }

        $rows = $rows->sortBy(fn (array $row): string => sprintf(
            '%d-%s',
            match ($row['support_level']) {
                'HIGH' => 0,
                'MODERATE' => 1,
                'LOW' => 2,
                default => 3,
            },
            $row['name'],
        ))->values();

        return [
            'students' => $rows,
            'subjects' => $subjects,
            'filters' => [
                'search' => $search,
                'subject' => $selectedSubject,
                'support' => in_array($support, ['LOW', 'MODERATE', 'HIGH', 'UNASSESSED'], true) ? $support : null,
            ],
            'summary' => [
                'visible' => $rows->count(),
                'high' => $rows->where('support_level', 'HIGH')->count(),
                'moderate' => $rows->where('support_level', 'MODERATE')->count(),
                'unassessed' => $rows->where('support_level', 'UNASSESSED')->count(),
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public function analysis(User $user, Student $student, ?int $requestedSubjectId = null): ?array
    {
        $teacher = $user->teacher()->first();

        if (! $teacher) {
            return null;
        }

        $subjects = $teacher->subjects()
            ->whereHas('enrollments', fn ($query) => $query
                ->where('student_id', $student->id)
                ->where('status', 'active'))
            ->orderBy('code')
            ->get();

        if ($subjects->isEmpty()) {
            return null;
        }

        $subject = $requestedSubjectId
            ? $subjects->firstWhere('id', $requestedSubjectId)
            : $subjects->first();

        if (! $subject) {
            return null;
        }

        $performances = StudentPerformance::query()
            ->with('supportAnalysis')
            ->where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->orderBy('snapshot_date')
            ->orderBy('id')
            ->get();
        $latest = $performances->last();
        $analysis = $latest?->supportAnalysis;

        return [
            'student' => $student->loadMissing('user'),
            'subjects' => $subjects,
            'selected_subject' => $subject,
            'latest' => $latest,
            'analysis' => $analysis,
            'records' => $performances->sortByDesc('snapshot_date')->values(),
            'performance_chart' => [
                'labels' => $performances->map(fn (StudentPerformance $item) => $item->snapshot_date->format('M d, Y'))->all(),
                'attendance' => $performances->map(fn (StudentPerformance $item) => (float) $item->attendance_rate)->all(),
                'quiz' => $performances->map(fn (StudentPerformance $item) => (float) $item->quiz_average)->all(),
                'assignment' => $performances->map(fn (StudentPerformance $item) => (float) $item->assignment_average)->all(),
            ],
            'recommendations' => $this->recommendations($analysis?->id),
            'study_plan' => $this->studyPlan($student->id, $subject->id),
        ];
    }

    public function canRecord(User $user, Student $student, int $subjectId): bool
    {
        $teacherId = $user->teacher()->value('id');

        if (! $teacherId) {
            return false;
        }

        return DB::table('enrollments')
            ->join('subjects', 'subjects.id', '=', 'enrollments.subject_id')
            ->where('enrollments.student_id', $student->id)
            ->where('enrollments.subject_id', $subjectId)
            ->where('enrollments.status', 'active')
            ->where('subjects.teacher_id', $teacherId)
            ->exists();
    }

    /** @return Collection<int, object> */
    private function recommendations(?int $analysisId): Collection
    {
        if (! $analysisId || ! Schema::hasTable('recommendations')) {
            return collect();
        }

        return DB::table('recommendations')
            ->where('support_analysis_id', $analysisId)
            ->whereNotIn('status', ['dismissed'])
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('id')
            ->get();
    }

    /** @return array{plan: object|null, items: Collection<int, object>} */
    private function studyPlan(int $studentId, int $subjectId): array
    {
        if (! Schema::hasTable('study_plans') || ! Schema::hasTable('study_plan_items')) {
            return ['plan' => null, 'items' => collect()];
        }

        $plan = DB::table('study_plans')
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->whereIn('status', ['active', 'draft'])
            ->orderByDesc('start_date')
            ->first();

        return [
            'plan' => $plan,
            'items' => $plan
                ? DB::table('study_plan_items')->where('study_plan_id', $plan->id)->orderBy('day_number')->get()
                : collect(),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyRoster(array $filters): array
    {
        return [
            'students' => collect(),
            'subjects' => collect(),
            'filters' => $filters,
            'summary' => ['visible' => 0, 'high' => 0, 'moderate' => 0, 'unassessed' => 0],
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
}
