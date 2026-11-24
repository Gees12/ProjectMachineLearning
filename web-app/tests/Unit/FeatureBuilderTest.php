<?php

namespace Tests\Unit;

use App\Models\Factor;
use App\Models\Question;
use App\Services\FeatureBuilder;
use PHPUnit\Framework\TestCase;

class FeatureBuilderTest extends TestCase
{
    private function q(int $id, string $code, string $type, string $role, ?string $factor = null): Question
    {
        $q = new Question(['code' => $code, 'type' => $type, 'ml_role' => $role]);
        $q->id = $id;
        $q->setRelation('factor', $factor ? new Factor(['code' => $factor]) : null);

        return $q;
    }

    public function test_skor_faktor_rata_rata_dan_kolom_terlarang_tidak_masuk_fitur(): void
    {
        $questions = collect([
            $this->q(1, 'F_MAK_1', 'likert', 'factor_item', 'F_MAK'),
            $this->q(2, 'F_MAK_2', 'likert', 'factor_item', 'F_MAK'),
            $this->q(3, 'F_BIA_1', 'likert', 'factor_item', 'F_BIA'),
            $this->q(4, 'ANGKATAN', 'single_choice', 'feature'),
            $this->q(5, 'KEP_UMUM', 'likert', 'descriptive'),
            $this->q(6, 'INGIN_KELUAR', 'yes_no', 'target'),
            $this->q(7, 'SARAN', 'text', 'open'),
        ]);

        $out = (new FeatureBuilder)->build($questions, [1 => '2', 2 => '5', 3 => 4, 4 => '2025', 5 => 3, 6 => '1', 7 => 'lebih banyak sayur']);

        $this->assertSame(['F_MAK' => 3.5, 'F_BIA' => 4.0, 'ANGKATAN' => '2025'], $out['features']);
        $this->assertSame(1, $out['target']);
        $this->assertSame(3, $out['kep_umum']);
    }

    public function test_pembulatan_dua_desimal_sama_dengan_python(): void
    {
        $questions = collect([
            $this->q(1, 'A_1', 'likert', 'factor_item', 'F_A'),
            $this->q(2, 'A_2', 'likert', 'factor_item', 'F_A'),
            $this->q(3, 'A_3', 'likert', 'factor_item', 'F_A'),
        ]);
        $out = (new FeatureBuilder)->build($questions, [1 => 1, 2 => 2, 3 => 2]);
        $this->assertSame(1.67, $out['features']['F_A']);
    }
}
