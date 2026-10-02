<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneratedQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Teacher;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:1000'],
            'choices' => ['nullable', 'string', 'max:4000'],
            'correct_answer' => ['required', 'string', 'max:500'],
            'explanation' => ['required', 'string', 'max:2000'],
        ];
    }
}
