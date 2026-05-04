<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if ($user->role_id !== null) {
                return;
            }

            $user->role_id = Role::query()->firstOrCreate([
                'name' => 'user',
            ])->id;
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'google_id',
        'avatar_url',
        'password',
        'total_score',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function subjectStats(): HasMany
    {
        return $this->hasMany(UserSubjectStat::class);
    }

    public function wrongQuestionStats(): HasMany
    {
        return $this->hasMany(UserWrongQuestionStat::class);
    }

    public function recentQuestionHistory(): HasMany
    {
        return $this->hasMany(UserRecentQuestionHistory::class);
    }

    public function createdQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function approvedQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'approved_by');
    }

    public function submittedQuestions(): HasMany
    {
        return $this->hasMany(UserSubmittedQuestion::class);
    }

    public function questionReports(): HasMany
    {
        return $this->hasMany(QuestionReport::class);
    }

    public function reviewedSubmittedQuestions(): HasMany
    {
        return $this->hasMany(UserSubmittedQuestion::class, 'reviewed_by');
    }

    public function changedQuestionVersions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class, 'changed_by');
    }

    public function uploadedQuestionImportBatches(): HasMany
    {
        return $this->hasMany(QuestionImportBatch::class, 'uploaded_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function leaderboardGlobalSnapshots(): HasMany
    {
        return $this->hasMany(LeaderboardGlobalSnapshot::class);
    }

    public function leaderboardSubjectSnapshots(): HasMany
    {
        return $this->hasMany(LeaderboardSubjectSnapshot::class);
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role?->name === 'editor';
    }

    public function isUser(): bool
    {
        return $this->role?->name === 'user';
    }
}
