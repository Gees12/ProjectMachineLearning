<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\DemoResponsesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_tanpa_analisis_menampilkan_statistik_deskriptif(): void
    {
        $this->seed(DemoResponsesSeeder::class);
        $this->actingAs(User::factory()->role('pengelola')->create())
            ->get(route('dashboard'))->assertOk()
            ->assertSee('Analisis ML belum dijalankan')->assertSee('Skor rata-rata per faktor');
    }

    public function test_segmen_kecil_disembunyikan(): void
    {
        $this->seed(DemoResponsesSeeder::class);
        $survey = Survey::firstWhere('status', 'closed');
        $service = app(DashboardService::class);

        $all = $service->data($survey);
        $this->assertFalse($all['hidden']);
        $this->assertNotNull($all['factor_means']['F_MAK']);

        // Hapus hampir semua respons angkatan 2026 agar segmennya kecil
        $ids = $service->validRows($survey)->filter(fn ($r) => $r->features['ANGKATAN'] === '2026')->pluck('response_id');
        \App\Models\Response::whereIn('id', $ids->slice(3))->delete();

        $small = $service->data($survey, ['angkatan' => '2026']);
        $this->assertTrue($small['hidden']);
        $this->assertNull($small['factor_means']['F_MAK']);
        $this->assertNull($small['stated_exit_rate']);
    }

    public function test_laporan_pdf_dan_excel_dapat_diunduh(): void
    {
        $this->seed(DemoResponsesSeeder::class);
        $survey = Survey::firstWhere('status', 'closed');
        $user = User::factory()->role('pengelola')->create();

        $this->actingAs($user)->get(route('reports.pdf', $survey))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($user)->get(route('reports.excel', $survey))->assertOk();
    }

    public function test_ekspor_csv_anonim(): void
    {
        $this->seed(DemoResponsesSeeder::class);
        $survey = Survey::firstWhere('status', 'closed');

        $response = $this->actingAs(User::factory()->role('admin')->create())->get(route('admin.responses.export', $survey));
        $response->assertOk();
        $csv = $response->streamedContent();
        $header = str_getcsv(strtok($csv, "\n"));

        $this->assertSame('response_id', $header[0]);
        $this->assertContains('F_MAK_1', $header);
        $this->assertContains('INGIN_KELUAR', $header);
        $this->assertContains('is_valid', $header);
        $this->assertNotContains('user_id', $header);
        $this->assertNotContains('nim', $header);
        $this->assertSame(151, count(array_filter(explode("\n", trim($csv)))));
    }
}
