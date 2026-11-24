<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisPrediction extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'analysis_run_id';

    protected $fillable = ['analysis_run_id', 'response_id', 'probability', 'risk_level'];

    protected function casts(): array
    {
        return ['probability' => 'float'];
    }
}
