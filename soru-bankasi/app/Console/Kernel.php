<?php

namespace App\Console;

use App\Services\SettingsService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('leaderboard:snapshot')->everyFiveMinutes();
        $schedule->command('queue:work --once --max-jobs=10')->everyFiveMinutes();
        $schedule->command('cleanup:audit-logs --days=90')->dailyAt('02:00');
        $schedule->command('backup:database')
            ->dailyAt('03:00')
            ->when(fn () => app(SettingsService::class)->getString('backup_mode', 'manual') === 'automatic');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
