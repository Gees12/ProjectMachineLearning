<?php

namespace App\Services;

use App\Exceptions\AnalysisException;
use App\Models\AnalysisPrediction;
use App\Models\AnalysisRun;
use App\Models\AnalysisShap;
use App\Models\ResponseFeature;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Menjalankan analisis ML untuk satu survei dan menyimpan hasil secara atomik (UC-06). */
class AnalysisService
{
    public function __construct(private MlClient $ml)
    {
    }

    /** @return array<int, array{id: string, features: array}> */
    public function validRecords(Survey $survey): array
    {
        return ResponseFeature::query()
            ->join('responses', 'responses.id', '=', 'response_features.response_id')
            ->where('response_features.survey_id', $survey->id)
            ->where('responses.is_valid', true)
            ->get(['response_features.response_id', 'response_features.features'])
            ->map(fn ($row) => ['id' => $row->response_id, 'features' => $row->features])
            ->all();
    }

    public function run(Survey $survey, User $user): AnalysisRun
    {
        if ($survey->status !== 'closed') {
            throw new AnalysisException('Analisis hanya dapat dijalankan untuk survei yang sudah ditutup.');
        }

        $records = $this->validRecords($survey);

        try {
            if (empty($records)) {
                throw new AnalysisException('Belum ada respons valid untuk dianalisis.');
            }

            $info = $this->ml->modelInfo();
            $this->assertFeaturesMatch($info['features'] ?? [], array_keys($records[0]['features']));

            $predictions = $this->ml->predictBatch($records);
            $explain = $this->ml->explainGlobal($records);
        } catch (AnalysisException $e) {
            AnalysisRun::create([
                'survey_id' => $survey->id,
                'run_by' => $user->id,
                'model_version' => $info['model_version'] ?? 'unknown',
                'algorithm' => $info['algorithm'] ?? '-',
                'metrics' => [],
                'n_responses' => count($records),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'run_at' => now(),
            ]);
            throw $e;
        }

        $stability = collect($info['shap_stability'] ?? [])->pluck('top3_frequency', 'code');

        return DB::transaction(function () use ($survey, $user, $records, $info, $predictions, $explain, $stability) {
            $run = AnalysisRun::create([
                'survey_id' => $survey->id,
                'run_by' => $user->id,
                'model_version' => $info['model_version'],
                'algorithm' => $info['algorithm'],
                'metrics' => ($info['metrics'] ?? []) + [
                    'data_source' => $info['data_source'] ?? 'dummy',
                    'data_sufficient' => (bool) ($info['data_sufficient'] ?? false),
                    'risk_thresholds' => $info['risk_thresholds'] ?? config('dorminsight.risk_thresholds'),
                    'trained_at' => $info['trained_at'] ?? null,
                ],
                'n_responses' => count($records),
                'status' => 'success',
                'run_at' => now(),
            ]);

            foreach (array_chunk($predictions, 500) as $chunk) {
                AnalysisPrediction::insert(array_map(fn ($p) => [
                    'analysis_run_id' => $run->id,
                    'response_id' => $p['id'],
                    'probability' => $p['probability'],
                    'risk_level' => $p['risk_level'],
                ], $chunk));
            }

            AnalysisShap::insert(array_map(fn ($f) => [
                'analysis_run_id' => $run->id,
                'feature_code' => $f['code'],
                'mean_abs_shap' => $f['mean_abs_shap'],
                'direction' => $f['direction'],
                'rank' => $f['rank'],
                'top3_frequency' => $stability[$f['code']] ?? null,
            ], $explain['features'] ?? []));

            return $run;
        });
    }

    private function assertFeaturesMatch(array $expected, array $actual): void
    {
        $missing = array_diff($expected, $actual);
        $extra = array_diff($actual, $expected);
        if ($missing || $extra) {
            throw new AnalysisException('Fitur survei tidak cocok dengan model. Hilang: ['.implode(', ', $missing)
                .'], tidak dikenal model: ['.implode(', ', $extra).'].');
        }
    }
}
