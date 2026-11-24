<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun awal. Aman dijalankan berulang: akun yang sudah ada TIDAK diubah.
     * Semua akun wajib mengganti password saat login pertama.
     *
     * Di server (APP_ENV=production):
     *  - password admin diambil dari ADMIN_INITIAL_PASSWORD
     *  - akun pengelola dan mahasiswa demo tidak dibuat
     */
    public function run(): void
    {
        $production = app()->environment('production');

        User::firstOrCreate(['email' => config('dorminsight.admin.email')], [
            'name' => 'Administrator', 'role' => 'admin', 'must_change_password' => true,
            'password' => config('dorminsight.admin.initial_password') ?: ($production ? Str::password(16) : 'Admin12345'),
        ]);

        if (! $production) {
            User::firstOrCreate(['email' => 'pengelola@dorminsight.test'], [
                'name' => 'Pengelola Crystal Dormitory', 'role' => 'pengelola', 'password' => 'Pengelola123', 'must_change_password' => true,
            ]);
            foreach (range(1, 3) as $i) {
                User::firstOrCreate(['email' => "mahasiswa{$i}@dorminsight.test"], [
                    'name' => "Mahasiswa Demo {$i}", 'nim' => "1050000{$i}", 'role' => 'mahasiswa',
                    'password' => "1050000{$i}", 'must_change_password' => true,
                ]);
            }
        }

        $this->call(SurveyTemplateSeeder::class);
    }
}
