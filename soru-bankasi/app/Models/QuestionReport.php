<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class QuestionReport extends Model
{
    use HasFactory;

    protected $table = 'question_reports';

    protected $fillable = [
        'user_id',
        'question_id',
        'category',
        'note',
        'suggested_correct_option',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'user_message',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'WRONG_ANSWER' => 'Yanlış Cevap',
            'UNCLEAR_WORDING' => 'İfade Belirsizliği',
            'TYPO' => 'Yazım Hatası',
            'OTHER' => 'Diğer',
            default => $this->category,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Beklemede',
            'approved' => 'Onaylandı',
            'rejected' => 'Reddedildi',
            'resolved' => 'Çözüldü',
            default => $this->status,
        };
    }
}
