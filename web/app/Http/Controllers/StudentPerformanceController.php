<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentPerformanceRequest;
use App\Models\Student;
use App\Models\StudentPerformance;
use App\Services\AcademicSupportAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StudentPerformanceController extends Controller
{
    public function store(
        StoreStudentPerformanceRequest $request,
        Student $student,
        AcademicSupportAssessmentService $assessment,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $student, $assessment, $request): void {
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
                    'late_submissions' => $validated['late_submissions'],
                    'missing_submissions' => $validated['missing_submissions'],
                    'activity_score' => $validated['activity_score'],
                    'performance_trend' => $validated['performance_trend'],
                ],
            );

            $assessment->assess($performance, $request->weakTopics());
        });

        return redirect()
            ->route('teacher.students.show', ['student' => $student, 'subject' => $validated['subject_id']])
            ->with('status', 'Performance snapshot saved and the provisional support level was refreshed.');
    }
}
