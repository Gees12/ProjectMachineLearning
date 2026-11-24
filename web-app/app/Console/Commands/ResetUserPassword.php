<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Mereset password akun dari terminal, mis. saat admin lupa password.
 *
 *   php artisan dorm:reset-password admin@dorminsight.test
 *   php artisan dorm:reset-password 10500001 --password=Rahasia123
 */
class ResetUserPassword extends Command
{
    protected $signature = 'dorm:reset-password {login : Email atau NIM akun} {--password= : Password baru (kosongkan untuk dibuat acak)}';

    protected $description = 'Reset password akun DormInsight; pengguna wajib menggantinya saat login berikutnya';

    public function handle(): int
    {
        $login = $this->argument('login');
        $user = User::where(str_contains($login, '@') ? 'email' : 'nim', $login)->first();

        if (! $user) {
            $this->error("Akun dengan email/NIM \"{$login}\" tidak ditemukan.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: Str::password(10, symbols: false);
        $user->forceFill(['password' => $password, 'must_change_password' => true, 'is_active' => true])->save();

        // Buka kunci login jika sebelumnya terlalu banyak percobaan gagal
        foreach ([$user->email, $user->nim] as $key) {
            if ($key) {
                RateLimiter::clear(Str::lower($key).'|127.0.0.1');
            }
        }

        $this->info("Password {$user->name} ({$user->email}) direset.");
        $this->line("Password sementara: <comment>{$password}</comment>");
        $this->line('Pengguna wajib mengganti password ini saat login berikutnya.');

        return self::SUCCESS;
    }
}
