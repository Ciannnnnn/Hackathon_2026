<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitQuizAttemptRequest;
use App\Models\GeneratedQuiz;
use App\Models\QuizAttempt;
use App\Services\QuizGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentQuizController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;
        $subjects = $student->subjects()->where('subjects.is_active', true)->wherePivot('status', 'active')->orderBy('code')->get();
        $selectedSubject = $subjects->firstWhere('id', (int) $request->query('subject')) ?? $subjects->first();
        $quizzes = $selectedSubject ? GeneratedQuiz::query()
            ->with(['module', 'attempts' => fn ($query) => $query->where('student_id', $student->id)->latest('completed_at')])
            ->where('subject_id', $selectedSubject->id)->where('is_published', true)->latest()->get() : collect();

        return view('student.quizzes.index', compact('subjects', 'selectedSubject', 'quizzes'));
    }

    public function show(Request $request, GeneratedQuiz $quiz): View
    {
        $this->authorizeAccess($request->user()->student->id, $quiz);
        $quiz->load(['subject', 'module', 'questions']);

        return view('student.quizzes.show', compact('quiz'));
    }

    public function submit(SubmitQuizAttemptRequest $request, GeneratedQuiz $quiz, QuizGradingService $grading): RedirectResponse
    {
        $attempt = $grading->grade($request->user()->student, $quiz, $request->validated('answers'));

        return redirect()->route('student.quizzes.results', $attempt)->with('status', 'Quiz submitted and graded.');
    }

    public function results(Request $request, QuizAttempt $attempt): View
    {
        abort_unless($attempt->student_id === $request->user()->student?->id, 404);
        $attempt->load(['quiz.subject', 'quiz.module', 'answers.question.sourceChunk.module']);

        return view('student.quizzes.results', compact('attempt'));
    }

    private function authorizeAccess(int $studentId, GeneratedQuiz $quiz): void
    {
        abort_unless($quiz->is_published && $quiz->subject->students()->whereKey($studentId)->wherePivot('status', 'active')->exists(), 404);
    }
}
