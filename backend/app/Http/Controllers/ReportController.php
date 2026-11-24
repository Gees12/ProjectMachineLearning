<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Services\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;

/** Laporan PDF dan Excel (FR-070 sampai FR-072). Hanya data agregat. */
class ReportController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    public function pdf(Survey $survey)
    {
        $d = $this->dashboard->data($survey);

        return Pdf::loadView('reports.pdf', compact('d'))
            ->setPaper('a4')
            ->download('Laporan_DormInsight_'.$survey->id.'_'.now()->format('Ymd').'.pdf');
    }

    public function excel(Survey $survey)
    {
        $d = $this->dashboard->data($survey);
        $run = $d['run'];

        $summary = collect([
            ['Item' => 'Survei', 'Nilai' => $survey->title],
            ['Item' => 'Periode', 'Nilai' => $survey->open_at->format('d M Y').' - '.$survey->close_at->format('d M Y')],
            ['Item' => 'Respons valid', 'Nilai' => $d['total_valid']],
            ['Item' => 'Tingkat respons', 'Nilai' => round($d['response_rate'] * 100, 1).'%'],
            ['Item' => 'Rata-rata kepuasan keseluruhan (1-5)', 'Nilai' => $d['avg_kep_umum']],
            ['Item' => 'Menyatakan ingin keluar', 'Nilai' => $d['stated_exit_rate'] !== null ? round($d['stated_exit_rate'] * 100, 1).'%' : '-'],
            ['Item' => 'Tanggal analisis', 'Nilai' => $run?->run_at?->format('d M Y H:i') ?? 'Belum dianalisis'],
            ['Item' => 'Versi model', 'Nilai' => $run?->model_version ?? '-'],
            ['Item' => 'F1 kelas ingin keluar', 'Nilai' => $run->metrics['f1_keluar'] ?? '-'],
            ['Item' => 'Catatan', 'Nilai' => $run && $run->isExperimental() ? 'Hasil eksperimental (data dummy atau data belum memadai)' : 'Hasil agregat; bukan penilaian individu'],
        ]);

        $factors = collect($d['factor_means'])->map(fn ($mean, $code) => [
            'Kode' => $code, 'Faktor' => $d['labels'][$code] ?? $code, 'Rata-rata (1-5)' => $mean,
        ])->values();

        $shap = collect($d['shap'])->map(fn ($s) => [
            'Peringkat' => $s['rank'], 'Faktor' => $s['label'], 'Rata-rata |SHAP|' => $s['mean_abs_shap'],
            'Arah' => $s['direction'], 'Masuk 3 besar (uji ulang)' => $s['top3_frequency'] !== null ? round($s['top3_frequency'] * 100).'%' : '-',
        ]);

        $risk = collect($d['risk'])->map(fn ($count, $level) => [
            'Kategori' => ucfirst($level), 'Jumlah' => $count, 'Persentase' => round($d['risk_pct'][$level] * 100, 1).'%',
        ])->values();

        return (new FastExcel(new SheetCollection([
            'Ringkasan' => $summary,
            'Skor Faktor' => $factors,
            'Faktor Dominan' => $shap,
            'Distribusi Risiko' => $risk,
        ])))->download('Laporan_DormInsight_'.$survey->id.'_'.now()->format('Ymd').'.xlsx');
    }
}
