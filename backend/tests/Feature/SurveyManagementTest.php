<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\User;
use Database\Seeders\SurveyTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SurveyHelpers;
use Tests\TestCase;

class SurveyManagementTest extends TestCase
{
    use RefreshDatabase, SurveyHelpers;

    private function admin(): User
    {
        return User::factory()->role('admin')->create();
    }

    public function test_survei_tanpa_target_tidak_dapat_dibuka(): void
    {
        $survey = (new SurveyTemplateSeeder)->createSurvey('Uji', 'draft');
        $survey->questions()->where('ml_role', 'target')->delete();

        $this->actingAs($this->admin())->patch(route('admin.surveys.open', $survey))->assertSessionHas('error');
        $this->assertSame('draft', $survey->fresh()->status);
    }

    public function test_hanya_satu_survei_aktif(): void
    {
        $this->activeSurvey();
        $draft = (new SurveyTemplateSeeder)->createSurvey('Uji 2', 'draft');

        $this->actingAs($this->admin())->patch(route('admin.surveys.open', $draft))->assertSessionHas('error');
        $this->assertSame(1, Survey::where('status', 'active')->count());
    }

    public function test_hanya_satu_pertanyaan_target(): void
    {
        $survey = (new SurveyTemplateSeeder)->createSurvey('Uji', 'draft');

        $this->actingAs($this->admin())->post(route('admin.surveys.questions.store', $survey), [
            'code' => 'TARGET_2', 'text' => 'Target kedua?', 'type' => 'yes_no', 'ml_role' => 'target',
            'is_required' => 1, 'sort_order' => 99,
        ])->assertSessionHasErrors('ml_role');
    }

    public function test_pertanyaan_terkunci_setelah_ada_respons(): void
    {
        $survey = $this->activeSurvey();
        $this->submitAs($this->student(), $survey, $this->answersFor($survey));
        $question = $survey->questions()->first();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.surveys.questions.update', [$survey, $question]), [
            'code' => $question->code, 'text' => 'Diubah', 'type' => 'likert', 'ml_role' => 'factor_item',
            'factor_id' => $question->factor_id, 'is_required' => 1, 'sort_order' => 1,
        ])->assertSessionHas('error');

        $this->assertNotSame('Diubah', $question->fresh()->text);
        $this->actingAs($admin)->delete(route('admin.surveys.questions.destroy', [$survey, $question]))->assertSessionHas('error');
        $this->assertNotNull($question->fresh());
    }

    public function test_duplikasi_menyalin_faktor_pertanyaan_dan_opsi(): void
    {
        $survey = (new SurveyTemplateSeeder)->createSurvey('Asli', 'closed');

        $this->actingAs($this->admin())->post(route('admin.surveys.duplicate', $survey))->assertRedirect();

        $copy = Survey::where('title', 'Asli (Salinan)')->sole();
        $this->assertSame('draft', $copy->status);
        $this->assertSame($survey->factors()->count(), $copy->factors()->count());
        $this->assertSame($survey->questions()->count(), $copy->questions()->count());
        $this->assertSame(4, $copy->questions()->where('code', 'ANGKATAN')->first()->options()->count());
        $this->assertNotNull($copy->questions()->where('code', 'F_MAK_1')->first()->factor_id);
    }

    public function test_impor_csv_mahasiswa(): void
    {
        $csv = "name,nim,email\nAni,105001,ani@x.test\nBudi,105002,budi@x.test\nDuplikat,105001,dup@x.test\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('mhs.csv', $csv);

        $this->actingAs($this->admin())->post(route('admin.users.import'), ['file' => $file])
            ->assertSessionHas('success')->assertSessionHas('import_errors');

        $this->assertSame(2, User::where('role', 'mahasiswa')->count());
        $ani = User::firstWhere('nim', '105001');
        $this->assertTrue($ani->must_change_password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('105001', $ani->password));
    }
}
