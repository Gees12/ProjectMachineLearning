<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public $timestamps = false;

    public const TYPES = [
        'likert' => 'Skala Likert 1-5',
        'single_choice' => 'Pilihan tunggal',
        'yes_no' => 'Ya / Tidak',
        'text' => 'Teks bebas',
    ];

    public const ML_ROLES = [
        'factor_item' => 'Item faktor (dirata-rata menjadi skor faktor)',
        'feature' => 'Fitur langsung (mis. angkatan)',
        'target' => 'Label target (ingin keluar)',
        'descriptive' => 'Deskriptif saja (bukan fitur)',
        'open' => 'Jawaban terbuka',
    ];

    protected $fillable = ['survey_id', 'factor_id', 'code', 'text', 'type', 'ml_role', 'is_required', 'sort_order'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function factor(): BelongsTo
    {
        return $this->belongsTo(Factor::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }
}
