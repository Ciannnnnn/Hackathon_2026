<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_student_account_and_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'New',
            'last_name' => 'Student',
            'email' => 'new.student@example.test',
            'role' => 'student',
            'password' => 'Temporary2026',
            'password_confirmation' => 'Temporary2026',
            'is_active' => '1',
            'student_number' => 'S-ADMIN-001',
            'grade_level' => '2nd Year',
            'program' => 'BS Information Technology',
            'guardian_email' => 'guardian@example.test',
        ]);

        $user = User::query()->where('email', 'new.student@example.test')->firstOrFail();

        $response->assertRedirect(route('admin.users.index'))->assertSessionHas('status');
        $this->assertTrue(Hash::check('Temporary2026', $user->password_hash));
        $this->assertSame('S-ADMIN-001', $user->student->student_number);
        $this->assertSame('2nd Year', $user->student->grade_level);
        $this->assertTrue($user->is_active);
    }

    public function test_administrator_can_create_a_teacher_account_and_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'New',
            'last_name' => 'Teacher',
            'email' => 'new.teacher@example.test',
            'role' => 'teacher',
            'password' => 'Temporary2026',
            'password_confirmation' => 'Temporary2026',
            'is_active' => '1',
            'employee_number' => 'T-ADMIN-001',
            'department' => 'Information Technology',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'new.teacher@example.test')->firstOrFail();

        $this->assertSame('T-ADMIN-001', $user->teacher->employee_number);
        $this->assertSame('Information Technology', $user->teacher->department);
    }

    public function test_non_administrators_cannot_manage_accounts(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_disable_another_account_but_not_their_own(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $student))
            ->assertSessionHas('status');

        $this->assertFalse($student->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $admin))
            ->assertSessionHasErrors('status');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_account_creation_validates_role_profiles_and_password_strength(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Incomplete',
            'last_name' => 'Student',
            'email' => 'invalid@example.test',
            'role' => 'student',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'is_active' => '1',
        ])->assertSessionHasErrors(['password', 'student_number', 'grade_level']);

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
    }
}
