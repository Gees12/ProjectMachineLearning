<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ani Lestari', 'nim' => '105099001', 'email' => 'ani@student.test',
            'password' => 'RahasiaAni1', 'password_confirmation' => 'RahasiaAni1',
        ], $overrides);
    }

    public function test_halaman_daftar_tersedia_dan_ditautkan_dari_login(): void
    {
        $this->get(route('login'))->assertSee(route('register'));
        $this->get(route('register'))->assertOk()->assertSee('NIM');
    }

    public function test_pendaftaran_menunggu_persetujuan_dan_belum_bisa_login(): void
    {
        $this->post(route('register.store'), $this->form())
            ->assertRedirect(route('login'))->assertSessionHas('success');

        $user = User::firstWhere('nim', '105099001');
        $this->assertSame('mahasiswa', $user->role);
        $this->assertTrue($user->self_registered);
        $this->assertTrue($user->isPendingApproval());
        $this->assertFalse($user->must_change_password);
        $this->assertGuest();

        $this->post(route('login.attempt'), ['login' => '105099001', 'password' => 'RahasiaAni1'])
            ->assertSessionHasErrors(['login' => 'Akun Anda masih menunggu persetujuan admin asrama.']);
        $this->assertGuest();
    }

    public function test_admin_menyetujui_lalu_mahasiswa_bisa_login(): void
    {
        $this->post(route('register.store'), $this->form());
        $user = User::firstWhere('nim', '105099001');
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertSee('menunggu persetujuan')->assertSee('Setujui');
        $this->actingAs($admin)->patch(route('admin.users.toggle', $user))->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->approved_at);
        $this->assertFalse($user->isPendingApproval());

        $this->post(route('logout'));
        $this->post(route('login.attempt'), ['login' => 'ani@student.test', 'password' => 'RahasiaAni1'])
            ->assertRedirect(route('survey.index'));
    }

    public function test_admin_menolak_pendaftaran(): void
    {
        $this->post(route('register.store'), $this->form());
        $user = User::firstWhere('nim', '105099001');

        $this->actingAs(User::factory()->role('admin')->create())
            ->delete(route('admin.users.reject', $user))->assertSessionHas('success');
        $this->assertNull(User::firstWhere('nim', '105099001'));
    }

    public function test_akun_aktif_tidak_bisa_ditolak_lewat_rute_reject(): void
    {
        $student = User::factory()->role('mahasiswa')->create();
        $this->actingAs(User::factory()->role('admin')->create())
            ->delete(route('admin.users.reject', $student))->assertSessionHas('error');
        $this->assertNotNull($student->fresh());
    }

    public function test_nim_dan_email_ganda_ditolak(): void
    {
        User::factory()->create(['nim' => '105099001', 'email' => 'lama@student.test']);

        $this->post(route('register.store'), $this->form())->assertSessionHasErrors('nim');
        $this->post(route('register.store'), $this->form(['nim' => '105099002', 'email' => 'lama@student.test']))
            ->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
    }

    public function test_password_lemah_dan_konfirmasi_beda_ditolak(): void
    {
        $this->post(route('register.store'), $this->form(['password' => 'abc', 'password_confirmation' => 'abc']))
            ->assertSessionHasErrors('password');
        $this->post(route('register.store'), $this->form(['password_confirmation' => 'BedaSekali1']))
            ->assertSessionHasErrors('password');
        $this->assertSame(0, User::count());
    }

    public function test_pembatasan_domain_email(): void
    {
        config(['dorminsight.registration.email_domain' => 'unklab.ac.id']);

        $this->post(route('register.store'), $this->form())->assertSessionHasErrors('email');
        $this->post(route('register.store'), $this->form(['email' => 'ani@unklab.ac.id']))->assertSessionDoesntHaveErrors();
        $this->assertNotNull(User::firstWhere('email', 'ani@unklab.ac.id'));
    }

    public function test_tanpa_persetujuan_langsung_aktif_dan_masuk(): void
    {
        config(['dorminsight.registration.require_approval' => false]);

        $this->post(route('register.store'), $this->form())->assertRedirect(route('survey.index'));
        $this->assertAuthenticated();
        $this->assertTrue(User::firstWhere('nim', '105099001')->is_active);
    }

    public function test_pendaftaran_dapat_dimatikan(): void
    {
        config(['dorminsight.registration.enabled' => false]);

        $this->get(route('register'))->assertNotFound();
        $this->post(route('register.store'), $this->form())->assertNotFound();
        $this->get(route('login'))->assertDontSee(route('register'));
    }

    public function test_pendaftar_selalu_berperan_mahasiswa(): void
    {
        $this->post(route('register.store'), $this->form(['role' => 'admin']));
        $this->assertSame('mahasiswa', User::firstWhere('nim', '105099001')->role);
    }
}
