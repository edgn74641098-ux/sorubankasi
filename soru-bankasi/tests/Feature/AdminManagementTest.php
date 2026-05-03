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
            ->from(route('tests.create'))
            ->post(route('tests.start'), [
                'subject_id' => $subject->id,
                'mode' => 'RANDOM',
            ])
            ->assertRedirect(route('tests.create'))
            ->assertSessionHasErrors('test');
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
