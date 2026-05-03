<?php

namespace Tests\Feature;

use App\Models\LeaderboardGlobalSnapshot;
use App\Models\LeaderboardSubjectSnapshot;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
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
            'rank' => 1,
            'snapshot_at' => $snapshotAt,
        ]);

        LeaderboardGlobalSnapshot::query()->create([
            'user_id' => $user->id,
            'score' => 120,
            'rank' => 2,
            'snapshot_at' => $snapshotAt,
        ]);

        LeaderboardSubjectSnapshot::query()->create([
            'subject_id' => $subject->id,
            'user_id' => $user->id,
            'score' => 80,
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

