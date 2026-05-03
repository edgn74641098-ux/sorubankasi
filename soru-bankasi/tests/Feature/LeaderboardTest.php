<?php

namespace Tests\Feature;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserWrongQuestionStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_leaderboard_page(): void
    {
        $user = $this->createVerifiedUser('Me');
        $other = $this->createVerifiedUser('Other User');
        $subject = Subject::query()->create([
            'name' => 'Matematik',
            'slug' => 'matematik',
            'is_active' => true,
        ]);

        $snapshotAt = now()->startOfMinute();

        LeaderboardGlobalSnapshot::query()->create([
            'user_id' => $other->id,
            'score' => 180,
            'questions_total' => 60,
            'correct_total' => 36,
            'wrong_total' => 24,
            'rank' => 1,
            'snapshot_at' => $snapshotAt,
        ]);

        LeaderboardGlobalSnapshot::query()->create([
            'user_id' => $user->id,
            'score' => 120,
            'questions_total' => 60,
            'correct_total' => 24,
            'wrong_total' => 36,
            'rank' => 2,
            'snapshot_at' => $snapshotAt,
        ]);

        LeaderboardSubjectSnapshot::query()->create([
            'subject_id' => $subject->id,
            'user_id' => $user->id,
            'score' => 80,
            'questions_total' => 40,
            'correct_total' => 16,
            'wrong_total' => 24,
            'rank' => 1,
            'snapshot_at' => $snapshotAt,
        ]);

        $response = $this->actingAs($user)->get(route('leaderboard.index'));

        $response->assertOk();
        $response->assertSee('Global Sıralama');
        $response->assertSee('Ders Bazlı Sıralama');
        $response->assertSee('#2');
        $response->assertSee('Other User');
    }

    public function test_leaderboard_shows_weakness_test_button_for_subject_with_wrong_questions(): void
    {
        $user = $this->createVerifiedUser('Me');
        $subject = Subject::query()->create([
            'name' => 'Geometri',
            'slug' => 'geometri',
            'is_active' => true,
        ]);

        $questions = Question::factory()->count(3)->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'status' => 'active',
            'approved_at' => now(),
            'difficulty_score' => 5,
        ]);

        foreach ($questions as $question) {
            UserWrongQuestionStat::query()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'wrong_count' => 1,
                'last_wrong_at' => now()->subDay(),
            ]);
        }

        $response = $this->actingAs($user)->get(route('leaderboard.index', ['subject_id' => $subject->id]));

        $response->assertOk();
        $response->assertSee('Takıldıklarım Testi Başlat');
        $response->assertSee('Bu derste en çok yanlış verdiğin soru kaydı');
        $response->assertSee('subject_id=' . $subject->id);
        $response->assertSee('mode=WEAKNESSES');
    }

    private function createVerifiedUser(string $name): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'user']);

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $name,
            'email_verified_at' => now(),
            'total_score' => 0,
        ]);
    }
}

