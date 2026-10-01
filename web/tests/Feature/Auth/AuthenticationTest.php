<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to your account');
    }

    #[DataProvider('roleRedirectProvider')]
    public function test_active_users_are_sent_to_their_role_dashboard(string $role, string $route): void
    {
        $user = User::factory()->create([
            'email' => "{$role}@example.com",
            'password_hash' => Hash::make('EduPulse123!'),
            'role' => $role,
        ]);

        $this->post('/login', [
            'email' => strtoupper($user->email),
            'password' => 'EduPulse123!',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertRedirect(route($route));
        $this->get(route($route))->assertOk();
    }

    /** @return array<string, array{string, string}> */
    public static function roleRedirectProvider(): array
    {
        return [
            'student' => ['student', 'student.dashboard'],
            'teacher' => ['teacher', 'teacher.dashboard'],
            'admin' => ['admin', 'admin.dashboard'],
        ];
    }

    public function test_invalid_credentials_are_rejected_without_authenticating(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('correct-password'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('EduPulse123!'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'EduPulse123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'limited@example.com',
            'password_hash' => Hash::make('correct-password'),
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email'),
        );

        $this->assertGuest();
    }

    public function test_authenticated_users_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
