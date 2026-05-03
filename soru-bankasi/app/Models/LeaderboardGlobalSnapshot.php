<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LeaderboardGlobalSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'score',
        'questions_total',
        'correct_total',
        'wrong_total',
        'rank',
        'snapshot_at',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
