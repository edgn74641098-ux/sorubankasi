<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Subject;
use Illuminate\Console\Command;

class PruneArchivedContentCommand extends Command
{
    protected $signature = 'archive:prune';

    protected $description = 'Permanently delete archived subjects and questions after their retention period';

    public function handle(): int
    {
        $now = now();

        $deletedQuestions = 0;
        Question::query()
            ->where('status', 'archived')
            ->whereNotNull('purge_after')
            ->where('purge_after', '<=', $now)
            ->whereDoesntHave('testItems')
            ->chunkById(100, function ($questions) use (&$deletedQuestions): void {
                foreach ($questions as $question) {
                    $question->delete();
                    $deletedQuestions++;
                }
            });

        $deletedSubjects = 0;
        Subject::query()
            ->whereNotNull('archived_at')
            ->whereNotNull('purge_after')
            ->where('purge_after', '<=', $now)
            ->whereDoesntHave('tests')
            ->whereDoesntHave('questions')
            ->chunkById(100, function ($subjects) use (&$deletedSubjects): void {
                foreach ($subjects as $subject) {
                    $subject->delete();
                    $deletedSubjects++;
                }
            });

        $this->info("Archived cleanup completed. Questions deleted: {$deletedQuestions}, subjects deleted: {$deletedSubjects}.");

        return self::SUCCESS;
    }
}
