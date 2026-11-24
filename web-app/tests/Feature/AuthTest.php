<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_dengan_email_dan_nim(): void
    {
        $user = User::factory()->role('mahasiswa')->create(['nim' => '105012345']);

        $this->post(route('login.attempt'), ['login' => $user->email, 'password' => 'Password123'])
            ->assertRedirect(route('survey.index'));
        $this->post(route('logout'));

        $this->post(route('login.attempt'), ['login' => '105012345', 'password' => 'Password123'])
            ->assertRedirect(route('survey.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_pengelola_diarahkan_ke_dashboard(): void
    {
        $user = User::factory()->role('pengelola')->create();
        $this->post(route('login.attempt'), ['login' => $user->email, 'password' => 'Password123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_password_salah_ditolak_dengan_pesan_umum(): void
    {
        $user = User::factory()->create();
        $this->post(route('login.attempt'), ['login' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['login' => 'Email/NIM atau password salah.']);
        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_dapat_login(): void
    {
        $user = User::factory()->inactive()->create();
        $this->post(route('login.attempt'), ['login' => $user->email, 'password' => 'Password123'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_dikunci_setelah_5_kali_gagal(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), ['login' => $user->email, 'password' => 'salah']);
        }
        $this->post(route('login.attempt'), ['login' => $user->email, 'password' => 'Password123'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_wajib_ganti_password_awal(): void
    {
        $user = User::factory()->role('mahasiswa')->mustChangePassword()->create();

        $this->actingAs($user)->get(route('survey.index'))->assertRedirect(route('password.edit'));

        $this->actingAs($user)->put(route('password.change'), [
            'current_password' => 'Password123', 'password' => 'BaruSekali1', 'password_confirmation' => 'BaruSekali1',
        ])->assertRedirect(route('survey.index'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->get(route('survey.index'))->assertOk();
    }
}
