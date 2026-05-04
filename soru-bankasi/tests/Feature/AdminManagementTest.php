<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
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
            'daily_test_limit' => '15',
            'daily_question_limit' => '12',
            'login_rate_limit' => '4',
            'login_lockout_duration' => '600',
            'minimum_leaderboard_tests' => '3',
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
            ->assertRedirect(route('admin.archive.index'));

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

    public function test_archive_prune_deletes_expired_unreferenced_records(): void
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
            ->expectsOutput('Archived cleanup completed. Questions deleted: 1, subjects deleted: 1.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
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
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->setTypedValue($value);
        $setting->save();
    }
}
