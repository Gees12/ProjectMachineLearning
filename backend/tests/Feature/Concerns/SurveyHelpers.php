<?php

namespace Tests\Feature\Concerns;

use App\Models\Survey;
use App\Models\User;
use Database\Seeders\SurveyTemplateSeeder;

trait SurveyHelpers
{
    protected function activeSurvey(): Survey
    {
        return (new SurveyTemplateSeeder)->createSurvey('Survei Uji', 'active', now()->subDay(), now()->addWeek());
    }

    /** Jawaban lengkap yang valid; $overrides berdasarkan kode pertanyaan. */
    protected function answersFor(Survey $survey, array $overrides = [], int $likert = 3): array
    {
        $answers = [];
        foreach ($survey->questions as $q) {
            $value = match ($q->code) {
                'ANGKATAN' => '2025',
                'LAMA_TINGGAL' => '1_2',
                'INGIN_KELUAR' => '0',
                'SARAN' => '',
                default => $likert,
            };
            if ($q->ml_role === 'factor_item' && $likert === 3) {
                // variasikan agar tidak dianggap jawaban seragam
                $value = ($q->sort_order % 5) + 1;
            }
            $answers[$q->id] = $overrides[$q->code] ?? $value;
        }

        return $answers;
    }

    protected function student(): User
    {
        return User::factory()->role('mahasiswa')->create();
    }

    /** Mengisi survei sebagai mahasiswa, melewati halaman consent dengan waktu mulai tertentu. */
    protected function submitAs(User $user, Survey $survey, array $answers, int $secondsAgo = 300)
    {
        return $this->actingAs($user)
            ->withSession(["survey_started.{$survey->id}" => now()->subSeconds($secondsAgo)->timestamp])
            ->post(route('survey.submit'), ['answers' => $answers]);
    }
}
