<?php

namespace App\Console\Commands;

use App\Services\LeaderboardSnapshotService;
use Illuminate\Console\Command;

class LeaderboardSnapshotCommand extends Command
{
    protected $signature = 'leaderboard:snapshot';

    protected $description = 'Calculate and store leaderboard snapshots';

    public function handle(LeaderboardSnapshotService $service): int
    {
        $service->generate();
        $this->info('Leaderboard snapshots generated.');

        return self::SUCCESS;
    }
}

