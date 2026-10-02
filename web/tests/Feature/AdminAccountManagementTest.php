<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
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

    public function test_administrator_can_open_the_account_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        Student::query()->create([
            'user_id' => $student->id,
            'student_number' => 'S-EDITOR-001',
            'grade_level' => '1st Year',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Edit')
            ->assertSee('Save changes')
            ->assertSee(route('admin.users.update', $student), escape: false);
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

    public function test_administrator_can_edit_student_details_and_reset_their_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentUser = User::factory()->create([
            'role' => 'student',
            'password_hash' => Hash::make('OriginalPassword2026'),
        ]);
        Student::query()->create([
            'user_id' => $studentUser->id,
            'student_number' => 'S-OLD-001',
            'grade_level' => '1st Year',
        ]);

        $this->actingAs($admin)->patch(route('admin.users.update', $studentUser), [
            'first_name' => 'Updated',
            'last_name' => 'Learner',
            'email' => 'updated.student@example.test',
            'password' => 'NewSecurePassword2026',
            'password_confirmation' => 'NewSecurePassword2026',
            'is_active' => '1',
            'student_number' => 'S-NEW-001',
            'grade_level' => '2nd Year',
            'program' => 'BS Computer Science',
            'guardian_email' => 'guardian@example.test',
        ])->assertSessionHas('status');

        $studentUser->refresh()->load('student');

        $this->assertSame('Updated', $studentUser->first_name);
        $this->assertSame('updated.student@example.test', $studentUser->email);
        $this->assertSame('S-NEW-001', $studentUser->student->student_number);
        $this->assertSame('2nd Year', $studentUser->student->grade_level);
        $this->assertTrue(Hash::check('NewSecurePassword2026', $studentUser->password_hash));
    }

    public function test_leaving_password_blank_keeps_the_existing_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacherUser = User::factory()->create([
            'role' => 'teacher',
            'password_hash' => Hash::make('ExistingPassword2026'),
        ]);
        Teacher::query()->create([
            'user_id' => $teacherUser->id,
            'employee_number' => 'T-OLD-001',
            'department' => 'Old Department',
        ]);
        $oldPasswordHash = $teacherUser->password_hash;

        $this->actingAs($admin)->patch(route('admin.users.update', $teacherUser), [
            'first_name' => $teacherUser->first_name,
            'last_name' => $teacherUser->last_name,
            'email' => $teacherUser->email,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => '1',
            'employee_number' => 'T-NEW-001',
            'department' => 'Information Technology',
        ])->assertSessionHas('status');

        $teacherUser->refresh()->load('teacher');

        $this->assertSame($oldPasswordHash, $teacherUser->password_hash);
        $this->assertSame('T-NEW-001', $teacherUser->teacher->employee_number);
        $this->assertSame('Information Technology', $teacherUser->teacher->department);
    }

    public function test_administrator_cannot_deactivate_their_own_account_while_editing_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'email' => $admin->email,
            'is_active' => '0',
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_non_administrator_cannot_update_an_account(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($teacher)->patch(route('admin.users.update', $student), [
            'first_name' => 'Blocked',
            'last_name' => 'Update',
            'email' => 'blocked@example.test',
            'is_active' => '1',
            'student_number' => 'BLOCKED-1',
            'grade_level' => '1st Year',
        ])->assertForbidden();

        $this->assertNotSame('blocked@example.test', $student->fresh()->email);
    }
}
