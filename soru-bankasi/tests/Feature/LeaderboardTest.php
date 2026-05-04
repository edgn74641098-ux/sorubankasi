<?php

namespace Tests\Feature;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Test;
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

        foreach ([$user, $other] as $participant) {
            Test::query()->create([
                'user_id' => $participant->id,
                'subject_id' => $subject->id,
                'question_count' => 20,
                'duration_minutes' => 30,
                'score' => $participant->id === $other->id ? 95 : 75,
                'correct_count' => 15,
                'wrong_count' => 5,
                'blank_count' => 0,
                'started_at' => now()->subHour(),
                'ended_at' => now(),
                'status' => 'finished',
                'feedback_mode' => 'DELAYED_FEEDBACK',
                'aborted' => false,
            ]);
        }

        $response = $this->actingAs($user)->get(route('leaderboard.index'));

        $response->assertOk();
        $response->assertSee('Global Siralama');
        $response->assertSee('Ders Bazli Siralama');
        $response->assertSee('#2');
        $response->assertSee('Other User');
        $response->assertSee('Sen');
        $response->assertSee('Sonraki Hedef');
        $response->assertSee('Haftalik Liderler');
        $response->assertSee('En Iyi Form');
    }

    public function test_leaderboard_page_limits_public_race_lists(): void
    {
        $user = $this->createVerifiedUser('Viewer');
        $subject = Subject::query()->create([
            'name' => 'Genel Kultur',
            'slug' => 'genel-kultur',
            'is_active' => true,
        ]);
        $snapshotAt = now()->startOfMinute();

        for ($rank = 1; $rank <= 25; $rank++) {
            $participant = $this->createVerifiedUser('Global Player ' . $rank);

            LeaderboardGlobalSnapshot::query()->create([
                'user_id' => $participant->id,
                'score' => 1000 - $rank,
                'questions_total' => 100,
                'correct_total' => 80,
                'wrong_total' => 20,
                'rank' => $rank,
                'snapshot_at' => $snapshotAt,
            ]);
        }

        for ($rank = 1; $rank <= 6; $rank++) {
            $weeklyUser = $this->createVerifiedUser('Weekly Player ' . $rank);

            Test::query()->create([
                'user_id' => $weeklyUser->id,
                'subject_id' => $subject->id,
                'question_count' => 20,
                'duration_minutes' => 30,
                'score' => 300 - $rank,
                'correct_count' => 15,
                'wrong_count' => 5,
                'blank_count' => 0,
                'started_at' => now()->subHour(),
                'ended_at' => now(),
                'status' => 'finished',
                'feedback_mode' => 'DELAYED_FEEDBACK',
                'aborted' => false,
            ]);
        }

        for ($rank = 1; $rank <= 6; $rank++) {
            $formUser = $this->createVerifiedUser('Form Player ' . $rank);

            foreach ([1, 2] as $testNo) {
                Test::query()->create([
                    'user_id' => $formUser->id,
                    'subject_id' => $subject->id,
                    'question_count' => 20,
                    'duration_minutes' => 30,
                    'score' => 50 - $rank,
                    'correct_count' => 15,
                    'wrong_count' => 5,
                    'blank_count' => 0,
                    'started_at' => now()->subHours($testNo),
                    'ended_at' => now(),
                    'status' => 'finished',
                    'feedback_mode' => 'DELAYED_FEEDBACK',
                    'aborted' => false,
                ]);
            }
        }

        $response = $this->actingAs($user)->get(route('leaderboard.index'));

        $response->assertOk();
        $response->assertSee('Global Player 20');
        $response->assertDontSee('Global Player 21');
        $response->assertSee('Weekly Player 5');
        $response->assertDontSee('Weekly Player 6');
        $response->assertSee('Form Player 5');
        $response->assertDontSee('Form Player 6');
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
        $response->assertSee('Takildiklarim Testi Baslat');
        $response->assertSee('Antrenman firsati');
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
