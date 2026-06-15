<?php

namespace App\Jobs;

use App\Services\Lottery\LotteryDrawScrapingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeLotteryDraw implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $game,
        public ?string $url = null,
    ) {}

    public function handle(LotteryDrawScrapingService $scraper): void
    {
        $scraper->scrapeGame($this->game, $this->url);
    }
}
