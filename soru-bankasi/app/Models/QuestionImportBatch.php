<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class QuestionImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_by',
        'file_name',
        'file_type',
        'total_rows',
        'success_rows',
        'failed_rows',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(QuestionImportRow::class, 'batch_id');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(QuestionImportError::class, 'batch_id');
    }
}
