<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Mengubah jawaban survei menjadi fitur siap ML.
 * HARUS konsisten dengan ml-service/src/features.py:
 * skor faktor = rata-rata item faktor, dibulatkan 2 desimal.
 */
class FeatureBuilder
{
    /** @return array{value_numeric: float|null, value_text: string|null} */
    public function normalize(Question $question, mixed $raw): array
    {
        return match ($question->type) {
            'likert' => ['value_numeric' => (float) $raw, 'value_text' => null],
            'yes_no' => ['value_numeric' => (float) ((int) $raw === 1 ? 1 : 0), 'value_text' => null],
            default => ['value_numeric' => null, 'value_text' => trim((string) $raw)],
        };
    }

    /**
     * @param  Collection<int, Question>  $questions  dengan relasi factor
     * @param  array<int|string, mixed>  $answers  [question_id => nilai mentah]
     * @return array{features: array<string, float|string>, target: int, kep_umum: int|null}
     */
    public function build(Collection $questions, array $answers): array
    {
        $factorValues = [];
        $features = [];
        $target = 0;
        $kepUmum = null;

        foreach ($questions as $question) {
            if (! array_key_exists($question->id, $answers) || $answers[$question->id] === '' || $answers[$question->id] === null) {
                continue;
            }
            $value = $this->normalize($question, $answers[$question->id]);

            switch ($question->ml_role) {
                case 'factor_item':
                    if ($question->factor) {
                        $factorValues[$question->factor->code][] = $value['value_numeric'];
                    }
                    break;
                case 'feature':
                    $features[$question->code] = $value['value_text'] ?? (string) $value['value_numeric'];
                    break;
                case 'target':
                    $target = (int) $value['value_numeric'];
                    break;
                case 'descriptive':
                    $kepUmum ??= $value['value_numeric'] !== null ? (int) $value['value_numeric'] : null;
                    break;
            }
        }

        $scores = [];
        foreach ($factorValues as $code => $values) {
            $scores[$code] = round(array_sum($values) / count($values), 2);
        }

        return ['features' => $scores + $features, 'target' => $target, 'kep_umum' => $kepUmum];
    }
}
