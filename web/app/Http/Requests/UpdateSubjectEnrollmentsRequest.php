<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectEnrollmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => [
                'integer',
                'distinct',
                'exists:students,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Student::query()->whereKey($value)->whereHas('user', fn ($query) => $query->where('is_active', true))->exists()) {
                        $fail('Only students with active accounts may be enrolled.');
                    }
                },
            ],
        ];
    }
}
