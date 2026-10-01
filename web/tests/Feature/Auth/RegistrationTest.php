<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_student_registration(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Create an account')
            ->assertSee(route('register'), escape: false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your student account')
            ->assertSee('Teacher and administrator accounts are issued');
    }

    public function test_student_can_create_an_account_and_is_signed_in(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Jamie',
            'last_name' => 'Rivera',
            'email' => 'jamie.rivera@example.test',
            'student_number' => 'S-REGISTER-001',
            'grade_level' => '2nd Year',
            'program' => 'BS Information Technology',
            'guardian_email' => 'guardian@example.test',
            'password' => 'SecureStudent2026',
            'password_confirmation' => 'SecureStudent2026',
            'role' => 'admin',
        ]);

        $user = User::query()->where('email', 'jamie.rivera@example.test')->firstOrFail();

        $response
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('status');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertSame('S-REGISTER-001', $user->student->student_number);
        $this->assertTrue(Hash::check('SecureStudent2026', $user->password_hash));
    }

    public function test_registration_rejects_weak_or_duplicate_account_details(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);

        $this->post(route('register.store'), [
            'first_name' => 'Jamie',
            'last_name' => 'Rivera',
            'email' => 'existing@example.test',
            'student_number' => '',
            'grade_level' => '',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors(['email', 'student_number', 'grade_level', 'password']);

        $this->assertGuest();
    }
}
