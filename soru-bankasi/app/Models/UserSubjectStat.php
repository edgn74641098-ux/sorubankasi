<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class UserSubjectStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'solved_count',
        'correct_count',
        'wrong_count',
        'blank_count',
        'total_score',
        'last_test_at',
    ];

    protected $casts = [
        'last_test_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
