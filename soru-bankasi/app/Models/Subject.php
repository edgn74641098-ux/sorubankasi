<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
