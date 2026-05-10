<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserWrongQuestionStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'wrong_count',
        'consecutive_correct_count',
        'last_wrong_at',
    ];

    protected $casts = [
        'last_wrong_at' => 'datetime',
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
