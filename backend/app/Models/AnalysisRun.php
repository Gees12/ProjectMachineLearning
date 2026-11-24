<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisRun extends Model
{
    public $timestamps = false;

    protected $fillable = ['survey_id', 'run_by', 'model_version', 'algorithm', 'metrics', 'n_responses',
        'status', 'error_message', 'run_at'];

    protected function casts(): array
    {
        return ['metrics' => 'array', 'run_at' => 'datetime'];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(AnalysisPrediction::class);
    }

    public function shap(): HasMany
    {
        return $this->hasMany(AnalysisShap::class)->orderBy('rank');
    }

    /** True jika model dilatih dengan data dummy atau datanya belum memadai. */
    public function isExperimental(): bool
    {
        return ($this->metrics['data_source'] ?? 'dummy') !== 'real'
            || ! ($this->metrics['data_sufficient'] ?? false);
    }
}
