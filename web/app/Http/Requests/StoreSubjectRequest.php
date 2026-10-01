<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'teacher_id' => [
                'required',
                'integer',
                'exists:teachers,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Teacher::query()->whereKey($value)->whereHas('user', fn ($query) => $query->where('is_active', true))->exists()) {
                        $fail('The selected teacher account must be active.');
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('subjects')->where(fn ($query) => $query
                    ->where('school_year', $this->input('school_year'))
                    ->where('term', $this->input('term'))),
            ],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'school_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
