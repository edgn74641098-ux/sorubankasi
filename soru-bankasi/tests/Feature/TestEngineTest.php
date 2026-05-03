<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
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
            ->from(route('tests.create'))
            ->post(route('tests.start'), [
                'subject_id' => $subject->id,
                'mode' => 'RANDOM',
            ])
            ->assertRedirect(route('tests.create'));

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
            ->from(route('tests.create'))
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
}