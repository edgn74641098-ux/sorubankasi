<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserRecentQuestionHistory extends Model
{
    use HasFactory;

    protected $table = 'user_recent_question_history';

    protected $fillable = [
        'user_id',
        'question_id',
        'last_answered_at',
        'attempt_count',
        'correct_count',
        'wrong_count',
    ];

    protected $casts = [
        'last_answered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
