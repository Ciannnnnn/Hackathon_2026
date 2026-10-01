<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGeneratedQuizRequest;
use App\Models\GeneratedQuiz;
use App\Models\LearningModule;
use App\Services\QuizGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherQuizController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user()->teacher;
        $subjects = $teacher->subjects()->where('is_active', true)->orderBy('code')->get();
        $selectedSubject = $subjects->firstWhere('id', (int) $request->query('subject')) ?? $subjects->first();
        $modules = $selectedSubject ? $selectedSubject->modules()->where('teacher_id', $teacher->id)->where('processing_status', 'ready')->withCount('chunks')->get() : collect();
        $quizzes = GeneratedQuiz::query()->with(['module', 'questions.sourceChunk'])->withCount('attempts')
            ->where('created_by_user_id', $request->user()->id)
            ->when($selectedSubject, fn ($query) => $query->where('subject_id', $selectedSubject->id))
            ->latest()->get();

        return view('teacher.quizzes.index', compact('subjects', 'selectedSubject', 'modules', 'quizzes'));
    }

    public function store(StoreGeneratedQuizRequest $request, QuizGenerationService $generator): RedirectResponse
    {
        $quiz = $generator->generate($request->user(), LearningModule::findOrFail($request->validated('module_id')), $request->validated());

        return redirect()->route('teacher.quizzes.index', ['subject' => $quiz->subject_id])
            ->with('status', "{$quiz->title} was generated".($quiz->is_published ? ' and published.' : ' as a draft.'));
    }

    public function togglePublish(Request $request, GeneratedQuiz $quiz): RedirectResponse
    {
        $this->authorizeOwnership($request, $quiz);
        $quiz->update(['is_published' => ! $quiz->is_published]);

        return back()->with('status', "{$quiz->title} is now ".($quiz->is_published ? 'published.' : 'a draft.'));
    }

    private function authorizeOwnership(Request $request, GeneratedQuiz $quiz): void
    {
        abort_unless($quiz->created_by_user_id === $request->user()->id, 404);
    }
}
