<?php

namespace Tests\Feature;

use App\Http\Controllers\Student\SubmissionController;
use App\Models\SurveySubmission;
use App\Models\User;
use Database\Seeders\SurveyTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SurveyHelpers;
use Tests\TestCase;

class StudentHomeTest extends TestCase
{
    use RefreshDatabase, SurveyHelpers;

    private function markSubmitted($survey, int $n): void
    {
        foreach (User::factory()->role('mahasiswa')->count($n)->create() as $user) {
            SurveySubmission::create(['survey_id' => $survey->id, 'user_id' => $user->id, 'submitted_date' => now()->toDateString()]);
        }
    }

    public function test_beranda_menampilkan_sisa_waktu_dan_progres_partisipasi(): void
    {
        config(['dorminsight.population' => 300, 'dorminsight.min_valid_responses' => 100]);
        $survey = $this->activeSurvey();
        $this->markSubmitted($survey, 2);

        $this->actingAs($this->student())->get(route('survey.index'))
            ->assertOk()
            ->assertSee('Isi Survei')
            ->assertSee('Ditutup dalam')
            ->assertSee('hari')
            ->assertSee('dari 300 penghuni sudah mengisi')
            ->assertSee('Masih kurang 98 lagi');
    }

    public function test_target_tercapai_ditampilkan(): void
    {
        config(['dorminsight.population' => 10, 'dorminsight.min_valid_responses' => 3]);
        $survey = $this->activeSurvey();
        $this->markSubmitted($survey, 3);

        $this->actingAs($this->student())->get(route('survey.index'))
            ->assertSee('Target minimum 3 penghuni sudah tercapai');
    }

    public function test_riwayat_partisipasi_hanya_milik_sendiri_tanpa_jawaban(): void
    {
        $survey = $this->activeSurvey();
        $me = $this->student();
        $this->submitAs($me, $survey, $this->answersFor($survey, ['SARAN' => 'Rahasia saya']))
            ->assertRedirect(route('survey.thanks'));

        $other = (new SurveyTemplateSeeder)->createSurvey('Survei Orang Lain', 'closed');
        $this->markSubmitted($other, 1);

        $this->actingAs($me)->get(route('survey.index'))
            ->assertOk()
            ->assertSee('Terima kasih, Anda sudah mengisi survei ini')
            ->assertSee('berpartisipasi di')
            ->assertSee('Survei Uji')
            ->assertDontSee('Survei Orang Lain')
            ->assertDontSee('Rahasia saya')
            ->assertDontSee('Isi Survei</a>', false);
    }

    public function test_tanpa_survei_aktif_riwayat_tetap_tampil(): void
    {
        $closed = (new SurveyTemplateSeeder)->createSurvey('Survei Semester Lalu', 'closed', now()->subMonth(), now()->subWeeks(2));
        $me = $this->student();
        SurveySubmission::create(['survey_id' => $closed->id, 'user_id' => $me->id, 'submitted_date' => now()->subWeeks(3)->toDateString()]);

        $this->actingAs($me)->get(route('survey.index'))
            ->assertOk()
            ->assertSee('Belum ada survei aktif')
            ->assertSee('Survei Semester Lalu')
            ->assertDontSee('Partisipasi penghuni');
    }

    public function test_teks_sisa_waktu(): void
    {
        $this->travelTo(now()->startOfMinute());
        $this->assertSame('3 hari 4 jam', SubmissionController::remainingText(now()->addDays(3)->addHours(4)->addMinutes(10)));
        $this->assertSame('2 hari', SubmissionController::remainingText(now()->addDays(2)->addMinutes(20)));
        $this->assertSame('5 jam 30 menit', SubmissionController::remainingText(now()->addHours(5)->addMinutes(30)));
        $this->assertSame('15 menit', SubmissionController::remainingText(now()->addMinutes(15)));
        $this->assertSame('1 menit', SubmissionController::remainingText(now()->addSeconds(20)));
        $this->assertSame('sudah ditutup', SubmissionController::remainingText(now()->subMinute()));
    }
}
