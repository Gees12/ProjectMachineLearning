<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Response;
use App\Models\ResponseFeature;
use App\Models\SurveySubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\SurveyHelpers;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase, SurveyHelpers;

    public function test_alur_consent_ke_formulir(): void
    {
        $survey = $this->activeSurvey();
        $user = $this->student();

        $this->actingAs($user)->get(route('survey.index'))->assertOk()->assertSee('Isi Survei');
        $this->actingAs($user)->get(route('survey.fill'))->assertRedirect(route('survey.consent'));
        $this->actingAs($user)->post(route('survey.start'), [])->assertSessionHasErrors('agree');
        $this->actingAs($user)->post(route('survey.start'), ['agree' => '1'])->assertRedirect(route('survey.fill'));
        $this->actingAs($user)->get(route('survey.fill'))->assertOk()->assertSee('Makanan / Kafetaria')->assertSee('Tinjau Jawaban');
    }

    public function test_jawaban_tersimpan_anonim_dan_fitur_dihitung(): void
    {
        $survey = $this->activeSurvey();
        $user = $this->student();
        $answers = $this->answersFor($survey, ['F_MAK_1' => 2, 'F_MAK_2' => 3, 'INGIN_KELUAR' => '1', 'KEP_UMUM' => 2]);

        $this->submitAs($user, $survey, $answers)->assertRedirect(route('survey.thanks'));

        // Skema: tidak ada kolom penghubung ke akun (NFR-010)
        $this->assertFalse(Schema::hasColumn('responses', 'user_id'));
        $this->assertFalse(Schema::hasColumn('answers', 'user_id'));
        $this->assertFalse(Schema::hasColumn('responses', 'created_at'));

        $response = Response::sole();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $response->id);
        $this->assertTrue($response->is_valid);
        $this->assertSame(count(array_filter($answers, fn ($v) => $v !== '')), Answer::count());

        $feature = ResponseFeature::sole();
        $this->assertSame(2.5, $feature->features['F_MAK']);
        $this->assertSame('2025', $feature->features['ANGKATAN']);
        $this->assertSame('1_2', $feature->features['LAMA_TINGGAL']);
        $this->assertArrayNotHasKey('KEP_UMUM', $feature->features);
        $this->assertSame(1, $feature->target);
        $this->assertSame(2, $feature->kep_umum);
        $this->assertCount(12, $feature->features);

        $this->assertDatabaseHas('survey_submissions', ['survey_id' => $survey->id, 'user_id' => $user->id]);
    }

    public function test_tidak_dapat_mengisi_dua_kali(): void
    {
        $survey = $this->activeSurvey();
        $user = $this->student();
        $answers = $this->answersFor($survey);

        $this->submitAs($user, $survey, $answers)->assertRedirect(route('survey.thanks'));
        $this->submitAs($user, $survey, $answers)->assertRedirect(route('survey.index'));
        $this->actingAs($user)->get(route('survey.consent'))->assertRedirect(route('survey.index'));

        $this->assertSame(1, Response::count());
        $this->assertSame(1, SurveySubmission::count());
    }

    public function test_pertanyaan_wajib_divalidasi(): void
    {
        $survey = $this->activeSurvey();
        $answers = $this->answersFor($survey);
        $target = $survey->questions->firstWhere('code', 'INGIN_KELUAR');
        unset($answers[$target->id]);

        $this->submitAs($this->student(), $survey, $answers)->assertSessionHasErrors("answers.{$target->id}");
        $this->assertSame(0, Response::count());
    }

    public function test_nilai_likert_di_luar_rentang_ditolak(): void
    {
        $survey = $this->activeSurvey();
        $this->submitAs($this->student(), $survey, $this->answersFor($survey, ['F_FAS_1' => 7]))->assertSessionHasErrors();
        $this->assertSame(0, Response::count());
    }

    public function test_survei_yang_sudah_ditutup_menolak_pengiriman(): void
    {
        $survey = $this->activeSurvey();
        $survey->update(['close_at' => now()->subMinute()]);

        $this->submitAs($this->student(), $survey, $this->answersFor($survey))->assertRedirect(route('survey.index'));
        $this->assertSame(0, Response::count());
    }

    public function test_respons_terlalu_cepat_dan_seragam_ditandai(): void
    {
        $survey = $this->activeSurvey();
        $answers = $this->answersFor($survey, [], 4);

        $this->submitAs($this->student(), $survey, $answers, 30)->assertRedirect(route('survey.thanks'));

        $response = Response::sole();
        $this->assertFalse($response->is_valid);
        $this->assertStringContainsString('straight_lining', $response->flag_reason);
        $this->assertStringContainsString('too_fast', $response->flag_reason);
    }
}
