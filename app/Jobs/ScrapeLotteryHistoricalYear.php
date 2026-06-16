<?php

namespace App\Jobs;

use App\Services\Lottery\LotteryDrawScrapingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeLotteryHistoricalYear implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(
        public int $year,
        public array $games,
        public array $urls = [],
    ) {}

    public function handle(LotteryDrawScrapingService $scraper): void
    {
        $scraper->scrapeHistoricalYearForGames($this->year, $this->games, $this->urls);
    }
}
