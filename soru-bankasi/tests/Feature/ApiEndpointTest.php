<?php

namespace Tests\Feature;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_api_can_read_subjects_and_leaderboards(): void
    {
        $user = User::factory()->create(['name' => 'Api User', 'email_verified_at' => now()]);
        $other = User::factory()->create(['name' => 'Ranked User', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['name' => 'Matematik', 'slug' => 'matematik']);

        Question::factory()
            ->count(2)
            ->for($subject)
            ->create(['status' => 'active']);

        $snapshotAt = now()->startOfMinute();
        LeaderboardGlobalSnapshot::query()->create([
            'user_id' => $other->id,
            'score' => 240,
            'questions_total' => 60,
            'correct_total' => 48,
            'wrong_total' => 12,
            'rank' => 1,
            'snapshot_at' => $snapshotAt,
        ]);
        LeaderboardSubjectSnapshot::query()->create([
            'subject_id' => $subject->id,
            'user_id' => $user->id,
            'score' => 120,
            'questions_total' => 40,
            'correct_total' => 24,
            'wrong_total' => 16,
            'rank' => 2,
            'snapshot_at' => $snapshotAt,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/subjects')
            ->assertOk()
            ->assertJsonPath('0.name', 'Matematik')
            ->assertJsonPath('0.active_questions_count', 2);

        $this->getJson("/api/subjects/{$subject->id}")
            ->assertOk()
            ->assertJsonPath('slug', 'matematik')
            ->assertJsonPath('active_questions_count', 2);

        $this->getJson('/api/leaderboard')
            ->assertOk()
            ->assertJsonPath('rows.0.user_name', 'Ranked User')
            ->assertJsonPath('rows.0.rank', 1)
            ->assertJsonPath('rows.0.questions_total', 60)
            ->assertJsonPath('rows.0.correct_total', 48)
            ->assertJsonPath('rows.0.wrong_total', 12)
            ->assertJsonPath('my_rank', null);

        $this->getJson("/api/leaderboard/subject/{$subject->id}")
            ->assertOk()
            ->assertJsonPath('subject.name', 'Matematik')
            ->assertJsonPath('my_rank.rank', 2)
            ->assertJsonPath('my_rank.questions_total', 40)
            ->assertJsonPath('my_rank.correct_total', 24)
            ->assertJsonPath('my_rank.wrong_total', 16);
    }

    public function test_authenticated_api_can_update_profile_and_manage_submissions(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['name' => 'Fen', 'slug' => 'fen']);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->postJson('/api/questions/submit', [
            'subject_id' => $subject->id,
            'question_text' => 'Bu API uzerinden gonderilen yeterince uzun bir soru metnidir?',
            'option_a' => 'Birinci secenek',
            'option_b' => 'Ikinci secenek',
            'option_c' => 'Ucuncu secenek',
            'option_d' => 'Dorduncu secenek',
            'option_e' => 'Besinci secenek',
            'correct_option' => 'A',
            'explanation_text' => 'Bu aciklama API testinde minimum uzunlugu karsilayan bir metindir.',
        ])
            ->assertCreated()
            ->assertJsonPath('status', 'pending');

        $this->getJson('/api/questions/submissions')
            ->assertOk()
            ->assertJsonPath('data.0.subject.name', 'Fen')
            ->assertJsonPath('data.0.status', 'pending');

        $this->patchJson('/api/profile', [
            'name' => 'Yeni Isim',
            'email' => 'yeni@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('name', 'Yeni Isim')
            ->assertJsonPath('email', 'yeni@example.com')
            ->assertJsonPath('email_verified_at', null);
    }
}
