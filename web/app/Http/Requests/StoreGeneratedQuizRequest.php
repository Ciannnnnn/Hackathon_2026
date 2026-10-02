<?php

namespace App\Http\Requests;

use App\Models\LearningModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneratedQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacherId = $this->user()?->teacher()->value('id');

        return $teacherId && LearningModule::query()
            ->whereKey((int) $this->input('module_id'))
            ->where('subject_id', (int) $this->input('subject_id'))
            ->where('teacher_id', $teacherId)
            ->where('processing_status', 'ready')
            ->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:180'],
            'topic' => ['required', 'string', 'max:160'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'question_count' => ['required', 'integer', 'between:3,10'],
        ];
    }
}
