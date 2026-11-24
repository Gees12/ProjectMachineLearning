<?php

namespace App\Services;

use App\Models\AnalysisPrediction;
use App\Models\ResponseFeature;
use App\Models\Survey;
use Illuminate\Support\Collection;

/** Agregasi untuk dashboard, perbandingan, dan laporan. Semua hasil bersifat agregat. */
class DashboardService
{
    public const SEGMENT_FIELDS = ['angkatan' => 'ANGKATAN', 'lama_tinggal' => 'LAMA_TINGGAL'];

    public const DEFAULT_LABELS = ['ANGKATAN' => 'Angkatan', 'LAMA_TINGGAL' => 'Lama tinggal'];

    /** @return Collection<int, ResponseFeature> */
    public function validRows(Survey $survey): Collection
    {
        return ResponseFeature::query()
            ->join('responses', 'responses.id', '=', 'response_features.response_id')
            ->where('response_features.survey_id', $survey->id)
            ->where('responses.is_valid', true)
            ->get(['response_features.response_id', 'response_features.features',
                'response_features.target', 'response_features.kep_umum']);
    }

    /** Label kode fitur, mis. F_MAK => "Makanan / Kafetaria". */
    public function labels(Survey $survey): array
    {
        return $survey->factors()->pluck('name', 'code')->all() + self::DEFAULT_LABELS;
    }

    /** Pilihan filter segmen dari opsi pertanyaan ANGKATAN dan LAMA_TINGGAL. */
    public function segmentOptions(Survey $survey): array
    {
        $out = [];
        foreach (self::SEGMENT_FIELDS as $param => $code) {
            $question = $survey->questions()->where('code', $code)->with('options')->first();
            $out[$param] = $question ? $question->options->pluck('label', 'value')->all() : [];
        }

        return $out;
    }

    public function data(Survey $survey, array $filters = []): array
    {
        $minSegment = config('dorminsight.min_segment_size');
        $labels = $this->labels($survey);
        $factorCodes = $survey->factors()->pluck('code')->all();

        $all = $this->validRows($survey);
        $filters = array_filter(array_intersect_key($filters, self::SEGMENT_FIELDS));
        $rows = $all->filter(function ($row) use ($filters) {
            foreach ($filters as $param => $value) {
                if ((string) ($row->features[self::SEGMENT_FIELDS[$param]] ?? '') !== (string) $value) {
                    return false;
                }
            }

            return true;
        })->values();

        $hidden = $filters && $rows->count() < $minSegment;
        $n = $rows->count();

        $factorMeans = [];
        foreach ($factorCodes as $code) {
            $values = $rows->map(fn ($r) => $r->features[$code] ?? null)->filter(fn ($v) => $v !== null);
            $factorMeans[$code] = $hidden || $values->isEmpty() ? null : round($values->avg(), 2);
        }

        $run = $survey->latestSuccessfulRun;
        $risk = ['rendah' => 0, 'sedang' => 0, 'tinggi' => 0];
        if ($run && ! $hidden && $n > 0) {
            $counts = AnalysisPrediction::where('analysis_run_id', $run->id)
                ->whereIn('response_id', $rows->pluck('response_id'))
                ->get(['risk_level'])->countBy('risk_level');
            foreach ($risk as $level => $_) {
                $risk[$level] = (int) ($counts[$level] ?? 0);
            }
        }
        $riskTotal = array_sum($risk);

        $shap = $run ? $run->shap->map(fn ($s) => [
            'code' => $s->feature_code,
            'label' => $labels[$s->feature_code] ?? $s->feature_code,
            'mean_abs_shap' => $s->mean_abs_shap,
            'direction' => $s->direction,
            'rank' => $s->rank,
            'top3_frequency' => $s->top3_frequency,
            'stable' => $s->top3_frequency !== null && $s->top3_frequency >= config('dorminsight.stable_top3_frequency'),
        ])->values()->all() : [];

        return [
            'survey' => $survey,
            'filters' => $filters,
            'hidden' => $hidden,
            'labels' => $labels,
            'n' => $n,
            'total_valid' => $all->count(),
            'response_rate' => round($all->count() / max(1, config('dorminsight.population')), 4),
            'avg_kep_umum' => $hidden || $n === 0 ? null : round((float) $rows->whereNotNull('kep_umum')->avg('kep_umum'), 2),
            'stated_exit_rate' => $hidden || $n === 0 ? null : round($rows->avg('target'), 4),
            'factor_means' => $factorMeans,
            'run' => $run,
            'risk' => $risk,
            'risk_pct' => array_map(fn ($c) => $riskTotal ? round($c / $riskTotal, 4) : 0, $risk),
            'shap' => $shap,
            'insights' => $this->insights($shap),
        ];
    }

    /** Kalimat sederhana untuk 3 faktor teratas (NFR-033, US-19). */
    public function insights(array $shap): array
    {
        return collect($shap)->take(3)->map(function ($s) {
            $name = mb_strtolower($s['label']);
            $sentence = match ($s['direction']) {
                'negatif' => "Kepuasan terhadap {$name} yang rendah berkaitan dengan meningkatnya keinginan keluar asrama.",
                'positif' => "Nilai {$name} yang lebih tinggi berkaitan dengan meningkatnya keinginan keluar asrama.",
                default => "Pengaruh {$name} terhadap keinginan keluar bervariasi antarmahasiswa.",
            };
            if ($s['top3_frequency'] !== null) {
                $pct = round($s['top3_frequency'] * 100);
                $sentence .= $s['stable']
                    ? " Hasil ini konsisten (masuk 3 besar pada {$pct}% uji ulang)."
                    : " Hasil ini belum stabil (masuk 3 besar hanya pada {$pct}% uji ulang), tafsirkan dengan hati-hati.";
            }

            return $sentence;
        })->all();
    }

    /** Rata-rata fitur sebagai baseline simulasi: faktor = rata-rata, kategori = modus. */
    public function baselineFeatures(Survey $survey): ?array
    {
        $rows = $this->validRows($survey);
        if ($rows->isEmpty()) {
            return null;
        }
        $baseline = [];
        foreach (array_keys($rows->first()->features) as $code) {
            $values = $rows->pluck("features.{$code}")->filter(fn ($v) => $v !== null);
            $baseline[$code] = is_numeric($values->first()) && ! in_array($code, self::SEGMENT_FIELDS, true)
                ? round($values->avg(), 2)
                : (string) $values->countBy()->sortDesc()->keys()->first();
        }

        return $baseline;
    }
}
