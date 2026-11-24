<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_mahasiswa_tidak_dapat_membuka_dashboard_dan_admin(): void
    {
        $user = User::factory()->role('mahasiswa')->create();
        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('simulation.index'))->assertForbidden();
    }

    public function test_pengelola_tidak_dapat_membuka_admin_dan_survei_mahasiswa(): void
    {
        $user = User::factory()->role('pengelola')->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.surveys.index'))->assertForbidden();
        $this->actingAs($user)->get(route('survey.index'))->assertForbidden();
    }

    public function test_admin_dapat_membuka_admin_dan_dashboard(): void
    {
        $user = User::factory()->role('admin')->create();
        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.surveys.index'))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_akun_yang_dinonaktifkan_saat_login_dikeluarkan(): void
    {
        $user = User::factory()->role('pengelola')->create();
        $this->actingAs($user);
        $user->update(['is_active' => false]);
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
