<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class QuestionImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'question_hash',
        'action',
        'matched_question_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(QuestionImportBatch::class, 'batch_id');
    }

    public function matchedQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'matched_question_id');
    }
}
