<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradeRequest;
use App\Models\Student;
use App\Models\StudentPerformance;
use App\Services\AcademicSupportAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeacherGradebookController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user()->teacher;
        $subjects = $teacher?->subjects()->where('is_active', true)->orderBy('code')->get() ?? collect();
        $selectedSubject = $subjects->firstWhere('id', (int) $request->query('subject')) ?? $subjects->first();
        $students = collect();

        if ($selectedSubject) {
            $students = Student::query()
                ->with('user')
                ->whereHas('enrollments', fn ($query) => $query
                    ->where('subject_id', $selectedSubject->id)
                    ->where('status', 'active'))
                ->orderBy('student_number')
                ->get();

            $latestByStudent = StudentPerformance::query()
                ->where('subject_id', $selectedSubject->id)
                ->whereIn('student_id', $students->pluck('id'))
                ->orderByDesc('snapshot_date')
                ->orderByDesc('id')
                ->get()
                ->unique('student_id')
                ->keyBy('student_id');

            $students->each(fn (Student $student) => $student->setRelation(
                'latestPerformance',
                $latestByStudent->get($student->id),
            ));
        }

        return view('teacher.grades.index', compact('subjects', 'selectedSubject', 'students'));
    }

    public function store(
        StoreGradeRequest $request,
        Student $student,
        AcademicSupportAssessmentService $assessment,
    ): RedirectResponse {
        $validated = $request->validated();
        $trend = $this->calculateTrend($student, (int) $validated['subject_id'], $validated);

        DB::transaction(function () use ($validated, $student, $trend, $assessment, $request): void {
            $performance = StudentPerformance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $validated['subject_id'],
                    'snapshot_date' => $validated['snapshot_date'],
                ],
                [
                    'attendance_rate' => $validated['attendance_rate'],
                    'quiz_average' => $validated['quiz_average'],
                    'assignment_average' => $validated['assignment_average'],
                    'activity_score' => $validated['activity_score'],
                    'late_submissions' => $validated['late_submissions'],
                    'missing_submissions' => $validated['missing_submissions'],
                    'performance_trend' => $trend,
                ],
            );

            $assessment->assess($performance, $request->weakTopics());
        });

        return redirect()
            ->route('teacher.grades.index', ['subject' => $validated['subject_id']])
            ->with('status', "{$student->user->full_name}'s grades were saved and their support assessment was refreshed.");
    }

    /** @param array<string, mixed> $grades */
    private function calculateTrend(Student $student, int $subjectId, array $grades): float
    {
        $previous = StudentPerformance::query()
            ->where('student_id', $student->id)
            ->where('subject_id', $subjectId)
            ->whereDate('snapshot_date', '<', $grades['snapshot_date'])
            ->orderByDesc('snapshot_date')
            ->first();

        if (! $previous) {
            return 0;
        }

        $currentGrade = ((float) $grades['quiz_average'] + (float) $grades['assignment_average'] + (float) $grades['activity_score']) / 3;

        return round($currentGrade - $previous->overall_grade, 2);
    }
}
