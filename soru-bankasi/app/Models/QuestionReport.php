<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'suggested_subject_id',
        'suggested_payload_json',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'user_message',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'suggested_payload_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withTrashed();
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function suggestedSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'suggested_subject_id')->withTrashed();
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'WRONG_ANSWER' => 'Yanlis Cevap',
            'UNCLEAR_WORDING' => 'Ifade Belirsizligi',
            'TYPO' => 'Yazim Hatasi',
            'WRONG_SUBJECT' => 'Yanlis Ders',
            'OTHER' => 'Diger',
            default => $this->category,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Beklemede',
            'approved' => 'Onaylandi',
            'rejected' => 'Reddedildi',
            'resolved' => 'Cozuldu',
            default => $this->status,
        };
    }
}
