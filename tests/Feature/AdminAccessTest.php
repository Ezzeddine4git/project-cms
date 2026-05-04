<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_open_filament_dashboard(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@camping-vibes.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }
}
