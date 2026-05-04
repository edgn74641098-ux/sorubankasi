<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use App\Models\UserRecentQuestionHistory;
use App\Models\UserWrongQuestionStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TestEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_random_test_and_answer_then_finish(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Matematik',
            'slug' => 'matematik',
            'is_active' => true,
        ]);

        $this->seedQuestions($subject, $user, 20);
        $this->seedFeedbackMode('DELAYED_FEEDBACK');

        $response = $this->actingAs($user)->post(route('tests.start'), [
            'subject_id' => $subject->id,
            'mode' => 'RANDOM',
        ]);

        $response->assertRedirect();
        $test = Test::query()->first();

        $this->assertNotNull($test);
        $this->assertCount(20, $test->items);

        $item = $test->items()->with('question')->first();

        $this->actingAs($user)->post(route('tests.answer', $test), [
            'test_item_id' => $item->id,
            'answer' => $item->question->correct_option,
            'current_index' => 1,
            'action' => 'stay',
        ])->assertRedirect();

        $this->actingAs($user)
            ->post(route('tests.finish', $test))
            ->assertRedirect(route('tests.review', $test));

        $this->assertDatabaseHas('tests', [
            'id' => $test->id,
            'status' => 'finished',
        ]);
    }

    public function test_mastered_questions_are_deprioritized_when_generating_random_tests(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Programlama',
            'slug' => 'programlama',
            'is_active' => true,
        ]);

        $regularQuestions = $this->seedQuestions($subject, $user, 20);
        $masteredQuestions = $this->seedQuestions($subject, $user, 5);
        $this->seedFeedbackMode('DELAYED_FEEDBACK');

        foreach ($masteredQuestions as $question) {
            UserRecentQuestionHistory::query()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'last_answered_at' => Carbon::now()->subDay(),
                'attempt_count' => 5,
                'correct_count' => 4,
                'wrong_count' => 1,
            ]);
        }

        $response = $this->actingAs($user)->post(route('tests.start'), [
            'subject_id' => $subject->id,
            'mode' => 'RANDOM',
        ]);

        $response->assertRedirect();

        $test = Test::query()->with('items')->firstOrFail();
        $selectedQuestionIds = $test->items->pluck('question_id');

        $this->assertCount(20, $test->items);
        $this->assertEqualsCanonicalizing($regularQuestions->pluck('id')->all(), $selectedQuestionIds->all());
        $this->assertEmpty($selectedQuestionIds->intersect($masteredQuestions->pluck('id'))->all());
    }

    public function test_start_test_page_shows_visual_test_center(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Matematik',
            'slug' => 'matematik',
            'is_active' => true,
        ]);

        $questions = $this->seedQuestions($subject, $user, 20);

        UserWrongQuestionStat::query()->create([
            'user_id' => $user->id,
            'question_id' => $questions->first()->id,
            'wrong_count' => 1,
            'last_wrong_at' => now()->subDay(),
        ]);

        Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 20,
            'duration_minutes' => 30,
            'score' => 75,
            'correct_count' => 15,
            'wrong_count' => 5,
            'blank_count' => 0,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'status' => 'finished',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        $this->actingAs($user)
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee('Dersini sec, teste basla')
            ->assertSee('Aktif soru havuzu')
            ->assertSee('Test Ayarlari')
            ->assertSee('Rastgele')
            ->assertSee('Zorluk Araligi')
            ->assertSee('Takildiklarim')
            ->assertSeeText('1 takildigim soru')
            ->assertSeeText('Basarim: %75.0')
            ->assertSee('weakQuestionModeCount', false)
            ->assertDontSee('20 soru, 1 takildigim');
    }

    public function test_old_start_test_route_redirects_to_subjects_page(): void
    {
        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->get(route('tests.create', ['mode' => 'WEAKNESSES']))
            ->assertRedirect(route('subjects.index', ['mode' => 'WEAKNESSES']));
    }

    public function test_start_test_page_highlights_active_test(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Turkce',
            'slug' => 'turkce',
            'is_active' => true,
        ]);

        Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 20,
            'duration_minutes' => 30,
            'started_at' => now(),
            'status' => 'active',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        $this->actingAs($user)
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee('Devam eden testiniz var')
            ->assertSee('Aktif Teste Git');
    }

    public function test_user_cannot_start_test_when_active_test_exists(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Türkçe',
            'slug' => 'turkce',
            'is_active' => true,
        ]);

        $this->seedQuestions($subject, $user, 20);
        $this->seedFeedbackMode('DELAYED_FEEDBACK');

        Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 20,
            'duration_minutes' => 30,
            'started_at' => now(),
            'status' => 'active',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        $this->actingAs($user)
            ->from(route('subjects.index'))
            ->post(route('tests.start'), [
                'subject_id' => $subject->id,
                'mode' => 'RANDOM',
            ])
            ->assertRedirect(route('subjects.index'));

        $this->assertDatabaseCount('tests', 1);
    }

    public function test_weakness_mode_can_be_completed_with_random_fallback(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Fen Bilimleri',
            'slug' => 'fen-bilimleri',
            'is_active' => true,
        ]);

        $questions = $this->seedQuestions($subject, $user, 20);
        $this->seedFeedbackMode('DELAYED_FEEDBACK');

        foreach ($questions->take(10) as $question) {
            UserWrongQuestionStat::query()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'wrong_count' => 1,
                'last_wrong_at' => Carbon::now()->subDays(5),
            ]);
        }

        $response = $this->actingAs($user)
            ->from(route('subjects.index'))
            ->post(route('tests.start'), [
                'subject_id' => $subject->id,
                'mode' => 'WEAKNESSES',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');

        $this->assertDatabaseCount('tests', 1);
    }

    public function test_expired_test_is_auto_finalized_on_show(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Sosyal Bilgiler',
            'slug' => 'sosyal-bilgiler',
            'is_active' => true,
        ]);

        $questions = $this->seedQuestions($subject, $user, 20);
        $this->seedFeedbackMode('DELAYED_FEEDBACK');

        $test = Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 20,
            'duration_minutes' => 30,
            'started_at' => Carbon::now()->subMinutes(31),
            'status' => 'active',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        foreach ($questions as $question) {
            $test->items()->create([
                'question_id' => $question->id,
            ]);
        }

        $this->actingAs($user)
            ->get(route('tests.show', $test))
            ->assertRedirect(route('tests.review', $test));

        $this->assertDatabaseHas('tests', [
            'id' => $test->id,
            'status' => 'finished',
        ]);
    }

    public function test_review_page_shows_answer_option_texts(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Bilisim',
            'slug' => 'bilisim',
            'is_active' => true,
        ]);

        $question = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'source_type' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
            'option_d' => 'Apple Pages',
            'option_e' => 'Faraday cantasi',
            'correct_option' => 'E',
        ]);

        $test = Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 1,
            'duration_minutes' => 30,
            'started_at' => now()->subMinutes(10),
            'ended_at' => now(),
            'score' => 0,
            'correct_count' => 0,
            'wrong_count' => 1,
            'blank_count' => 0,
            'status' => 'finished',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        $test->items()->create([
            'question_id' => $question->id,
            'user_answer' => 'D',
            'is_correct' => false,
            'answered_at' => now(),
            'awarded_points' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('tests.review', $test))
            ->assertOk()
            ->assertSee('Verilen cevap: D - Apple Pages')
            ->assertSee('Dogru cevap: E - Faraday cantasi')
            ->assertSee('Itiraz Et')
            ->assertSee('suggested_correct_option', false);

        $this->actingAs($user)
            ->from(route('tests.review', $test))
            ->post(route('questions.report'), [
                'question_id' => $question->id,
                'category' => 'WRONG_ANSWER',
                'suggested_correct_option' => 'D',
                'note' => 'Bu soru icin dogru cevabin D oldugunu dusunuyorum.',
            ])
            ->assertRedirect(route('tests.review', $test))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('question_reports', [
            'user_id' => $user->id,
            'question_id' => $question->id,
            'category' => 'WRONG_ANSWER',
            'suggested_correct_option' => 'D',
            'status' => 'pending',
        ]);
    }

    public function test_question_difficulty_is_recalculated_from_correct_and_wrong_answers(): void
    {
        $user = $this->createVerifiedUser();
        $subject = Subject::query()->create([
            'name' => 'Algoritma',
            'slug' => 'algoritma',
            'is_active' => true,
        ]);

        $easyQuestion = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'source_type' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
            'difficulty_score' => 5,
            'correct_count' => 20,
            'wrong_count' => 0,
            'correct_option' => 'A',
        ]);

        $hardQuestion = Question::factory()->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'source_type' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
            'difficulty_score' => 5,
            'correct_count' => 0,
            'wrong_count' => 20,
            'correct_option' => 'A',
        ]);

        $easyTest = $this->createActiveTestWithQuestion($user, $subject, $easyQuestion, 'A');
        $hardTest = $this->createActiveTestWithQuestion($user, $subject, $hardQuestion, 'B');

        $this->actingAs($user)->post(route('tests.finish', $easyTest))->assertRedirect(route('tests.review', $easyTest));
        $this->actingAs($user)->post(route('tests.finish', $hardTest))->assertRedirect(route('tests.review', $hardTest));

        $easyQuestion->refresh();
        $hardQuestion->refresh();

        $this->assertSame(21, $easyQuestion->correct_count);
        $this->assertSame(0, $easyQuestion->wrong_count);
        $this->assertLessThan(5, (float) $easyQuestion->difficulty_score);
        $this->assertDatabaseHas('user_recent_question_history', [
            'user_id' => $user->id,
            'question_id' => $easyQuestion->id,
            'attempt_count' => 1,
            'correct_count' => 1,
            'wrong_count' => 0,
        ]);

        $this->assertSame(0, $hardQuestion->correct_count);
        $this->assertSame(21, $hardQuestion->wrong_count);
        $this->assertGreaterThan(5, (float) $hardQuestion->difficulty_score);
        $this->assertGreaterThan((float) $easyQuestion->difficulty_score, (float) $hardQuestion->difficulty_score);
        $this->assertDatabaseHas('user_recent_question_history', [
            'user_id' => $user->id,
            'question_id' => $hardQuestion->id,
            'attempt_count' => 1,
            'correct_count' => 0,
            'wrong_count' => 1,
        ]);
    }

    private function createVerifiedUser(): User
    {
        $role = Role::query()->create(['name' => 'user']);

        return User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'total_score' => 0,
        ]);
    }

    private function seedQuestions(Subject $subject, User $user, int $count)
    {
        return Question::factory()->count($count)->create([
            'subject_id' => $subject->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'source_type' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
            'difficulty_score' => 5,
        ]);
    }

    private function seedFeedbackMode(string $mode): void
    {
        $setting = Setting::query()->firstOrNew(['key' => 'test_feedback_mode']);
        $setting->setTypedValue($mode);
        $setting->save();
    }

    private function createActiveTestWithQuestion(User $user, Subject $subject, Question $question, string $answer): Test
    {
        $test = Test::query()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'question_count' => 1,
            'duration_minutes' => 30,
            'started_at' => now(),
            'status' => 'active',
            'feedback_mode' => 'DELAYED_FEEDBACK',
            'aborted' => false,
        ]);

        $test->items()->create([
            'question_id' => $question->id,
            'user_answer' => $answer,
            'answered_at' => now(),
        ]);

        return $test;
    }
}
