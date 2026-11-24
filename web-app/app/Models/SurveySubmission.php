<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hanya mencatat bahwa seorang mahasiswa SUDAH mengisi survei.
 * Tidak menyimpan dan tidak berelasi dengan jawaban.
 */
class SurveySubmission extends Model
{
    public $timestamps = false;

    protected $fillable = ['survey_id', 'user_id', 'submitted_date'];

    protected function casts(): array
    {
        return ['submitted_date' => 'date'];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
