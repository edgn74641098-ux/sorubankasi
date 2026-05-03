<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'created_by',
        'approved_by',
        'source_type',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'option_e',
        'correct_option',
        'explanation_text',
        'difficulty_score',
        'correct_count',
        'wrong_count',
        'status',
        'approved_at',
        'current_version',
    ];

    protected $casts = [
        'difficulty_score' => 'decimal:1',
        'approved_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function testItems(): HasMany
    {
        return $this->hasMany(TestItem::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class);
    }

    public function wrongQuestionStats(): HasMany
    {
        return $this->hasMany(UserWrongQuestionStat::class);
    }

    public function recentQuestionHistory(): HasMany
    {
        return $this->hasMany(UserRecentQuestionHistory::class);
    }

    public function importRows(): HasMany
    {
        return $this->hasMany(QuestionImportRow::class, 'matched_question_id');
    }
}
