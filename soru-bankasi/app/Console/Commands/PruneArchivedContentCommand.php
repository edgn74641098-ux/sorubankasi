<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Subject;
use App\Services\SettingsService;
use Illuminate\Console\Command;

class PruneArchivedContentCommand extends Command
{
    protected $signature = 'archive:prune';

    protected $description = 'Remove archived subjects and questions from archive view after their retention period';

    public function handle(): int
    {
        if (! app(SettingsService::class)->getBool('archive_auto_prune_enabled', true)) {
            $this->info('Archived cleanup skipped. Automatic archive pruning is disabled.');

            return self::SUCCESS;
        }

        $now = now();

        $deletedQuestions = 0;
        Question::query()
            ->where('status', 'archived')
            ->whereNotNull('purge_after')
            ->where('purge_after', '<=', $now)
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
            ->whereDoesntHave('questions')
            ->chunkById(100, function ($subjects) use (&$deletedSubjects): void {
                foreach ($subjects as $subject) {
                    $subject->delete();
                    $deletedSubjects++;
                }
            });

        $this->info("Archived cleanup completed. Questions removed from archive: {$deletedQuestions}, subjects removed from archive: {$deletedSubjects}.");

        return self::SUCCESS;
    }
}
