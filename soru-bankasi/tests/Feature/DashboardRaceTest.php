<?php

namespace Tests\Feature;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use App\Models\UserWrongQuestionStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_race_summary_cards(): void
    {
        $user = $this->createVerifiedUser('Ben');
        $rival = $this->createVerifiedUser('Yakin Rakip');
        $subject = Subject::factory()->create(['name' => 'Matematik']);
        $snapshotAt = now()->startOfMinute();

        LeaderboardGlobalSnapshot::query()->create([
            'user_id' => $rival->id,
            'score' => 260,
            'questions_total' => 60,
            'correct_total' => 52,
            'wrong_total' => 8,
            'rank' => 1,
            'snapshot_at' => $snapshotAt,
        ]);

        LeaderboardGlobalSnapshot::query()->create([
            'user_id' => $user->id,
            'score' => 240,
            'questions_total' => 60,
            'correct_total' => 48,
            'wrong_total' => 12,
            'rank' => 2,
            'snapshot_at' => $snapshotAt,
        ]);

        LeaderboardSubjectSnapshot::query()->create([
            'subject_id' => $subject->id,
            'user_id' => $user->id,
            'score' => 180,
            'questions_total' => 40,
            'correct_total' => 36,
            'wrong_total' => 4,
            'rank' => 1,
            'snapshot_at' => $snapshotAt,
        ]);

        Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 20,
            'duration_minutes' => 30,
            'score' => 80,
            'correct_count' => 16,
            'wrong_count' => 4,
            'blank_count' => 0,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'status' => 'finished',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);
        UserWrongQuestionStat::query()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'wrong_count' => 3,
            'last_wrong_at' => now(),
        ]);
        QuestionReport::query()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category' => 'TYPO',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Yarisa hazir misin?')
            ->assertSee('Global Siran')
            ->assertSee('#2')
            ->assertSee('Yakin Rakip')
            ->assertSee('21')
            ->assertSee('Bu Hafta')
            ->assertSee('Performans Grafigi')
            ->assertSee('Son 30 gunde cozdunuz test')
            ->assertSee('80.0')
            ->assertSee('Bugunun hedefi')
            ->assertSee('Zirve Panosu')
            ->assertSee('Ilk 3')
            ->assertSee('En iyi ders yarisin')
            ->assertSee('Matematik')
            ->assertSee('Zayif Ders Onerisi')
            ->assertSee('Itiraz Durumu')
            ->assertSee('1 bekleyen');
    }

    public function test_dashboard_handles_user_without_leaderboard_rank(): void
    {
        $user = $this->createVerifiedUser('Yeni Kullanici');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Listeye gir')
            ->assertSee('Ilk hedef: liste')
            ->assertSee('Bugunun hedefi');
    }

    public function test_dashboard_paginates_recent_tests_by_ten(): void
    {
        $user = $this->createVerifiedUser('Cok Test Cozen');
        $subject = Subject::factory()->create(['name' => 'Tarih']);

        foreach (range(1, 12) as $index) {
            Test::query()->create([
                'user_id' => $user->id,
                'subject_id' => $subject->id,
                'question_count' => 20,
                'duration_minutes' => 30,
                'score' => 80,
                'correct_count' => 16,
                'wrong_count' => 4,
                'blank_count' => 0,
                'started_at' => now()->subDays($index)->subMinutes(30),
                'ended_at' => now()->subDays($index),
                'status' => 'finished',
                'feedback_mode' => 'DELAYED_FEEDBACK',
                'aborted' => false,
            ]);
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Son Testler')
            ->assertSee('12 test')
            ->assertSee('recent_tests_page=2', false);
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
