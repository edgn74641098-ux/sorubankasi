<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuestionReport;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use App\Models\UserSubmittedQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_settings_with_password_reconfirmation_and_audit_log(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->seedSetting('test_feedback_mode', 'DELAYED_FEEDBACK');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'test_feedback_mode' => 'NO_FEEDBACK',
            'registration_open' => '1',
            'inactive_login_message' => 'Hesabiniz pasif duruma alinmistir.',
            'email_verification_required' => '1',
            'google_auth_enabled' => '1',
            'password_reset_enabled' => '1',
            'daily_test_limit' => '15',
            'daily_question_limit' => '12',
            'login_rate_limit' => '4',
            'login_lockout_duration' => '600',
            'minimum_leaderboard_tests' => '3',
            'correct_answer_points' => '5',
            'wrong_answer_penalty_enabled' => '0',
            'wrong_answer_penalty_points' => '0',
            'blank_answer_points' => '0',
            'leaderboard_global_limit' => '20',
            'leaderboard_weekly_limit' => '5',
            'leaderboard_form_limit' => '5',
            'question_report_accept_message' => 'Itiraziniz kabul edildi. {old_answer} -> {new_answer}',
            'user_submissions_enabled' => '1',
            'submission_approval_reward' => '10',
            'submission_rejection_note_required' => '1',
            'archive_retention_days' => '7',
            'archive_auto_prune_enabled' => '1',
            'maintenance_mode' => '0',
            'backup_mode' => 'manual',
            'current_password' => 'password',
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertSame('NO_FEEDBACK', Setting::query()->where('key', 'test_feedback_mode')->first()->typed_value);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'settings.updated',
            'entity_type' => 'settings',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['success' => 'Ayarlar guncellendi.'])
            ->get(route('admin.settings.index'))
            ->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'Ayarlar guncellendi.'));
    }

    public function test_settings_page_shows_boolean_values_correctly_and_creates_missing_defaults(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->seedSetting('google_auth_enabled', false);
        $this->seedSetting('email_verification_required', false);
        $this->seedSetting('password_reset_enabled', false);

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Google ile giris')
            ->assertSee('E-posta dogrulama zorunlu')
            ->assertSee('Sifremi unuttum linki');

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<select[^>]*id="google_auth_enabled"[\s\S]*?<option value="0" selected>Kapali<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*id="email_verification_required"[\s\S]*?<option value="0" selected>Kapali<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*id="password_reset_enabled"[\s\S]*?<option value="0" selected>Kapali<\/option>/', $html);

        $this->assertDatabaseHas('settings', [
            'key' => 'leaderboard_global_limit',
            'value_type' => 'integer',
            'value' => '20',
        ]);
    }

    public function test_admin_can_change_settings_off_and_on(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'test_feedback_mode' => 'DELAYED_FEEDBACK',
            'registration_open' => '0',
            'inactive_login_message' => 'Hesabiniz gecici olarak pasif duruma alinmistir.',
            'email_verification_required' => '0',
            'google_auth_enabled' => '0',
            'password_reset_enabled' => '0',
            'daily_test_limit' => '11',
            'daily_question_limit' => '9',
            'login_rate_limit' => '3',
            'login_lockout_duration' => '300',
            'minimum_leaderboard_tests' => '2',
            'correct_answer_points' => '6',
            'wrong_answer_penalty_enabled' => '1',
            'wrong_answer_penalty_points' => '2',
            'blank_answer_points' => '1',
            'leaderboard_global_limit' => '18',
            'leaderboard_weekly_limit' => '4',
            'leaderboard_form_limit' => '4',
            'question_report_accept_message' => 'Itiraziniz kabul edildi. Yeni cevap: {new_answer}',
            'user_submissions_enabled' => '0',
            'submission_approval_reward' => '12',
            'submission_rejection_note_required' => '0',
            'archive_retention_days' => '14',
            'archive_auto_prune_enabled' => '0',
            'maintenance_mode' => '0',
            'backup_mode' => 'automatic',
            'current_password' => 'password',
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertSame(false, Setting::query()->where('key', 'registration_open')->first()->typed_value);
        $this->assertSame(false, Setting::query()->where('key', 'google_auth_enabled')->first()->typed_value);
        $this->assertSame(18, Setting::query()->where('key', 'leaderboard_global_limit')->first()->typed_value);
        $this->assertSame('automatic', Setting::query()->where('key', 'backup_mode')->first()->typed_value);
    }

    public function test_admin_can_update_user_role_and_audit_the_change(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $editorRole = Role::query()->firstOrCreate(['name' => 'editor']);

        $this->actingAs($admin)->patch(route('admin.users.update-role', $user), [
            'role_id' => $editorRole->id,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue($user->fresh()->isEditor());
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.role_updated',
            'entity_type' => 'users',
            'entity_id' => $user->id,
        ]);
    }

    public function test_admin_users_page_shows_metrics_and_filters(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['is_active' => true]);

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
        UserSubmittedQuestion::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'payload_json' => [
                'question_text' => 'Kullanici oneri metni',
                'options' => ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E'],
                'correct_option' => 'A',
                'explanation_text' => 'Aciklama',
            ],
            'status' => 'approved',
        ]);
        QuestionReport::query()->create([
            'user_id' => $user->id,
            'question_id' => Question::factory()->create([
                'subject_id' => $subject->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'status' => 'active',
                'approved_at' => now(),
            ])->id,
            'category' => 'WRONG_ANSWER',
            'suggested_correct_option' => 'B',
            'status' => 'approved',
        ]);
        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4', '10.0.0.5', '10.0.0.6'] as $index => $ip) {
            AuditLog::query()->create([
                'actor_id' => $user->id,
                'action' => 'auth.login',
                'entity_type' => 'users',
                'entity_id' => $user->id,
                'reason' => 'Kullanici giris yapti.',
                'ip_address' => $ip,
                'created_at' => now()->subMinutes(6 - $index),
                'updated_at' => now()->subMinutes(6 - $index),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'role_id' => $user->role_id,
                'email_status' => 'verified',
                'activity' => 'active_7',
            ]))
            ->assertOk()
            ->assertSee('Erisim ve Performans')
            ->assertSee('Toplam Kullanici')
            ->assertSee($user->email)
            ->assertSee('Aktif')
            ->assertSee('1 test')
            ->assertSee('20 soru')
            ->assertSee('%80.0')
            ->assertSee('Detay')
            ->assertSee('Katki Kalitesi')
            ->assertSee('1 oneri')
            ->assertSee('1 itiraz')
            ->assertSee('Son 5 Giris IP')
            ->assertSee('10.0.0.6')
            ->assertSee('10.0.0.2')
            ->assertDontSee('10.0.0.1')
            ->assertSee('Son 7 gun aktif')
            ->assertDontSee('Filtrelere uygun kullanici bulunamadi.');
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $user), [
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.status_updated',
            'entity_type' => 'users',
            'entity_id' => $user->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update-status', $user), [
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_can_delete_regular_user_but_not_admin_user(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.deleted',
            'entity_type' => 'users',
            'entity_id' => $user->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_remove_last_admin_role(): void
    {
        $admin = $this->createUserWithRole('admin');
        $userRole = Role::query()->firstOrCreate(['name' => 'user']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update-role', $admin), [
                'role_id' => $userRole->id,
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_rollback_question_to_previous_version(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create(['is_active' => true]);

        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'question_text' => 'Yeni soru metni',
            'status' => 'active',
            'current_version' => 2,
            'approved_at' => now(),
        ]);

        $version = QuestionVersion::query()->create([
            'question_id' => $question->id,
            'version_no' => 1,
            'changed_by' => $admin->id,
            'change_reason' => 'Test surumu',
            'payload_json' => [
                'subject_id' => $subject->id,
                'question_text' => 'Eski soru metni',
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'option_e' => 'E',
                'correct_option' => 'A',
                'explanation_text' => 'Eski aciklama metni',
                'difficulty_score' => 4,
                'status' => 'active',
                'approved_by' => $admin->id,
                'approved_at' => now()->toISOString(),
                'current_version' => 1,
            ],
        ]);

        $test = Test::query()->create([
            'user_id' => $admin->id,
            'subject_id' => $subject->id,
            'question_count' => 20,
            'duration_minutes' => 30,
            'started_at' => now(),
            'status' => 'active',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);
        $item = $test->items()->create(['question_id' => $question->id]);

        $this->actingAs($admin)
            ->post(route('admin.questions.versions.rollback', [$question, $version]), [
                'reason' => 'Hatali guncelleme geri alindi.',
            ])
            ->assertRedirect(route('admin.questions.versions.index', $question));

        $this->assertSame('Eski soru metni', $question->fresh()->question_text);
        $this->assertTrue($item->fresh()->rollback_applied);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'question.rollback',
            'entity_type' => 'questions',
            'entity_id' => $question->id,
        ]);
    }

    public function test_registration_can_be_closed_from_settings(): void
    {
        $this->seedSetting('registration_open', false);

        $this->get(route('register'))->assertForbidden();
    }

    public function test_daily_test_limit_blocks_new_tests(): void
    {
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['is_active' => true]);
        Question::factory()->count(20)->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $this->seedSetting('daily_test_limit', 1);
        $this->seedSetting('test_feedback_mode', 'DELAYED_FEEDBACK');

        Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 20,
            'duration_minutes' => 30,
            'started_at' => now(),
            'status' => 'finished',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        $this->actingAs($user)
            ->from(route('subjects.index'))
            ->post(route('tests.start'), [
                'subject_id' => $subject->id,
                'mode' => 'RANDOM',
            ])
            ->assertRedirect(route('subjects.index'))
            ->assertSessionHasErrors('test');
    }

    public function test_maintenance_mode_blocks_user_pages_but_allows_admin_panel(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $this->seedSetting('maintenance_mode', true);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(503);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Operasyon Merkezi');
    }

    public function test_scoring_settings_affect_finished_test_score(): void
    {
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create(['is_active' => true]);
        $questions = Question::factory()->count(20)->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'status' => 'active',
            'approved_at' => now(),
            'correct_option' => 'A',
        ]);
        $test = Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 20,
            'duration_minutes' => 30,
            'started_at' => now()->subMinutes(10),
            'status' => 'active',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        foreach ($questions as $index => $question) {
            $test->items()->create([
                'question_id' => $question->id,
                'user_answer' => match ($index) {
                    0 => 'A',
                    1 => 'B',
                    default => null,
                },
            ]);
        }

        $this->seedSetting('correct_answer_points', 4);
        $this->seedSetting('wrong_answer_penalty_enabled', true);
        $this->seedSetting('wrong_answer_penalty_points', 2);
        $this->seedSetting('blank_answer_points', 1);

        app(\App\Services\TestFinalizeService::class)->finalize($test);

        $test->refresh();
        $this->assertSame(20, (int) $test->score);
        $this->assertSame(1, (int) $test->correct_count);
        $this->assertSame(1, (int) $test->wrong_count);
        $this->assertSame(18, (int) $test->blank_count);
        $this->assertSame(20, (int) $user->fresh()->total_score);
    }

    public function test_admin_can_archive_subject_and_its_questions(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create(['is_active' => true]);
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.subjects.destroy', $subject))
            ->assertRedirect(route('admin.archive.index'));

        $subject->refresh();
        $question->refresh();

        $this->assertFalse($subject->is_active);
        $this->assertNotNull($subject->archived_at);
        $this->assertNotNull($subject->purge_after);
        $this->assertSame('archived', $question->status);
        $this->assertNotNull($question->archived_at);

        $this->actingAs($admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertDontSee($subject->name);

        $this->actingAs($admin)
            ->get(route('admin.archive.index'))
            ->assertOk()
            ->assertSee($subject->name)
            ->assertSee('Otomatik Silme');
    }

    public function test_admin_can_archive_question(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create(['is_active' => true]);
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'question_text' => 'Arsive tasinacak soru metni',
            'status' => 'active',
            'approved_at' => now(),
            'current_version' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.questions.destroy', $question))
            ->assertRedirect(route('admin.questions.index'));

        $question->refresh();

        $this->assertSame('archived', $question->status);
        $this->assertNotNull($question->archived_at);
        $this->assertNotNull($question->purge_after);
        $this->assertSame(2, (int) $question->current_version);
        $this->assertDatabaseHas('question_versions', [
            'question_id' => $question->id,
            'version_no' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.questions.index'))
            ->assertOk()
            ->assertDontSee($question->question_text);

        $this->actingAs($admin)
            ->get(route('admin.archive.index'))
            ->assertOk()
            ->assertSee($question->question_text);
    }

    public function test_admin_can_bulk_archive_questions(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create(['is_active' => true]);
        $questions = Question::factory()->count(2)->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'active',
            'approved_at' => now(),
            'current_version' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.archive-bulk'), [
                'question_ids' => $questions->pluck('id')->all(),
            ])
            ->assertRedirect(route('admin.questions.index'));

        $questions->each(function (Question $question): void {
            $question->refresh();
            $this->assertSame('archived', $question->status);
            $this->assertNotNull($question->archived_at);
        });
    }

    public function test_archive_prune_soft_deletes_expired_archive_records(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create([
            'is_active' => false,
            'archived_at' => now()->subDays(8),
            'purge_after' => now()->subDay(),
        ]);
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => null,
            'status' => 'archived',
            'archived_at' => now()->subDays(8),
            'purge_after' => now()->subDay(),
        ]);

        $this->artisan('archive:prune')
            ->expectsOutput('Archived cleanup completed. Questions removed from archive: 1, subjects removed from archive: 1.')
            ->assertExitCode(0);

        $this->assertSoftDeleted('questions', ['id' => $question->id]);
        $this->assertSoftDeleted('subjects', ['id' => $subject->id]);
    }

    public function test_admin_can_view_archive_page_from_panel(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create([
            'name' => 'Arsiv Dersi',
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'status' => 'archived',
            'question_text' => 'Arsivde gorunen soru metni',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Aksiyon Gerekenler')
            ->assertSee('24 Saatte Silinecek')
            ->assertSee(route('admin.archive.index'), false);

        $this->actingAs($admin)
            ->get(route('admin.archive.index'))
            ->assertOk()
            ->assertSee('Arsivlenen Dersler')
            ->assertSee('Arsiv Dersi')
            ->assertSee('Arsivlenen Sorular')
            ->assertSee('Arsivde gorunen soru metni');
    }

    public function test_admin_can_remove_archived_question_from_archive_without_breaking_test_history(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');
        $subject = Subject::factory()->create([
            'name' => 'Soft Arsiv Dersi',
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'approved_by' => null,
            'status' => 'archived',
            'question_text' => 'Arsivden kaldirilan soru gecmiste gorunur',
            'correct_option' => 'A',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        $test = Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 1,
            'duration_minutes' => 30,
            'score' => 5,
            'correct_count' => 1,
            'wrong_count' => 0,
            'blank_count' => 0,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'status' => 'finished',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);
        $test->items()->create([
            'question_id' => $question->id,
            'user_answer' => 'A',
            'is_correct' => true,
            'awarded_points' => 5,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.archive.questions.remove', $question))
            ->assertRedirect(route('admin.archive.index'));

        $this->assertSoftDeleted('questions', ['id' => $question->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'archive.question_removed',
            'entity_type' => 'questions',
            'entity_id' => $question->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.archive.index'))
            ->assertOk()
            ->assertDontSee('Arsivden kaldirilan soru gecmiste gorunur');

        $this->actingAs($user)
            ->get(route('tests.review', $test))
            ->assertOk()
            ->assertSee('Arsivden kaldirilan soru gecmiste gorunur');
    }

    public function test_admin_search_finds_core_records(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create([
            'name' => 'Arama Dersi',
            'is_active' => true,
        ]);
        Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'status' => 'active',
            'question_text' => 'Arama icin benzersiz soru metni',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.search', ['q' => 'Arama']))
            ->assertOk()
            ->assertSee('Dersler')
            ->assertSee('Arama Dersi')
            ->assertSee('Sorular')
            ->assertSee('Arama icin benzersiz soru metni');
    }

    public function test_admin_can_view_enhanced_audit_logs_with_filters_and_details(): void
    {
        $admin = $this->createUserWithRole('admin');
        $user = $this->createUserWithRole('user');

        AuditLog::query()->create([
            'actor_id' => $user->id,
            'action' => 'auth.login_failed',
            'entity_type' => 'auth',
            'entity_id' => $user->id,
            'new_value' => ['email' => $user->email],
            'reason' => 'Basarisiz giris denemesi.',
            'ip_address' => '10.10.10.10',
            'user_agent' => 'Feature Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        AuditLog::query()->create([
            'actor_id' => $admin->id,
            'action' => 'settings.updated',
            'entity_type' => 'settings',
            'old_value' => ['registration_open' => true],
            'new_value' => ['registration_open' => false],
            'reason' => 'Admin ayarlari guncellendi.',
            'ip_address' => '10.10.10.11',
            'user_agent' => 'Feature Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', [
                'severity' => 'security',
                'ip_address' => '10.10.10.10',
            ]))
            ->assertOk()
            ->assertSee('Guvenlik ve Operasyon Izleme')
            ->assertSee('Giris Riski')
            ->assertSee('auth.login_failed')
            ->assertSee('10.10.10.10')
            ->assertSee('Basarisiz giris denemesi.')
            ->assertSee('Detay')
            ->assertDontSee('Admin ayarlari guncellendi.');
    }

    public function test_audit_log_page_hides_regular_user_login_entries_but_keeps_admin_and_editor_logins(): void
    {
        $admin = $this->createUserWithRole('admin');
        $editor = $this->createUserWithRole('editor');
        $user = $this->createUserWithRole('user');

        foreach ([
            [$admin, 'Admin giris kaydi'],
            [$editor, 'Editor giris kaydi'],
            [$user, 'Normal kullanici giris kaydi'],
        ] as [$actor, $reason]) {
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'action' => 'auth.login',
                'entity_type' => 'users',
                'entity_id' => $actor->id,
                'reason' => $reason,
                'ip_address' => '10.10.10.'.$actor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'auth.login',
            'reason' => 'Normal kullanici giris kaydi',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['action' => 'auth.login']))
            ->assertOk()
            ->assertSee('Admin giris kaydi')
            ->assertSee('Editor giris kaydi')
            ->assertDontSee('Normal kullanici giris kaydi');
    }

    public function test_editor_dashboard_hides_admin_only_audit_link(): void
    {
        $editor = $this->createUserWithRole('editor');

        $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tekrar hos geldiniz')
            ->assertDontSee(route('admin.audit-logs.index'), false);
    }

    public function test_admin_can_filter_archive_records(): void
    {
        $admin = $this->createUserWithRole('admin');
        $math = Subject::factory()->create([
            'name' => 'Matematik Arsiv',
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        $history = Subject::factory()->create([
            'name' => 'Tarih Arsiv',
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        Question::factory()->create([
            'subject_id' => $math->id,
            'created_by' => $admin->id,
            'status' => 'archived',
            'question_text' => 'Denklem arsiv sorusu',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        Question::factory()->create([
            'subject_id' => $history->id,
            'created_by' => $admin->id,
            'status' => 'archived',
            'question_text' => 'Osmanli arsiv sorusu',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.archive.index', ['subject_search' => 'Matematik']))
            ->assertOk()
            ->assertSee('Matematik Arsiv')
            ->assertSee('<td class="fw-semibold">Matematik Arsiv</td>', false)
            ->assertDontSee('<td class="fw-semibold">Tarih Arsiv</td>', false);

        $this->actingAs($admin)
            ->get(route('admin.archive.index', [
                'question_subject_id' => $history->id,
                'question_search' => 'Osmanli',
            ]))
            ->assertOk()
            ->assertSee('Osmanli arsiv sorusu')
            ->assertDontSee('Denklem arsiv sorusu');
    }

    public function test_admin_can_restore_archived_subject_with_questions(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create([
            'name' => 'Geri Donen Ders',
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'status' => 'archived',
            'question_text' => 'Dersle geri donen soru',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.archive.subjects.restore', $subject))
            ->assertRedirect(route('admin.archive.index'));

        $subject->refresh();
        $question->refresh();

        $this->assertTrue($subject->is_active);
        $this->assertNull($subject->archived_at);
        $this->assertNull($subject->purge_after);
        $this->assertSame('inactive', $question->status);
        $this->assertNull($question->archived_at);
        $this->assertNull($question->purge_after);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'archive.subject_restored',
            'entity_type' => 'subjects',
            'entity_id' => $subject->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertSee('Geri Donen Ders');

        $this->actingAs($admin)
            ->get(route('admin.questions.index'))
            ->assertOk()
            ->assertSee('Dersle geri donen soru');
    }

    public function test_admin_can_restore_archived_question(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create([
            'name' => 'Soru Dersi',
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'status' => 'archived',
            'question_text' => 'Tekil geri alinan soru',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.archive.questions.restore', $question))
            ->assertRedirect(route('admin.archive.index'));

        $subject->refresh();
        $question->refresh();

        $this->assertTrue($subject->is_active);
        $this->assertNull($subject->archived_at);
        $this->assertSame('inactive', $question->status);
        $this->assertNull($question->archived_at);
        $this->assertNull($question->purge_after);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'archive.question_restored',
            'entity_type' => 'questions',
            'entity_id' => $question->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.questions.index'))
            ->assertOk()
            ->assertSee('Tekil geri alinan soru');
    }

    public function test_admin_can_bulk_restore_archived_subjects(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subjects = Subject::factory()->count(2)->create([
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);

        $questions = $subjects->map(fn (Subject $subject) => Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'status' => 'archived',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]));

        $this->actingAs($admin)
            ->post(route('admin.archive.subjects.restore-bulk'), [
                'subject_ids' => $subjects->pluck('id')->all(),
            ])
            ->assertRedirect(route('admin.archive.index'));

        foreach ($subjects as $subject) {
            $subject->refresh();
            $this->assertTrue($subject->is_active);
            $this->assertNull($subject->archived_at);
        }

        foreach ($questions as $question) {
            $question->refresh();
            $this->assertSame('inactive', $question->status);
            $this->assertNull($question->archived_at);
        }
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'archive.subject_restored_bulk',
            'entity_type' => 'subjects',
            'entity_id' => $subjects->first()->id,
        ]);
    }

    public function test_admin_can_bulk_restore_archived_questions(): void
    {
        $admin = $this->createUserWithRole('admin');
        $subject = Subject::factory()->create([
            'is_active' => false,
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);
        $questions = Question::factory()->count(3)->create([
            'subject_id' => $subject->id,
            'created_by' => $admin->id,
            'status' => 'archived',
            'archived_at' => now()->subDay(),
            'purge_after' => now()->addDays(6),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.archive.questions.restore-bulk'), [
                'question_ids' => $questions->take(2)->pluck('id')->all(),
            ])
            ->assertRedirect(route('admin.archive.index'));

        $subject->refresh();
        $this->assertTrue($subject->is_active);
        $this->assertNull($subject->archived_at);

        foreach ($questions->take(2) as $question) {
            $question->refresh();
            $this->assertSame('inactive', $question->status);
            $this->assertNull($question->archived_at);
        }

        $remainingQuestion = $questions->last()->fresh();
        $this->assertSame('archived', $remainingQuestion->status);
        $this->assertNotNull($remainingQuestion->archived_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'archive.question_restored_bulk',
            'entity_type' => 'questions',
            'entity_id' => $questions->first()->id,
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

    private function seedSetting(string $key, mixed $value): void
    {
        app(\App\Services\SettingsService::class)->set($key, $value);
    }
}
