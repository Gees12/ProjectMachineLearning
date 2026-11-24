<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Primary key gabungan (response_id, question_id); disimpan dengan insert massal. */
class Answer extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'response_id';

    protected $keyType = 'string';

    protected $fillable = ['response_id', 'question_id', 'value_numeric', 'value_text'];

    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
