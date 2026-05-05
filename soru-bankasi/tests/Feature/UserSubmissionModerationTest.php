<?php

namespace Tests\Feature;

use App\Models\Question;
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
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'active',
            'approved_at' => now(),
            'question_text' => 'Gereksiz oldugu bildirilen soru metni',
            'option_a' => 'Birinci cevap',
            'option_b' => 'Ikinci cevap',
            'option_c' => 'Ucuncu cevap',
            'option_d' => 'Dorduncu cevap',
            'option_e' => 'Besinci cevap',
            'correct_option' => 'C',
        ]);
        $this->createPendingUnnecessaryReport($user, $question);

        $this->actingAs($admin)
            ->get(route('admin.submissions.pending'))
            ->assertOk()
            ->assertSee('Moderasyon Kuyrugu')
            ->assertSee('Bekleyen Oneriler')
            ->assertSee('Kullanici tarafindan onerilen soru metni')
            ->assertSee('Gereksiz soru bildirimi')
            ->assertSee('Gereksiz oldugu bildirilen soru metni')
            ->assertSee('Soru Sıklari')
            ->assertSee('Ucuncu cevap')
            ->assertSee('Dogru cevap')
            ->assertSee('Raporlama Nedeni')
            ->assertSee('Bu soru gereksiz olarak raporlandi.')
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

    public function test_admin_approval_of_unnecessary_question_report_archives_question_without_reward(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['is_active' => true]);
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'active',
            'approved_at' => now(),
            'current_version' => 1,
        ]);
        $submission = $this->createPendingUnnecessaryReport($user, $question);

        $this->actingAs($admin)
            ->post(route('admin.submissions.approve', $submission), [
                'review_note' => 'Soru havuzundan kaldirilmasi uygundur.',
            ])
            ->assertRedirect();

        $submission->refresh();
        $question->refresh();

        $this->assertSame('approved', $submission->status);
        $this->assertSame($question->id, $submission->approved_question_id);
        $this->assertSame(0, (int) $user->fresh()->total_score);
        $this->assertSame('archived', $question->status);
        $this->assertNull($question->approved_by);
        $this->assertNotNull($question->archived_at);
        $this->assertNotNull($question->purge_after);
        $this->assertSame(2, (int) $question->current_version);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user_submission.unnecessary_question_approved',
            'entity_type' => 'user_submitted_questions',
            'entity_id' => $submission->id,
        ]);
    }

    public function test_admin_rejection_of_unnecessary_question_report_keeps_question_active(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['is_active' => true]);
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $submission = $this->createPendingUnnecessaryReport($user, $question);

        $this->actingAs($admin)
            ->post(route('admin.submissions.reject', $submission), [
                'review_note' => 'Soru havuzunda kalmasi uygundur.',
            ])
            ->assertRedirect();

        $submission->refresh();
        $question->refresh();

        $this->assertSame('rejected', $submission->status);
        $this->assertSame('active', $question->status);
        $this->assertNull($question->archived_at);
    }

    public function test_submission_settings_control_availability_limit_reward_and_rejection_note(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['is_active' => true]);

        $this->seedSetting('user_submissions_enabled', false);
        $this->actingAs($user)
            ->get(route('questions.create'))
            ->assertForbidden();

        $this->seedSetting('user_submissions_enabled', true);
        $this->seedSetting('daily_question_limit', 1);
        $this->createPendingSubmission($user, $subject);

        $this->actingAs($user)
            ->post(route('questions.store'), $this->validSubmissionPayload($subject))
            ->assertSessionHasErrors('submission');

        $this->seedSetting('submission_approval_reward', 25);
        $rewardedSubmission = $this->createPendingSubmission($user, $subject);
        $this->actingAs($admin)
            ->post(route('admin.submissions.approve', $rewardedSubmission))
            ->assertRedirect();

        $this->assertSame(25, (int) $user->fresh()->total_score);

        $this->seedSetting('submission_rejection_note_required', false);
        $optionalNoteSubmission = $this->createPendingSubmission($user, $subject);
        $this->actingAs($admin)
            ->post(route('admin.submissions.reject', $optionalNoteSubmission), [
                'review_note' => null,
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $optionalNoteSubmission->fresh()->status);
        $this->assertNull($optionalNoteSubmission->fresh()->review_note);
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
            'submission_type' => 'question_suggestion',
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

    private function createPendingUnnecessaryReport(User $user, Question $question): UserSubmittedQuestion
    {
        return UserSubmittedQuestion::query()->create([
            'user_id' => $user->id,
            'subject_id' => $question->subject_id,
            'submission_type' => 'unnecessary_question_report',
            'reported_question_id' => $question->id,
            'payload_json' => [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'options' => [
                    'A' => $question->option_a,
                    'B' => $question->option_b,
                    'C' => $question->option_c,
                    'D' => $question->option_d,
                    'E' => $question->option_e,
                ],
                'correct_option' => $question->correct_option,
                'reason' => 'Bu soru gereksiz olarak raporlandi.',
            ],
            'status' => 'pending',
        ]);
    }

    private function validSubmissionPayload(Subject $subject): array
    {
        return [
            'subject_id' => $subject->id,
            'question_text' => 'Kullanici tarafindan limit testi icin onerilen yeterli uzunlukta soru metni',
            'option_a' => 'Birinci secenek',
            'option_b' => 'Ikinci secenek',
            'option_c' => 'Ucuncu secenek',
            'option_d' => 'Dorduncu secenek',
            'option_e' => 'Besinci secenek',
            'correct_option' => 'A',
            'explanation_text' => 'Bu soru icin yeterli uzunlukta ve gecerli bir aciklama metni.',
        ];
    }

    private function seedSetting(string $key, mixed $value): void
    {
        app(\App\Services\SettingsService::class)->set($key, $value);
    }
}
