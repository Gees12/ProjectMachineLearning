<?php

namespace App\Services;

use App\Exceptions\SubmissionException;
use App\Models\Answer;
use App\Models\Response;
use App\Models\ResponseFeature;
use App\Models\Survey;
use App\Models\SurveySubmission;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan survei secara anonim dalam SATU transaksi:
 *  - responses / answers / response_features: jawaban TANPA user_id
 *  - survey_submissions: catatan "sudah mengisi" TANPA jawaban
 */
class SubmissionService
{
    public function __construct(
        private FeatureBuilder $features,
        private ResponseValidator $validator,
    ) {
    }

    public function submit(Survey $survey, User $user, array $answers, int $durationSeconds): Response
    {
        if (! $survey->isOpenForSubmission()) {
            throw new SubmissionException('Survei sudah ditutup atau belum dibuka.');
        }

        return DB::transaction(function () use ($survey, $user, $answers, $durationSeconds) {
            if (SurveySubmission::where('survey_id', $survey->id)->where('user_id', $user->id)->exists()) {
                throw new SubmissionException('Anda sudah mengisi survei periode ini.');
            }

            $questions = $survey->questions()->with('factor')->get();
            [$isValid, $reason] = $this->validator->check($questions, $answers, $durationSeconds);
            $today = now()->toDateString();

            $response = Response::create([
                'survey_id' => $survey->id,
                'duration_seconds' => max(0, $durationSeconds),
                'submitted_date' => $today,
                'is_valid' => $isValid,
                'flag_reason' => $reason,
            ]);

            $rows = [];
            foreach ($questions as $question) {
                $raw = $answers[$question->id] ?? null;
                if ($raw === null || $raw === '') {
                    continue;
                }
                $rows[] = ['response_id' => $response->id, 'question_id' => $question->id]
                    + $this->features->normalize($question, $raw);
            }
            Answer::insert($rows);

            $built = $this->features->build($questions, $answers);
            ResponseFeature::create([
                'response_id' => $response->id,
                'survey_id' => $survey->id,
                'features' => $built['features'],
                'target' => $built['target'],
                'kep_umum' => $built['kep_umum'],
            ]);

            try {
                SurveySubmission::create([
                    'survey_id' => $survey->id,
                    'user_id' => $user->id,
                    'submitted_date' => $today,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new SubmissionException('Anda sudah mengisi survei periode ini.');
            }

            return $response;
        });
    }
}
