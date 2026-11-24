<?php

namespace Tests\Feature;

use App\Models\AnalysisPrediction;
use App\Models\AnalysisRun;
use App\Models\AnalysisShap;
use App\Models\User;
use Database\Seeders\DemoResponsesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalysisTest extends TestCase
{
    use RefreshDatabase;

    private const FEATURES = ['F_FAS', 'F_KEB', 'F_MAK', 'F_BIA', 'F_PER', 'F_PRI', 'F_KEA', 'F_SOS', 'F_PEL', 'F_BEL', 'ANGKATAN', 'LAMA_TINGGAL'];

    private function fakeMl(array $features = self::FEATURES): void
    {
        config(['dorminsight.ml.url' => 'http://ml.test', 'dorminsight.ml.key' => 'k']);
        Http::fake([
            'ml.test/model/info' => Http::response([
                'model_version' => 'lr-0.1.0-dummy', 'algorithm' => 'LogisticRegression', 'trained_at' => '2026-11-23T11:00:00+08:00',
                'data_source' => 'dummy', 'data_sufficient' => true, 'features' => $features, 'target' => 'INGIN_KELUAR',
                'risk_thresholds' => ['sedang' => 0.4, 'tinggi' => 0.7],
                'metrics' => ['f1_keluar' => 0.655, 'baseline_f1_keluar' => 0.0],
                'shap_stability' => [['code' => 'F_MAK', 'top3_frequency' => 0.67]],
            ]),
            'ml.test/predict/batch' => function ($request) {
                return Http::response(['model_version' => 'lr-0.1.0-dummy', 'predictions' => collect($request['records'])
                    ->map(fn ($r, $i) => ['id' => $r['id'], 'probability' => ($i % 10) / 10, 'risk_level' => ['rendah', 'sedang', 'tinggi'][$i % 3]])->all()]);
            },
            'ml.test/explain/global' => Http::response(['model_version' => 'lr-0.1.0-dummy', 'method' => 'shap.LinearExplainer', 'n_records' => 145,
                'features' => collect(self::FEATURES)->map(fn ($c, $i) => ['code' => $c, 'mean_abs_shap' => 0.5 - $i * 0.03, 'direction' => 'negatif', 'rank' => $i + 1])->all()]),
        ]);
    }

    private function demo()
    {
        $this->seed(DemoResponsesSeeder::class);

        return \App\Models\Survey::firstWhere('status', 'closed');
    }

    public function test_analisis_menyimpan_prediksi_dan_shap(): void
    {
        $this->fakeMl();
        $survey = $this->demo();
        $admin = User::factory()->role('admin')->create();
        $valid = $survey->responses()->where('is_valid', true)->count();

        $this->actingAs($admin)->post(route('admin.analyses.run', $survey))
            ->assertRedirect(route('admin.analyses.index', $survey))->assertSessionHas('success');

        $run = AnalysisRun::sole();
        $this->assertSame('success', $run->status);
        $this->assertSame($valid, $run->n_responses);
        $this->assertSame('dummy', $run->metrics['data_source']);
        $this->assertTrue($run->isExperimental());
        $this->assertSame($valid, AnalysisPrediction::count());
        $this->assertSame(12, AnalysisShap::count());
        $this->assertEqualsWithDelta(0.67, AnalysisShap::firstWhere('feature_code', 'F_MAK')->top3_frequency, 0.001);

        Http::assertSent(fn ($req) => $req->hasHeader('X-API-Key', 'k'));
    }

    public function test_dashboard_menampilkan_hasil_dan_peringatan_dummy(): void
    {
        $this->fakeMl();
        $survey = $this->demo();
        $admin = User::factory()->role('admin')->create();
        $this->actingAs($admin)->post(route('admin.analyses.run', $survey));

        $pengelola = User::factory()->role('pengelola')->create();
        $this->actingAs($pengelola)->get(route('dashboard', ['survey' => $survey->id]))
            ->assertOk()->assertSee('Hasil eksperimental')->assertSee('Faktor paling berpengaruh')->assertSee('lr-0.1.0-dummy');
    }

    public function test_ml_service_mati_dicatat_sebagai_gagal_tanpa_hasil_parsial(): void
    {
        config(['dorminsight.ml.url' => 'http://ml.test']);
        Http::fake(['ml.test/*' => Http::response(['detail' => 'Model not loaded'], 503)]);
        $survey = $this->demo();

        $this->actingAs(User::factory()->role('admin')->create())
            ->post(route('admin.analyses.run', $survey))->assertSessionHas('error');

        $this->assertSame('failed', AnalysisRun::sole()->status);
        $this->assertSame(0, AnalysisPrediction::count());
        $this->assertSame(0, AnalysisShap::count());
    }

    public function test_fitur_tidak_cocok_dibatalkan(): void
    {
        $this->fakeMl(['F_FAS', 'F_LAIN']);
        $survey = $this->demo();

        $this->actingAs(User::factory()->role('admin')->create())
            ->post(route('admin.analyses.run', $survey))->assertSessionHas('error');

        $this->assertStringContainsString('tidak cocok', AnalysisRun::sole()->error_message);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'predict'));
    }

    public function test_survei_belum_ditutup_tidak_dapat_dianalisis(): void
    {
        $this->fakeMl();
        $survey = $this->demo();
        $survey->update(['status' => 'active']);

        $this->actingAs(User::factory()->role('admin')->create())
            ->post(route('admin.analyses.run', $survey))->assertSessionHas('error');
        $this->assertSame(0, AnalysisRun::count());
    }
}
