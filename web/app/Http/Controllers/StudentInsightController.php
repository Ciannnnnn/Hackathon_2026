<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateStudentInsightsRequest;
use App\Models\Student;
use App\Services\StudentInsightGenerationService;
use Illuminate\Http\RedirectResponse;

class StudentInsightController extends Controller
{
    public function store(
        GenerateStudentInsightsRequest $request,
        Student $student,
        StudentInsightGenerationService $insights,
    ): RedirectResponse {
        $subjectId = (int) $request->validated('subject_id');
        $result = $insights->generate($student, $subjectId);
        $fallback = $result['analysis']->fallback || $result['plan']->fallback;
        $message = $fallback
            ? 'AI service fallback used. Demo recommendations and a seven-day study plan were saved.'
            : "Gemini analysis, recommendations, and a seven-day study plan were saved using {$result['analysis']->model}.";

        return redirect()
            ->route('teacher.students.show', ['student' => $student, 'subject' => $subjectId])
            ->with('status', $message);
    }
}
