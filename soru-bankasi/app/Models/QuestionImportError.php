<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class QuestionImportError extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'row_number',
        'error_message',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(QuestionImportBatch::class, 'batch_id');
    }
}
