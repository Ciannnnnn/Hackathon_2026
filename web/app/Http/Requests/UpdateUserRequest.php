<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var User $account */
        $account = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($account->id)],
            'password' => ['nullable', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'is_active' => ['required', 'boolean'],
            'student_number' => [
                Rule::requiredIf($account->role === UserRole::Student),
                'nullable',
                'string',
                'max:40',
                Rule::unique('students', 'student_number')->ignore($account->student?->id),
            ],
            'grade_level' => [Rule::requiredIf($account->role === UserRole::Student), 'nullable', 'string', 'max:40'],
            'program' => ['nullable', 'string', 'max:120'],
            'guardian_email' => ['nullable', 'email:rfc', 'max:191'],
            'employee_number' => [
                Rule::requiredIf($account->role === UserRole::Teacher),
                'nullable',
                'string',
                'max:40',
                Rule::unique('teachers', 'employee_number')->ignore($account->teacher?->id),
            ],
            'department' => ['nullable', 'string', 'max:120'],
        ];
    }
}
