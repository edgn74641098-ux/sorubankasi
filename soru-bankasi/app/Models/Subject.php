<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'archived_at',
        'purge_after',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
        'purge_after' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function userSubjectStats(): HasMany
    {
        return $this->hasMany(UserSubjectStat::class);
    }

    public function submittedQuestions(): HasMany
    {
        return $this->hasMany(UserSubmittedQuestion::class);
    }

    public function leaderboardSnapshots(): HasMany
    {
        return $this->hasMany(LeaderboardSubjectSnapshot::class);
    }
}
