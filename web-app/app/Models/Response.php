<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Respons survei ANONIM: UUID acak, tanpa user_id, tanpa timestamp presisi.
 */
class Response extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['survey_id', 'duration_seconds', 'submitted_date', 'is_valid', 'flag_reason'];

    protected function casts(): array
    {
        return ['submitted_date' => 'date', 'is_valid' => 'boolean'];
    }

    /** UUID v4 acak (bukan UUID berurutan waktu) agar urutan tidak bisa dicocokkan. */
    public function newUniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid();
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function feature(): HasOne
    {
        return $this->hasOne(ResponseFeature::class);
    }
}
