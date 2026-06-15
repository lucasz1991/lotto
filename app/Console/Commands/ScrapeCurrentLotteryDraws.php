<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeLotteryDraw;
use App\Models\LotteryDraw;
use App\Services\Lottery\LotteryDrawScrapingService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ScrapeCurrentLotteryDraws extends Command
{
    protected $signature = 'lottery:scrape-current
        {--game=* : Nur diese Spielarten scrapen}
        {--sync : Direkt im Command ausfuehren statt Queue-Jobs zu dispatchen}';

    protected $description = 'Scraped die aktuellen Lotto-Ziehungsdaten fuer alle konfigurierten Spielarten.';

    public function handle(LotteryDrawScrapingService $scraper): int
    {
        $games = $this->gamesToScrape();

        foreach ($games as $game) {
            if ($this->option('sync')) {
                try {
                    $draw = $scraper->scrapeGame($game);
                } catch (Throwable $exception) {
                    $this->error((LotteryDraw::gameLabels()[$game] ?? $game).': '.$exception->getMessage());

                    return self::FAILURE;
                }

                $this->info(sprintf(
                    '%s gespeichert: %s',
                    LotteryDraw::gameLabels()[$game] ?? $game,
                    $draw->draw_date?->format('d.m.Y') ?? '-',
                ));

                continue;
            }

            ScrapeLotteryDraw::dispatch($game);
            $this->info('Scraping-Job gestartet: '.(LotteryDraw::gameLabels()[$game] ?? $game));
        }

        return self::SUCCESS;
    }

    protected function gamesToScrape(): array
    {
        $requestedGames = array_filter(array_map('trim', (array) $this->option('game')));
        $validGames = array_keys(LotteryDraw::gameLabels());

        if ($requestedGames === []) {
            return $validGames;
        }

        foreach ($requestedGames as $game) {
            if (! in_array($game, $validGames, true)) {
                throw new InvalidArgumentException('Unbekannte Spielart: '.$game);
            }
        }

        return array_values($requestedGames);
    }
}
