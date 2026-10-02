<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountProvisioningService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'role' => $data['role'],
                'is_active' => $data['is_active'],
            ]);

            if ($data['role'] === 'student') {
                Student::create([
                    'user_id' => $user->id,
                    'student_number' => $data['student_number'],
                    'grade_level' => $data['grade_level'],
                    'program' => $data['program'] ?? null,
                    'guardian_email' => $data['guardian_email'] ?? null,
                ]);
            }

            if ($data['role'] === 'teacher') {
                Teacher::create([
                    'user_id' => $user->id,
                    'employee_number' => $data['employee_number'],
                    'department' => $data['department'] ?? null,
                ]);
            }

            return $user;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $oldEmail = $user->email;
            $attributes = [
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'is_active' => $data['is_active'],
            ];

            if (filled($data['password'] ?? null)) {
                $attributes['password_hash'] = Hash::make($data['password']);
            }

            $user->update($attributes);

            if ($user->role->value === 'student') {
                $user->student()->updateOrCreate([], [
                    'student_number' => $data['student_number'],
                    'grade_level' => $data['grade_level'],
                    'program' => $data['program'] ?? null,
                    'guardian_email' => $data['guardian_email'] ?? null,
                ]);
            }

            if ($user->role->value === 'teacher') {
                $user->teacher()->updateOrCreate([], [
                    'employee_number' => $data['employee_number'],
                    'department' => $data['department'] ?? null,
                ]);
            }

            if ($oldEmail !== $user->email) {
                DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();
            }

            return $user->refresh();
        });
    }
}
