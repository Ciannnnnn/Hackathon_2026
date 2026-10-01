<?php

namespace App\Http\Requests;

use App\Models\GeneratedQuiz;
use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');
        $student = $this->user()?->student;

        return $quiz instanceof GeneratedQuiz && $quiz->is_published && $student
            && $student->subjects()->whereKey($quiz->subject_id)->wherePivot('status', 'active')->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['answers' => ['required', 'array'], 'answers.*' => ['nullable', 'string', 'max:1000']];
    }
}
