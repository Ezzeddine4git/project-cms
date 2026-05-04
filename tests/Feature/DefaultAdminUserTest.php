<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DefaultAdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_user_is_created_when_missing(): void
    {
        $this->assertDatabaseMissing('users', [
            'email' => DefaultAdminUser::EMAIL,
        ]);

        $user = DefaultAdminUser::ensureExists();

        $this->assertTrue($user->is_admin);
        $this->assertDatabaseHas('users', [
            'email' => DefaultAdminUser::EMAIL,
            'is_admin' => true,
        ]);
    }

    public function test_existing_default_admin_keeps_password_and_becomes_admin(): void
    {
        $user = User::factory()->create([
            'email' => DefaultAdminUser::EMAIL,
            'password' => 'custom-password',
            'is_admin' => false,
        ]);

        $originalPassword = $user->password;

        $user = DefaultAdminUser::ensureExists();

        $this->assertTrue($user->is_admin);
        $this->assertSame($originalPassword, $user->password);
    }
}
