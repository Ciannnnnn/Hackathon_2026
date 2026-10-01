<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskTutorQuestionRequest;
use App\Models\ChatConversation;
use App\Models\Subject;
use App\Services\TutorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentTutorController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;
        $subjects = $student?->subjects()
            ->where('subjects.is_active', true)
            ->wherePivot('status', 'active')
            ->orderBy('code')
            ->get() ?? collect();
        $selectedSubject = $subjects->firstWhere('id', (int) $request->query('subject')) ?? $subjects->first();
        $conversations = collect();
        $conversation = null;
        $readyModuleCount = 0;

        if ($selectedSubject) {
            $conversations = ChatConversation::query()
                ->where('student_id', $student->id)
                ->where('subject_id', $selectedSubject->id)
                ->orderByDesc('updated_at')
                ->get();
            $readyModuleCount = $selectedSubject->modules()->where('processing_status', 'ready')->count();

            if ($request->filled('conversation')) {
                $conversation = ChatConversation::query()
                    ->with('messages')
                    ->whereKey((int) $request->query('conversation'))
                    ->where('student_id', $student->id)
                    ->where('subject_id', $selectedSubject->id)
                    ->firstOrFail();
            }
        }

        return view('student.tutor.index', compact(
            'subjects',
            'selectedSubject',
            'conversations',
            'conversation',
            'readyModuleCount',
        ));
    }

    public function store(AskTutorQuestionRequest $request, TutorService $tutor): RedirectResponse
    {
        $validated = $request->validated();
        $student = $request->user()->student;
        $subject = Subject::query()->findOrFail($validated['subject_id']);
        $conversation = isset($validated['conversation_id'])
            ? ChatConversation::query()->findOrFail($validated['conversation_id'])
            : null;

        $answer = $tutor->ask($student, $subject, $validated['question'], $conversation);

        return redirect()
            ->route('student.tutor.index', [
                'subject' => $subject->id,
                'conversation' => $answer['conversation']->id,
            ])
            ->with('status', match (true) {
                $answer['result']->fallback && $answer['sources'] !== [] => 'The AI provider was unavailable; relevant teacher sources were saved for review.',
                $answer['result']->fallback => 'The AI provider was unavailable, so fallback guidance was saved without source claims.',
                $answer['sources'] !== [] => 'Tutor answer generated from teacher-uploaded learning material.',
                default => 'No matching module passage was found; the answer is labeled as general guidance.',
            });
    }
}
