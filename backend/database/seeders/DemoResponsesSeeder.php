<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Response;
use App\Models\ResponseFeature;
use App\Services\FeatureBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Membuat survei DEMO berstatus "ditutup" berisi respons DUMMY untuk mencoba
 * dashboard, ekspor, dan analisis ML. JANGAN dipakai di server produksi.
 *
 *   php artisan db:seed --class=DemoResponsesSeeder
 */
class DemoResponsesSeeder extends Seeder
{
    public const TITLE = '[DEMO] Survei Kepuasan (data dummy)';

    private const WEIGHTS = ['F_MAK' => -1.1, 'F_BIA' => -0.9, 'F_PRI' => -0.7, 'F_PER' => -0.4, 'F_FAS' => -0.3,
        'F_PEL' => -0.3, 'F_SOS' => -0.2, 'F_KEB' => -0.15, 'F_BEL' => -0.15, 'F_KEA' => -0.05];

    private const MEANS = ['F_FAS' => 3.4, 'F_KEB' => 3.8, 'F_MAK' => 2.8, 'F_BIA' => 3.0, 'F_PER' => 3.0,
        'F_PRI' => 3.1, 'F_KEA' => 4.1, 'F_SOS' => 3.9, 'F_PEL' => 3.5, 'F_BEL' => 3.0];

    public function run(FeatureBuilder $builder): void
    {
        if (\App\Models\Survey::where('title', self::TITLE)->exists()) {
            $this->command?->info('Survei demo sudah ada, dilewati.');

            return;
        }

        mt_srand(42);
        $survey = (new SurveyTemplateSeeder)->createSurvey(self::TITLE, 'closed',
            now()->subWeeks(3), now()->subWeek());
        $questions = $survey->questions()->with('factor')->get();
        $byCode = $questions->keyBy('code');

        DB::transaction(function () use ($survey, $questions, $byCode, $builder) {
            for ($n = 0; $n < 150; $n++) {
                $general = $this->normal(0, 0.5);
                $latent = [];
                $answers = [];
                foreach (self::MEANS as $code => $mean) {
                    $latent[$code] = $this->clip($mean + $general + $this->normal(0, 0.8));
                    foreach ([1, 2] as $i) {
                        $answers[$byCode["{$code}_{$i}"]->id] = (int) round($this->clip($latent[$code] + $this->normal(0, 0.5)));
                    }
                }
                $answers[$byCode['ANGKATAN']->id] = $this->pick(['2023_atau_sebelumnya' => 20, '2024' => 25, '2025' => 30, '2026' => 25]);
                $lama = $this->pick(['lt1' => 35, '1_2' => 40, 'gt2' => 25]);
                $answers[$byCode['LAMA_TINGGAL']->id] = $lama;

                $logit = -1.0 + ($lama === 'gt2' ? 0.4 : 0) + $this->normal(0, 0.6);
                foreach (self::WEIGHTS as $code => $w) {
                    $logit += $w * ($latent[$code] - 3);
                }
                $answers[$byCode['INGIN_KELUAR']->id] = (mt_rand() / mt_getrandmax()) < 1 / (1 + exp(-$logit)) ? 1 : 0;
                $answers[$byCode['KEP_UMUM']->id] = (int) round($this->clip(array_sum($latent) / count($latent) + $this->normal(0, 0.4)));

                $tooFast = $n % 30 === 0;
                $response = Response::create([
                    'survey_id' => $survey->id,
                    'duration_seconds' => $tooFast ? mt_rand(40, 110) : mt_rand(150, 600),
                    'submitted_date' => now()->subDays(mt_rand(8, 20))->toDateString(),
                    'is_valid' => ! $tooFast,
                    'flag_reason' => $tooFast ? 'too_fast' : null,
                ]);
                $rows = [];
                foreach ($questions as $q) {
                    if (isset($answers[$q->id])) {
                        $rows[] = ['response_id' => $response->id, 'question_id' => $q->id] + $builder->normalize($q, $answers[$q->id]);
                    }
                }
                Answer::insert($rows);
                $built = $builder->build($questions, $answers);
                ResponseFeature::create(['response_id' => $response->id, 'survey_id' => $survey->id] + $built);
            }
        });

        $this->command?->info("Survei demo dibuat (ID {$survey->id}) dengan 150 respons dummy.");
    }

    private function normal(float $mean, float $sd): float
    {
        $u = max(1e-9, mt_rand() / mt_getrandmax());
        $v = mt_rand() / mt_getrandmax();

        return $mean + $sd * sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
    }

    private function clip(float $x): float
    {
        return max(1.0, min(5.0, $x));
    }

    private function pick(array $weights): string
    {
        $r = mt_rand(1, array_sum($weights));
        foreach ($weights as $value => $w) {
            if (($r -= $w) <= 0) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }
}
