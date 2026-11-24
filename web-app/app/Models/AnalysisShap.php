<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisShap extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'analysis_shap';

    protected $primaryKey = 'analysis_run_id';

    protected $fillable = ['analysis_run_id', 'feature_code', 'mean_abs_shap', 'direction', 'rank', 'top3_frequency'];

    protected function casts(): array
    {
        return ['mean_abs_shap' => 'float', 'top3_frequency' => 'float', 'rank' => 'integer'];
    }
}
