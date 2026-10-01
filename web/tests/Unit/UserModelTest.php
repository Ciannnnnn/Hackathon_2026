<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_it_matches_the_edupulse_authentication_schema(): void
    {
        $user = new User([
            'first_name' => 'Alex',
            'last_name' => 'Santos',
            'email' => 'alex@example.com',
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->assertSame('password_hash', $user->getAuthPasswordName());
        $this->assertSame('Alex Santos', $user->full_name);
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertContains('password_hash', $user->getHidden());
    }
}
