<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixRecentQuestionHistoryCommand extends Command
{
    protected $signature = 'data:fix-recent-history {--dry-run : Sadece raporla, veri yazma}';

    protected $description = 'Bos cevaplar nedeniyle sisen user_recent_question_history kayitlarini yeniden olusturur.';

    public function handle(): int
    {
        $this->info('user_recent_question_history duzeltme baslatildi...');

        $aggregates = DB::table('test_items')
            ->join('tests', 'tests.id', '=', 'test_items.test_id')
            ->where('tests.status', 'finished')
            ->whereNotNull('test_items.user_answer')
            ->whereIn('test_items.user_answer', ['A', 'B', 'C', 'D', 'E'])
            ->groupBy('tests.user_id', 'test_items.question_id')
            ->selectRaw('
                tests.user_id as user_id,
                test_items.question_id as question_id,
                MAX(test_items.answered_at) as last_answered_at,
                COUNT(*) as attempt_count,
                SUM(CASE WHEN test_items.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                SUM(CASE WHEN test_items.is_correct = 0 THEN 1 ELSE 0 END) as wrong_count
            ')
            ->get();

        $targetCount = $aggregates->count();
        $currentCount = (int) DB::table('user_recent_question_history')->count();

        $this->line("Mevcut satir sayisi: {$currentCount}");
        $this->line("Hedef satir sayisi : {$targetCount}");

        if ($this->option('dry-run')) {
            $this->comment('Dry-run tamamlandi. Veri degistirilmedi.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($aggregates): void {
            DB::table('user_recent_question_history')->delete();

            foreach ($aggregates->chunk(1000) as $chunk) {
                $now = now();
                $rows = $chunk->map(function ($row) use ($now) {
                    return [
                        'user_id' => (int) $row->user_id,
                        'question_id' => (int) $row->question_id,
                        'last_answered_at' => $row->last_answered_at,
                        'attempt_count' => (int) $row->attempt_count,
                        'correct_count' => (int) $row->correct_count,
                        'wrong_count' => (int) $row->wrong_count,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->values()->all();

                if (! empty($rows)) {
                    DB::table('user_recent_question_history')->insert($rows);
                }
            }
        });

        $finalCount = (int) DB::table('user_recent_question_history')->count();
        $this->info("Duzeltme tamamlandi. Yeni satir sayisi: {$finalCount}");

        return self::SUCCESS;
    }
}

