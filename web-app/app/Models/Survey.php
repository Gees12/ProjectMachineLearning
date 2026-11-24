<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Survey extends Model
{
    protected $fillable = ['title', 'description', 'open_at', 'close_at', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['open_at' => 'datetime', 'close_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function factors(): HasMany
    {
        return $this->hasMany(Factor::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(SurveySubmission::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function analysisRuns(): HasMany
    {
        return $this->hasMany(AnalysisRun::class)->orderByDesc('run_at')->orderByDesc('id');
    }

    public function latestSuccessfulRun(): HasOne
    {
        // Constraint harus di dalam ofMany agar subquery MAX(id) hanya melihat run yang sukses
        return $this->hasOne(AnalysisRun::class)->ofMany(['id' => 'max'], fn ($q) => $q->where('status', 'success'));
    }

    public static function active(): ?self
    {
        return static::where('status', 'active')->first();
    }

    public function isOpenForSubmission(): bool
    {
        return $this->status === 'active' && now()->between($this->open_at, $this->close_at);
    }

    public function hasResponses(): bool
    {
        return $this->responses()->exists();
    }

    public function targetQuestions()
    {
        return $this->questions()->where('ml_role', 'target');
    }

    public function statusLabel(): string
    {
        return ['draft' => 'Draft', 'active' => 'Aktif', 'closed' => 'Ditutup'][$this->status] ?? $this->status;
    }
}
