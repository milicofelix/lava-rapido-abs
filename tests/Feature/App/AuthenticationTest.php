<?php

namespace Tests\Feature\App;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_access_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'admin@lavaabs.test']);

        $this->post('/login', [
            'email' => 'admin@lavaabs.test',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('data-app-shell', false)
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('autoflow.sidebar.collapsed', false);
    }

    public function test_login_keeps_institutional_autoflow_logo(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('images/autoflow-logo.png', false);
    }
}
