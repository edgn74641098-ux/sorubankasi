<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestItem;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/** @var User|null $admin */
$admin = User::query()->where('email', 'admin@sorubank.com')->first();
if (! $admin) {
    (new AdminSeeder())->run();
    $admin = User::query()->where('email', 'admin@sorubank.com')->first();
}

if (! $admin) {
    fwrite(STDERR, "Admin kullanicisi olusturulamadi.\n");
    exit(1);
}

$subjects = Subject::query()
    ->where('is_active', true)
    ->whereNull('deleted_at')
    ->orderBy('id')
    ->get();

if ($subjects->isEmpty()) {
    fwrite(STDERR, "Aktif ders bulunamadi.\n");
    exit(1);
}

$totalQuestionsCreated = 0;
$totalTestsCreated = 0;
$testsPerDay = max(1, min(3, $subjects->count()));

DB::transaction(static function () use (
    $admin,
    $subjects,
    $testsPerDay,
    &$totalQuestionsCreated,
    &$totalTestsCreated
): void {
    foreach ($subjects as $subject) {
        for ($i = 1; $i <= 100; $i++) {
            Question::query()->create([
                'subject_id' => $subject->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'source_type' => 'admin',
                'question_text' => $subject->name." demo soru {$i} - ".fake()->sentence(8),
                'option_a' => 'Secenek A - '.fake()->words(3, true),
                'option_b' => 'Secenek B - '.fake()->words(3, true),
                'option_c' => 'Secenek C - '.fake()->words(3, true),
                'option_d' => 'Secenek D - '.fake()->words(3, true),
                'option_e' => 'Secenek E - '.fake()->words(3, true),
                'correct_option' => fake()->randomElement(['A', 'B', 'C', 'D', 'E']),
                'explanation_text' => 'Aciklama: '.fake()->sentence(12),
                'difficulty_score' => (float) fake()->numberBetween(1, 10),
                'correct_count' => 0,
                'wrong_count' => 0,
                'status' => 'active',
                'approved_at' => now(),
                'current_version' => 1,
            ]);
            $totalQuestionsCreated++;
        }
    }

    for ($daysAgo = 14; $daysAgo >= 0; $daysAgo--) {
        $day = Carbon::today()->subDays($daysAgo);
        $dayStart = $day->copy()->setTime(9, 0, 0);

        for ($testIndex = 0; $testIndex < $testsPerDay; $testIndex++) {
            $subject = $subjects[($daysAgo + $testIndex) % $subjects->count()];
            $questionPool = Question::query()
                ->where('subject_id', $subject->id)
                ->where('status', 'active')
                ->inRandomOrder()
                ->limit(20)
                ->get();

            if ($questionPool->count() < 20) {
                continue;
            }

            $startedAt = $dayStart->copy()->addMinutes($testIndex * 40);
            $endedAt = $startedAt->copy()->addMinutes(fake()->numberBetween(12, 32));

            $correct = 0;
            $wrong = 0;
            $blank = 0;

            $test = Test::query()->create([
                'user_id' => $admin->id,
                'subject_id' => $subject->id,
                'question_count' => 20,
                'duration_minutes' => 30,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'score' => 0,
                'correct_count' => 0,
                'wrong_count' => 0,
                'blank_count' => 0,
                'status' => 'finished',
                'feedback_mode' => 'DELAYED_FEEDBACK',
                'aborted' => false,
                'created_at' => $startedAt,
                'updated_at' => $endedAt,
            ]);

            foreach ($questionPool as $question) {
                $roll = fake()->numberBetween(1, 100);
                $userAnswer = null;
                $isCorrect = null;
                $awardedPoints = 0;

                if ($roll <= 62) {
                    $userAnswer = $question->correct_option;
                    $isCorrect = true;
                    $awardedPoints = 5;
                    $correct++;
                } elseif ($roll <= 92) {
                    $wrongChoices = collect(['A', 'B', 'C', 'D', 'E'])
                        ->reject(static fn ($choice) => $choice === $question->correct_option)
                        ->values();
                    $userAnswer = $wrongChoices->random();
                    $isCorrect = false;
                    $wrong++;
                } else {
                    $blank++;
                }

                TestItem::query()->create([
                    'test_id' => $test->id,
                    'question_id' => $question->id,
                    'user_answer' => $userAnswer,
                    'is_correct' => $isCorrect,
                    'answered_at' => $userAnswer ? $startedAt->copy()->addMinutes(fake()->numberBetween(1, 29)) : null,
                    'awarded_points' => $awardedPoints,
                    'rollback_applied' => false,
                    'created_at' => $endedAt,
                    'updated_at' => $endedAt,
                ]);
            }

            $score = (int) round(($correct / 20) * 100);
            $test->update([
                'score' => $score,
                'correct_count' => $correct,
                'wrong_count' => $wrong,
                'blank_count' => $blank,
            ]);

            $totalTestsCreated++;
        }
    }
});

echo "Tamamlandi.\n";
echo "Admin: {$admin->email} (id={$admin->id})\n";
echo "Eklenen soru: {$totalQuestionsCreated}\n";
echo "Eklenen test: {$totalTestsCreated}\n";

