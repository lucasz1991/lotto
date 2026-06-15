<?php

namespace Tests\Feature;

use App\Jobs\ScrapeLotteryDraw;
use App\Livewire\Admin\Config\SettingsPage;
use App\Models\LotteryDraw;
use App\Models\Setting;
use App\Services\Lottery\LotteryDrawScrapingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LotteryDrawScrapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_lotto_draw_can_be_scraped_from_html(): void
    {
        Http::fake([
            'https://www.lotto.de/lotto-6aus49/lottozahlen' => Http::response('<html><body>Ziehung vom 13.06.2026 Gewinnzahlen 3 12 18 22 34 49 Superzahl 7</body></html>'),
        ]);

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_LOTTO_6AUS49,
            'https://www.lotto.de/lotto-6aus49/lottozahlen',
        );

        $this->assertSame(LotteryDraw::GAME_LOTTO_6AUS49, $draw->game);
        $this->assertSame('2026-06-13', $draw->draw_date->toDateString());
        $this->assertSame([3, 12, 18, 22, 34, 49], $draw->numbers);
        $this->assertSame(7, $draw->bonus_numbers['superzahl']);
    }

    public function test_eurojackpot_draw_can_be_scraped_from_html(): void
    {
        Http::fake([
            'https://www.lotto.de/eurojackpot/zahlen' => Http::response('<main>Ziehung 2026-06-12 Gewinnzahlen 8 14 27 35 42 Eurozahlen 3 11</main>'),
        ]);

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_EUROJACKPOT,
            'https://www.lotto.de/eurojackpot/zahlen',
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
                'scraping_url' => 'https://www.lotto.de/lotto-6aus49/lottozahlen',
            ],
        ]);

        Http::fake([
            'https://www.lotto.de/lotto-6aus49/lottozahlen' => Http::response('Ziehung vom 13.06.2026 Gewinnzahlen 1 2 3 4 5 6 Superzahl 0'),
        ]);

        (new ScrapeLotteryDraw(LotteryDraw::GAME_LOTTO_6AUS49))->handle(app(LotteryDrawScrapingService::class));

        $draw = LotteryDraw::query()->where('game', LotteryDraw::GAME_LOTTO_6AUS49)->firstOrFail();

        $this->assertSame('2026-06-13', $draw->draw_date->toDateString());
    }

    public function test_lotto_de_lotto_page_uses_lotto_de_api_and_stores_latest_draw(): void
    {
        Http::fake([
            'https://www.lotto.de/api/stats/entities.lotto/last/game/1' => Http::response([
                'drawDate' => $this->lottoDeTimestamp('2026-06-10'),
                'drawNumbersCollection' => [
                    ['drawNumber' => 1, 'index' => 1],
                    ['drawNumber' => 2, 'index' => 2],
                    ['drawNumber' => 3, 'index' => 3],
                    ['drawNumber' => 4, 'index' => 4],
                    ['drawNumber' => 5, 'index' => 5],
                    ['drawNumber' => 6, 'index' => 6],
                ],
                'superNumber' => 0,
            ]),
            'https://www.lotto.de/api/stats/entities.lotto/last/game/2' => Http::response([
                'drawDate' => $this->lottoDeTimestamp('2026-06-13'),
                'drawNumbersCollection' => [
                    ['drawNumber' => 16, 'index' => 1],
                    ['drawNumber' => 13, 'index' => 2],
                    ['drawNumber' => 4, 'index' => 3],
                    ['drawNumber' => 20, 'index' => 4],
                    ['drawNumber' => 24, 'index' => 5],
                    ['drawNumber' => 43, 'index' => 6],
                ],
                'superNumber' => 8,
            ]),
        ]);

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_LOTTO_6AUS49,
            'https://www.lotto.de/lotto-6aus49/lottozahlen',
        );

        $this->assertSame('2026-06-13', $draw->draw_date->toDateString());
        $this->assertSame([16, 13, 4, 20, 24, 43], $draw->numbers);
        $this->assertSame(8, $draw->bonus_numbers['superzahl']);
        $this->assertSame('lotto.de-api', $draw->raw_data['source']);
    }

    public function test_lotto_de_eurojackpot_page_uses_lotto_de_api(): void
    {
        Http::fake([
            'https://www.lotto.de/api/stats/entities.eurojackpot/last' => Http::response([
                'drawDate' => $this->lottoDeTimestamp('2026-06-12'),
                'drawNumbersCollection' => [
                    ['drawNumber' => 2, 'drawNumberType' => 0, 'index' => 0],
                    ['drawNumber' => 28, 'drawNumberType' => 0, 'index' => 1],
                    ['drawNumber' => 18, 'drawNumberType' => 0, 'index' => 2],
                    ['drawNumber' => 4, 'drawNumberType' => 0, 'index' => 3],
                    ['drawNumber' => 14, 'drawNumberType' => 0, 'index' => 4],
                    ['drawNumber' => 9, 'drawNumberType' => 1, 'index' => 5],
                    ['drawNumber' => 11, 'drawNumberType' => 1, 'index' => 6],
                ],
            ]),
        ]);

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_EUROJACKPOT,
            'https://www.lotto.de/eurojackpot/zahlen',
        );

        $this->assertSame('2026-06-12', $draw->draw_date->toDateString());
        $this->assertSame([2, 28, 18, 4, 14], $draw->numbers);
        $this->assertSame([9, 11], $draw->bonus_numbers['euro_numbers']);
        $this->assertSame('lotto.de-api', $draw->raw_data['source']);
    }

    public function test_settings_page_can_run_direct_scrape_and_show_result(): void
    {
        Http::fake([
            'https://www.lotto.de/lotto-6aus49/lottozahlen' => Http::response('Ziehung vom 13.06.2026 Gewinnzahlen 1 2 3 4 5 6 Superzahl 0'),
        ]);

        Livewire::test(SettingsPage::class)
            ->set('lottoScrapingUrl', 'https://www.lotto.de/lotto-6aus49/lottozahlen')
            ->call('testScrapeGame', LotteryDraw::GAME_LOTTO_6AUS49)
            ->assertSet('lastScrapeResult.game', 'Lotto 6aus49')
            ->assertSet('lastScrapeResult.draw_date', '13.06.2026')
            ->assertSet('lastScrapeResult.numbers', '1 - 2 - 3 - 4 - 5 - 6')
            ->assertSet('lastScrapeResult.bonus_numbers', 'Superzahl: 0');

        $this->assertDatabaseHas('lottery_draws', [
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'source_file' => 'https://www.lotto.de/lotto-6aus49/lottozahlen',
        ]);
    }

    private function lottoDeTimestamp(string $date): int
    {
        return CarbonImmutable::parse($date, config('app.timezone'))->startOfDay()->getTimestamp() * 1000;
    }
}
