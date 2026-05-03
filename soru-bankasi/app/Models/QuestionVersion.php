<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class QuestionVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'version_no',
        'changed_by',
        'change_reason',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
