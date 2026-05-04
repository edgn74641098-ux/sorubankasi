<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubmittedQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSubmissionModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_creates_active_question_and_rewards_user(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['is_active' => true]);
        $submission = $this->createPendingSubmission($user, $subject);

        $this->actingAs($admin)
            ->post(route('admin.submissions.approve', $submission))
            ->assertRedirect();

        $submission->refresh();

        $this->assertSame('approved', $submission->status);
        $this->assertNotNull($submission->approved_question_id);
        $this->assertSame(10, (int) $user->fresh()->total_score);
        $this->assertDatabaseHas('questions', [
            'id' => $submission->approved_question_id,
            'source_type' => 'user_submission',
            'status' => 'active',
            'question_text' => 'Kullanici tarafindan onerilen soru metni',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user_submission.approved',
            'entity_type' => 'user_submitted_questions',
            'entity_id' => $submission->id,
        ]);
    }

    public function test_admin_can_view_modern_pending_submissions_page(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['name' => 'Adli Bilisim', 'is_active' => true]);
        $this->createPendingSubmission($user, $subject);

        $this->actingAs($admin)
            ->get(route('admin.submissions.pending'))
            ->assertOk()
            ->assertSee('Moderasyon Kuyrugu')
            ->assertSee('Bekleyen Oneriler')
            ->assertSee('Kullanici tarafindan onerilen soru metni')
            ->assertSee('Dogru cevap')
            ->assertSee('Onayla')
            ->assertSee('Reddet');
    }

    public function test_admin_can_revoke_approved_submission_reward_and_deactivate_question(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['is_active' => true]);
        $submission = $this->createPendingSubmission($user, $subject);

        $this->actingAs($admin)->post(route('admin.submissions.approve', $submission));
        $submission->refresh();

        $this->actingAs($admin)
            ->post(route('admin.submissions.revoke', $submission), [
                'reason' => 'Soru sonradan hatali bulundu.',
            ])
            ->assertRedirect();

        $submission->refresh();

        $this->assertSame('rejected', $submission->status);
        $this->assertSame(0, (int) $user->fresh()->total_score);
        $this->assertDatabaseHas('questions', [
            'id' => $submission->approved_question_id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user_submission.approval_revoked',
            'entity_type' => 'user_submitted_questions',
            'entity_id' => $submission->id,
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        $role = Role::query()->firstOrCreate(['name' => $role]);

        return User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'total_score' => 0,
        ]);
    }

    private function createPendingSubmission(User $user, Subject $subject): UserSubmittedQuestion
    {
        return UserSubmittedQuestion::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'payload_json' => [
                'question_text' => 'Kullanici tarafindan onerilen soru metni',
                'options' => [
                    'A' => 'Birinci secenek',
                    'B' => 'Ikinci secenek',
                    'C' => 'Ucuncu secenek',
                    'D' => 'Dorduncu secenek',
                    'E' => 'Besinci secenek',
                ],
                'correct_option' => 'A',
                'explanation_text' => 'Bu soru icin yeterli uzunlukta aciklama metni.',
            ],
            'status' => 'pending',
        ]);
    }
}
