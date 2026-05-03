<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CleanupAuditLogsCommand extends Command
{
    protected $signature = 'cleanup:audit-logs {--days=90 : Keep logs for N days}';

    protected $description = 'Delete old audit logs';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = CarbonImmutable::now()->subDays($days);

        $deleted = AuditLog::query()
            ->where('created_at', '<', $threshold)
            ->delete();

        $this->info("Deleted {$deleted} audit logs older than {$days} days.");

        return self::SUCCESS;
    }
}

