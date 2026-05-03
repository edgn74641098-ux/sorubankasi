<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'question_id',
        'user_answer',
        'is_correct',
        'answered_at',
        'awarded_points',
        'rollback_applied',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
        'rollback_applied' => 'boolean',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
