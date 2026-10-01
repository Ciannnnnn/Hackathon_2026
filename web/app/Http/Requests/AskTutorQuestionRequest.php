<?php

namespace App\Http\Requests;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

class AskTutorQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->user()?->student;
        if (! $student) {
            return false;
        }

        $subjectId = (int) $this->input('subject_id');
        $hasSubject = $student->subjects()
            ->whereKey($subjectId)
            ->where('subjects.is_active', true)
            ->wherePivot('status', 'active')
            ->exists();

        if (! $hasSubject) {
            return false;
        }

        $conversationId = $this->input('conversation_id');

        return blank($conversationId) || ChatConversation::query()
            ->whereKey((int) $conversationId)
            ->where('student_id', $student->id)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
            'question' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }
}
