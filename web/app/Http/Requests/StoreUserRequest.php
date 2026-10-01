<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:191', 'unique:users,email'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'is_active' => ['required', 'boolean'],
            'student_number' => ['exclude_unless:role,student', 'required', 'string', 'max:40', 'unique:students,student_number'],
            'grade_level' => ['exclude_unless:role,student', 'required', 'string', 'max:40'],
            'program' => ['exclude_unless:role,student', 'nullable', 'string', 'max:120'],
            'guardian_email' => ['exclude_unless:role,student', 'nullable', 'email:rfc', 'max:191'],
            'employee_number' => ['exclude_unless:role,teacher', 'required', 'string', 'max:40', 'unique:teachers,employee_number'],
            'department' => ['exclude_unless:role,teacher', 'nullable', 'string', 'max:120'],
        ];
    }
}
