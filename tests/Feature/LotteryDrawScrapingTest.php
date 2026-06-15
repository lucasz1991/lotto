<?php

namespace Tests\Feature;

use App\Jobs\ScrapeLotteryDraw;
use App\Models\LotteryDraw;
use App\Models\Setting;
use App\Services\Lottery\LotteryDrawScrapingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LotteryDrawScrapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_lotto_draw_can_be_scraped_from_html(): void
    {
        Http::fake([
            'https://example.test/lotto' => Http::response('<html><body>Ziehung vom 13.06.2026 Gewinnzahlen 3 12 18 22 34 49 Superzahl 7</body></html>'),
        ]);

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_LOTTO_6AUS49,
            'https://example.test/lotto',
        );

        $this->assertSame(LotteryDraw::GAME_LOTTO_6AUS49, $draw->game);
        $this->assertSame('2026-06-13', $draw->draw_date->toDateString());
        $this->assertSame([3, 12, 18, 22, 34, 49], $draw->numbers);
        $this->assertSame(7, $draw->bonus_numbers['superzahl']);
    }

    public function test_eurojackpot_draw_can_be_scraped_from_html(): void
    {
        Http::fake([
            'https://example.test/eurojackpot' => Http::response('<main>Ziehung 2026-06-12 Gewinnzahlen 8 14 27 35 42 Eurozahlen 3 11</main>'),
        ]);

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_EUROJACKPOT,
            'https://example.test/eurojackpot',
        );

        $this->assertSame(LotteryDraw::GAME_EUROJACKPOT, $draw->game);
        $this->assertSame('2026-06-12', $draw->draw_date->toDateString());
        $this->assertSame([8, 14, 27, 35, 42], $draw->numbers);
        $this->assertSame([3, 11], $draw->bonus_numbers['euro_numbers']);
    }

    public function test_scrape_job_uses_configured_url(): void
    {
        Setting::setValue('lottery', 'games', [
            LotteryDraw::GAME_LOTTO_6AUS49 => [
                'scraping_url' => 'https://example.test/lotto',
            ],
        ]);

        Http::fake([
            'https://example.test/lotto' => Http::response('Ziehung vom 13.06.2026 Gewinnzahlen 1 2 3 4 5 6 Superzahl 0'),
        ]);

        (new ScrapeLotteryDraw(LotteryDraw::GAME_LOTTO_6AUS49))->handle(app(LotteryDrawScrapingService::class));

        $draw = LotteryDraw::query()->where('game', LotteryDraw::GAME_LOTTO_6AUS49)->firstOrFail();

        $this->assertSame('2026-06-13', $draw->draw_date->toDateString());
    }
}
