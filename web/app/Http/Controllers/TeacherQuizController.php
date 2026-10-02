<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGeneratedQuizRequest;
use App\Http\Requests\UpdateGeneratedQuizQuestionRequest;
use App\Jobs\GenerateGroundedQuiz;
use App\Models\GeneratedQuiz;
use App\Models\GeneratedQuizQuestion;
use App\Models\LearningModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

    public function store(StoreGeneratedQuizRequest $request): RedirectResponse
    {
        $data = [...$request->validated(), 'is_published' => false];
        $module = LearningModule::findOrFail($data['module_id']);

        GenerateGroundedQuiz::dispatch($request->user(), $module, $data);

        return redirect()->route('teacher.quizzes.index', ['subject' => $module->subject_id])
            ->with('status', "Creating {$data['title']} now. This page will update automatically when the draft is ready.")
            ->with('pending_quiz_title', $data['title']);
    }

    public function togglePublish(Request $request, GeneratedQuiz $quiz): RedirectResponse
    {
        $this->authorizeOwnership($request, $quiz);
        $quiz->update(['is_published' => ! $quiz->is_published]);

        return back()->with('status', "{$quiz->title} is now ".($quiz->is_published ? 'published.' : 'a draft.'));
    }

    public function updateQuestion(
        UpdateGeneratedQuizQuestionRequest $request,
        GeneratedQuiz $quiz,
        GeneratedQuizQuestion $question,
    ): RedirectResponse {
        $this->authorizeOwnership($request, $quiz);
        abort_unless($question->quiz_id === $quiz->id, 404);

        if ($quiz->is_published) {
            throw ValidationException::withMessages(['question' => 'Return this quiz to draft before editing its questions.']);
        }

        if ($quiz->attempts()->exists()) {
            throw ValidationException::withMessages(['question' => 'Questions cannot be edited after a student has attempted the quiz.']);
        }

        $data = $request->validated();
        $choices = null;
        $correctAnswer = trim($data['correct_answer']);

        if ($question->question_type === 'multiple_choice') {
            $choices = collect(preg_split('/\R/u', (string) ($data['choices'] ?? '')))
                ->map(fn (string $choice): string => trim($choice))
                ->filter()
                ->unique(fn (string $choice): string => mb_strtolower($choice))
                ->values();

            if ($choices->count() < 2 || $choices->count() > 8) {
                throw ValidationException::withMessages(['choices' => 'Multiple-choice questions require between 2 and 8 unique choices.']);
            }

            $matchingAnswer = $choices->first(fn (string $choice): bool => mb_strtolower($choice) === mb_strtolower($correctAnswer));
            if (! is_string($matchingAnswer)) {
                throw ValidationException::withMessages(['correct_answer' => 'The correct answer must exactly match one of the choices.']);
            }

            $correctAnswer = $matchingAnswer;
            $choices = $choices->all();
        } elseif ($question->question_type === 'true_false') {
            if (! in_array(mb_strtolower($correctAnswer), ['true', 'false'], true)) {
                throw ValidationException::withMessages(['correct_answer' => 'The answer must be True or False.']);
            }

            $correctAnswer = ucfirst(mb_strtolower($correctAnswer));
            $choices = ['True', 'False'];
        }

        $question->update([
            'question_text' => trim($data['question_text']),
            'choices' => $choices,
            'correct_answer' => $correctAnswer,
            'explanation' => trim($data['explanation']),
        ]);

        return back()->with('status', "Question {$question->position} was updated.");
    }

    private function authorizeOwnership(Request $request, GeneratedQuiz $quiz): void
    {
        abort_unless($quiz->created_by_user_id === $request->user()->id, 404);
    }
}
