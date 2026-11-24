<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Instrumen survei usulan (PRD 7.1, Data Dictionary B). Dibuat sebagai DRAFT.
 * Validasi pertanyaan bersama pengelola asrama sebelum dibuka.
 */
class SurveyTemplateSeeder extends Seeder
{
    public const FACTORS = [
        'F_FAS' => ['Fasilitas Kamar', [
            'Kondisi tempat tidur, lemari, dan meja di kamar saya memadai.',
            'Fasilitas kamar yang rusak diperbaiki dalam waktu yang wajar.',
        ]],
        'F_KEB' => ['Kebersihan', [
            'Kamar mandi dan area umum asrama terjaga kebersihannya.',
            'Petugas dan jadwal kebersihan asrama berjalan dengan baik.',
        ]],
        'F_MAK' => ['Makanan / Kafetaria', [
            'Kualitas dan variasi makanan memenuhi kebutuhan saya.',
            'Jadwal dan porsi makan sesuai dengan kebutuhan saya.',
        ]],
        'F_BIA' => ['Biaya', [
            'Biaya asrama sebanding dengan fasilitas dan layanan yang diterima.',
            'Biaya asrama terjangkau bagi saya atau keluarga saya.',
        ]],
        'F_PER' => ['Peraturan Asrama', [
            'Peraturan asrama jelas dan diterapkan secara adil.',
            'Peraturan asrama (jam malam, kegiatan wajib, dan lainnya) masuk akal bagi saya.',
        ]],
        'F_PRI' => ['Privasi', [
            'Saya memiliki cukup privasi di asrama.',
            'Jumlah penghuni per kamar membuat saya tetap nyaman.',
        ]],
        'F_KEA' => ['Keamanan', [
            'Saya merasa aman dengan barang dan diri saya di asrama.',
            'Akses keluar-masuk asrama diawasi dengan baik.',
        ]],
        'F_SOS' => ['Lingkungan Sosial', [
            'Hubungan saya dengan penghuni lain berjalan baik.',
            'Saya merasa diterima dan memiliki teman di asrama.',
        ]],
        'F_PEL' => ['Pelayanan Pengelola', [
            'Pengelola asrama menanggapi keluhan dengan baik.',
            'Pengelola asrama mudah dihubungi dan bersikap ramah.',
        ]],
        'F_BEL' => ['Kenyamanan Belajar dan Internet', [
            'Suasana asrama mendukung kegiatan belajar saya.',
            'Koneksi internet di asrama memadai untuk kebutuhan kuliah.',
        ]],
    ];

    public const ANGKATAN = [
        '2023_atau_sebelumnya' => '2023 atau sebelumnya', '2024' => '2024', '2025' => '2025', '2026' => '2026',
    ];

    public const LAMA_TINGGAL = ['lt1' => 'Kurang dari 1 tahun', '1_2' => '1 sampai 2 tahun', 'gt2' => 'Lebih dari 2 tahun'];

    public const TEMPLATE_TITLE = 'Survei Kepuasan Penghuni Crystal Dormitory (Template)';

    public function run(): void
    {
        if (! Survey::where('title', self::TEMPLATE_TITLE)->exists()) {
            $this->createSurvey(self::TEMPLATE_TITLE, 'draft');
        }
    }

    public function createSurvey(string $title, string $status, $openAt = null, $closeAt = null): Survey
    {
        $survey = Survey::create([
            'title' => $title,
            'description' => "Terima kasih telah meluangkan waktu. Survei ini berisi pernyataan tentang pengalaman Anda tinggal di Crystal Dormitory.\nPilih angka 1 (sangat tidak setuju) sampai 5 (sangat setuju).",
            'open_at' => $openAt ?? now()->startOfDay(),
            'close_at' => $closeAt ?? now()->addWeeks(2)->endOfDay(),
            'status' => $status,
            'created_by' => User::where('role', 'admin')->value('id'),
        ]);

        $order = 1;
        $factorOrder = 1;
        foreach (self::FACTORS as $code => [$name, $items]) {
            $factor = $survey->factors()->create(['code' => $code, 'name' => $name, 'sort_order' => $factorOrder++]);
            foreach ($items as $i => $text) {
                $survey->questions()->create([
                    'factor_id' => $factor->id, 'code' => $code.'_'.($i + 1), 'text' => $text,
                    'type' => 'likert', 'ml_role' => 'factor_item', 'is_required' => true, 'sort_order' => $order++,
                ]);
            }
        }

        $angkatan = $survey->questions()->create(['code' => 'ANGKATAN', 'text' => 'Tahun masuk kuliah (angkatan)',
            'type' => 'single_choice', 'ml_role' => 'feature', 'is_required' => true, 'sort_order' => $order++]);
        foreach (array_keys(self::ANGKATAN) as $i => $value) {
            $angkatan->options()->create(['value' => $value, 'label' => self::ANGKATAN[$value], 'sort_order' => $i]);
        }

        $lama = $survey->questions()->create(['code' => 'LAMA_TINGGAL', 'text' => 'Sudah berapa lama Anda tinggal di Crystal Dormitory?',
            'type' => 'single_choice', 'ml_role' => 'feature', 'is_required' => true, 'sort_order' => $order++]);
        foreach (array_keys(self::LAMA_TINGGAL) as $i => $value) {
            $lama->options()->create(['value' => $value, 'label' => self::LAMA_TINGGAL[$value], 'sort_order' => $i]);
        }

        $survey->questions()->create(['code' => 'KEP_UMUM', 'text' => 'Secara keseluruhan, saya puas tinggal di Crystal Dormitory.',
            'type' => 'likert', 'ml_role' => 'descriptive', 'is_required' => true, 'sort_order' => $order++]);
        $survey->questions()->create(['code' => 'INGIN_KELUAR',
            'text' => 'Apakah Anda berencana atau ingin pindah keluar dari Crystal Dormitory pada semester berikutnya?',
            'type' => 'yes_no', 'ml_role' => 'target', 'is_required' => true, 'sort_order' => $order++]);
        $survey->questions()->create(['code' => 'SARAN', 'text' => 'Saran untuk perbaikan asrama (opsional)',
            'type' => 'text', 'ml_role' => 'open', 'is_required' => false, 'sort_order' => $order++]);

        return $survey;
    }
}
