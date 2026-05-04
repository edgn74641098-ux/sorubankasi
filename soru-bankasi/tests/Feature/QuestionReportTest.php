<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\QuestionReportAcceptedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuestionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_report_question_from_web_flow(): void
    {
        $user = $this->userWithRole('user');
        $question = Question::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->from('/tests/1')
            ->post(route('questions.report'), [
                'question_id' => $question->id,
                'category' => 'WRONG_ANSWER',
                'suggested_correct_option' => 'B',
                'note' => 'Cevap anahtari hatali gorunuyor.',
            ])
            ->assertRedirect('/tests/1')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('question_reports', [
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category' => 'WRONG_ANSWER',
            'suggested_correct_option' => 'B',
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_pending_report_is_not_created(): void
    {
        $user = $this->userWithRole('user');
        $question = Question::factory()->create(['status' => 'active']);

        QuestionReport::query()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category' => 'TYPO',
            'suggested_correct_option' => 'A',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/reports', [
            'question_id' => $question->id,
            'category' => 'TYPO',
            'suggested_correct_option' => 'A',
        ])
            ->assertStatus(409);

        $this->assertDatabaseCount('question_reports', 1);
    }

    public function test_editor_can_list_and_review_pending_reports_over_api(): void
    {
        $reporter = $this->userWithRole('user');
        $editor = $this->userWithRole('editor');
        $subject = Subject::factory()->create(['name' => 'Matematik']);
        $question = Question::factory()->for($subject)->create(['status' => 'active']);

        $report = QuestionReport::query()->create([
            'user_id' => $reporter->id,
            'question_id' => $question->id,
            'category' => 'UNCLEAR_WORDING',
            'suggested_correct_option' => 'C',
            'note' => 'Soru kokunde belirsizlik var.',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($editor);

        $this->getJson('/api/reports/pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $report->id)
            ->assertJsonPath('data.0.question.subject', 'Matematik');

        $this->postJson("/api/reports/{$report->id}/review", [
            'action' => 'approved',
            'review_note' => 'Soru duzenlenecek.',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('question_reports', [
            'id' => $report->id,
            'status' => 'approved',
            'reviewed_by' => $editor->id,
        ]);
    }

    public function test_editor_can_view_and_review_reports_from_admin_panel(): void
    {
        $reporter = $this->userWithRole('user');
        $editor = $this->userWithRole('editor');
        $subject = Subject::factory()->create(['name' => 'Turkce']);
        $question = Question::factory()->for($subject)->create([
            'question_text' => 'Bu soru admin panelinde gorunmelidir.',
            'status' => 'active',
        ]);

        $report = QuestionReport::query()->create([
            'user_id' => $reporter->id,
            'question_id' => $question->id,
            'category' => 'TYPO',
            'suggested_correct_option' => 'D',
            'note' => 'Yazim hatasi var.',
            'status' => 'pending',
        ]);

        $this->actingAs($editor)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Itirazlar')
            ->assertSee('Bu soru admin panelinde gorunmelidir.')
            ->assertSee('Yazim hatasi var.');

        $this->actingAs($editor)
            ->post(route('admin.reports.reject', $report), [
                'review_note' => 'Bildirim incelendi, soru dogru.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('question_reports', [
            'id' => $report->id,
            'status' => 'rejected',
            'reviewed_by' => $editor->id,
        ]);
    }

    public function test_approved_report_updates_question_answer_and_notifies_reporter(): void
    {
        Notification::fake();

        $reporter = $this->userWithRole('user');
        $editor = $this->userWithRole('editor');
        $question = Question::factory()->create([
            'correct_option' => 'A',
            'current_version' => 3,
            'status' => 'active',
        ]);

        $report = QuestionReport::query()->create([
            'user_id' => $reporter->id,
            'question_id' => $question->id,
            'category' => 'WRONG_ANSWER',
            'suggested_correct_option' => 'C',
            'note' => 'Dogru cevap C olmali.',
            'status' => 'pending',
        ]);

        $this->actingAs($editor)
            ->post(route('admin.reports.approve', $report), [
                'review_note' => 'Kontrol edildi, C dogru.',
            ])
            ->assertRedirect();

        $question->refresh();
        $report->refresh();

        $this->assertSame('C', $question->correct_option);
        $this->assertSame(4, (int) $question->current_version);
        $this->assertSame('approved', $report->status);
        $this->assertStringContainsString('tesekkur', $report->user_message);

        $this->assertDatabaseHas('question_versions', [
            'question_id' => $question->id,
            'version_no' => 3,
            'changed_by' => $editor->id,
        ]);

        Notification::assertSentTo($reporter, QuestionReportAcceptedNotification::class);
    }

    public function test_user_can_track_own_question_reports(): void
    {
        $user = $this->userWithRole('user');
        $otherUser = $this->userWithRole('user');
        $subject = Subject::factory()->create(['name' => 'Fen']);
        $question = Question::factory()->for($subject)->create([
            'question_text' => 'Kullanici kendi itirazini bu ekranda gorebilmelidir.',
            'correct_option' => 'B',
            'status' => 'active',
        ]);

        QuestionReport::query()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category' => 'WRONG_ANSWER',
            'suggested_correct_option' => 'D',
            'note' => 'Bence cevap D.',
            'status' => 'approved',
            'reviewed_at' => now(),
            'user_message' => 'Itiraziniz kabul edildi. Tesekkur ederiz.',
        ]);

        QuestionReport::query()->create([
            'user_id' => $otherUser->id,
            'question_id' => $question->id,
            'category' => 'TYPO',
            'suggested_correct_option' => 'A',
            'note' => 'Baskasinin itirazi.',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('questions.reports'))
            ->assertOk()
            ->assertSee('Itirazlarim')
            ->assertSee('Kullanici kendi itirazini bu ekranda gorebilmelidir.')
            ->assertSee('Itiraziniz kabul edildi. Tesekkur ederiz.')
            ->assertDontSee('Baskasinin itirazi.');
    }

    public function test_api_user_can_track_own_question_reports(): void
    {
        $user = $this->userWithRole('user');
        $question = Question::factory()->create([
            'question_text' => 'API takip sorusu',
            'correct_option' => 'E',
            'status' => 'active',
        ]);

        QuestionReport::query()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category' => 'WRONG_ANSWER',
            'suggested_correct_option' => 'C',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/reports/mine')
            ->assertOk()
            ->assertJsonPath('data.0.question.text', 'API takip sorusu')
            ->assertJsonPath('data.0.suggested_correct_option', 'C')
            ->assertJsonPath('data.0.status', 'pending');
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }
}
