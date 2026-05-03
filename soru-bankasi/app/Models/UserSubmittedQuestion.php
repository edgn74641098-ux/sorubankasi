<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserSubmittedQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'payload_json',
        'status',
        'approved_question_id',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'approved_question_id');
    }
}
