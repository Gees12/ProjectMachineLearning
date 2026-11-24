<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseFeature extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'response_id';

    protected $keyType = 'string';

    protected $fillable = ['response_id', 'survey_id', 'features', 'target', 'kep_umum'];

    protected function casts(): array
    {
        return ['features' => 'array', 'target' => 'integer', 'kep_umum' => 'integer'];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class);
    }
}
