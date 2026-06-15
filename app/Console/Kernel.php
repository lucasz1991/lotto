<?php

namespace App\Console;

use App\Services\Lottery\LotteryScrapingSchedule;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $scrapingSchedule = app(LotteryScrapingSchedule::class)->settings();

        if (! $scrapingSchedule['enabled']) {
            return;
        }

        $schedule->command('lottery:scrape-current')
            ->dailyAt($scrapingSchedule['time'])
            ->days($scrapingSchedule['weekdays'])
            ->timezone(config('app.timezone'))
            ->withoutOverlapping();
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
