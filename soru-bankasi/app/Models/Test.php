<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'question_count',
        'duration_minutes',
        'started_at',
        'ended_at',
        'score',
        'correct_count',
        'wrong_count',
        'blank_count',
        'status',
        'feedback_mode',
        'aborted',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'aborted' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TestItem::class);
    }
}
