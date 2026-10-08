<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_view_the_dashboard_shell(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('iSafe / Ruang kerja')
            ->assertSee('Transaksi terbaru')
            ->assertSee('Omzet 12 bulan terakhir');
    }

    public function test_admin_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_and_email_password_recovery_are_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password')->assertNotFound();
        $this->get('/reset-password/sample')->assertNotFound();
    }

    public function test_admin_seeder_creates_only_the_configured_admin_once(): void
    {
        config([
            'admin.name' => 'Pemilik Bangaldi',
            'admin.email' => 'owner@example.com',
            'admin.password' => 'a-secure-password',
        ]);

        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'name' => 'Pemilik Bangaldi',
            'email' => 'owner@example.com',
        ]);
    }
}
