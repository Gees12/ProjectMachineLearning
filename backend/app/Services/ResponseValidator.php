<?php

namespace App\Services;

use Illuminate\Support\Collection;

/** Menandai respons mencurigakan untuk ditinjau admin (FR-042). */
class ResponseValidator
{
    /**
     * @return array{0: bool, 1: string|null} [is_valid, flag_reason]
     */
    public function check(Collection $questions, array $answers, int $durationSeconds): array
    {
        $reasons = [];

        $likert = $questions
            ->filter(fn ($q) => $q->ml_role === 'factor_item' && isset($answers[$q->id]))
            ->map(fn ($q) => (int) $answers[$q->id])
            ->values();

        if ($likert->count() >= 4 && $likert->unique()->count() === 1) {
            $reasons[] = 'straight_lining';
        }

        if ($durationSeconds < config('dorminsight.min_duration_seconds')) {
            $reasons[] = 'too_fast';
        }

        return [empty($reasons), $reasons ? implode(',', $reasons) : null];
    }

    public static function reasonLabel(?string $reason): string
    {
        if (! $reason) {
            return '';
        }
        $labels = ['straight_lining' => 'Jawaban seragam', 'too_fast' => 'Terlalu cepat'];

        return collect(explode(',', $reason))->map(fn ($r) => $labels[$r] ?? $r)->implode(', ');
    }
}
