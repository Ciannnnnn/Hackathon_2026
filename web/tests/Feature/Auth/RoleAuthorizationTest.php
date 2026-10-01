<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_protected_routes(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/teacher/dashboard')->assertRedirect(route('login'));
        $this->get('/student/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_users_cannot_access_another_roles_dashboard(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher);

        $this->get('/teacher/dashboard')->assertOk();
        $this->get('/student/dashboard')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_an_account_deactivated_after_login_cannot_access_its_role_dashboard(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => false,
        ]);

        $this->actingAs($teacher)
            ->get('/teacher/dashboard')
            ->assertForbidden();
    }

    public function test_password_hash_is_never_serialized(): void
    {
        $user = User::factory()->create();
        $serialized = $user->toArray();

        $this->assertArrayNotHasKey('password_hash', $serialized);
        $this->assertArrayHasKey('full_name', $serialized);
    }
}
