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

    public function test_same_draw_from_different_sources_updates_existing_entry(): void
    {
        Http::fake([
            'https://example.test/lotto-a' => Http::response('Ziehung vom 13.06.2026 Gewinnzahlen 1 2 3 4 5 6 Superzahl 0'),
            'https://example.test/lotto-b' => Http::response('Ziehung vom 13.06.2026 Gewinnzahlen 7 8 9 10 11 12 Superzahl 9'),
        ]);

        app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_LOTTO_6AUS49,
            'https://example.test/lotto-a',
        );

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_LOTTO_6AUS49,
            'https://example.test/lotto-b',
        );

        $this->assertSame(1, LotteryDraw::query()->count());
        $this->assertSame([7, 8, 9, 10, 11, 12], $draw->numbers);
        $this->assertSame(9, $draw->bonus_numbers['superzahl']);
        $this->assertSame('https://example.test/lotto-b', $draw->source_file);
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

    public function test_lotto_de_lotto_page_scans_data_below_select_fields(): void
    {
        Http::fake([
            'https://www.lotto.de/lotto-6aus49/lottozahlen' => Http::response(<<<'HTML'
<div class="Layout__content" id="seiteninhalt">
    <div class="OddsDateInput">
        <select><option value="2026-06-10">10.06. (Mittwoch)</option></select>
        <select><option value="2026">2026</option></select>
    </div>
    <div class="DrawContainer">
        <div class="WinningNumbers WinningNumbers--lotto6aus49">
            <span class="WinningNumbers__date">Ziehung vom Samstag, 13.06.2026</span>
            <div class="DrawNumbersCollection">
                <div class="DrawNumbersCollection__container">
                    <span class="LottoBall__circle" aria-label="4">4</span>
                    <span class="LottoBall__circle" aria-label="13">13</span>
                    <span class="LottoBall__circle" aria-label="16">16</span>
                    <span class="LottoBall__circle" aria-label="20">20</span>
                    <span class="LottoBall__circle" aria-label="24">24</span>
                    <span class="LottoBall__circle" aria-label="43">43</span>
                </div>
                <div class="DrawNumbersCollection__container">
                    <div class="DrawNumbersCollection__label">Superzahl</div>
                    <span class="LottoBall__circle" aria-label="8">8</span>
                </div>
            </div>
            <div class="WinningNumbers__additionalGames">
                <div class="WinningNumbersAdditionalGame"><img alt="Spiel 77 - Logo der Zusatzlotterie"><span>5 7 7 0 2 3 2</span></div>
                <div class="WinningNumbersAdditionalGame"><img alt="SUPER6 - Zusatzlotterie"><span>5 5 3 9 4 2</span></div>
            </div>
        </div>
        <div class="OddsTableContainer">
            <div class="GameAmount">Spieleinsatz: 53.177.703,60&nbsp;&euro;</div>
            <table><tbody>
                <tr><td>1</td><td>6 Richtige + SZ</td><td>0 x</td><td>unbesetzt</td></tr>
                <tr><td>2</td><td>6 Richtige</td><td>1 x</td><td>2.854.924,20&nbsp;&euro;</td></tr>
            </tbody></table>
        </div>
    </div>
</div>
HTML),
        ]);

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_LOTTO_6AUS49,
            'https://www.lotto.de/lotto-6aus49/lottozahlen',
        );

        $this->assertSame('2026-06-13', $draw->draw_date->toDateString());
        $this->assertSame([4, 13, 16, 20, 24, 43], $draw->numbers);
        $this->assertSame(8, $draw->bonus_numbers['superzahl']);
        $this->assertSame('5770232', $draw->bonus_numbers['spiel77']);
        $this->assertSame('553942', $draw->bonus_numbers['super6']);
        $this->assertSame(5317770360, $draw->stake_cents);
        $this->assertSame(1, $draw->prize_classes[1]['winners']);
        $this->assertSame(285492420, $draw->prize_classes[1]['quote_cents']);
        $this->assertSame('scrape', $draw->raw_data['source']);
    }

    public function test_lotto_de_eurojackpot_page_scans_data_below_select_fields(): void
    {
        Http::fake([
            'https://www.lotto.de/eurojackpot/zahlen' => Http::response(<<<'HTML'
<div class="Layout__content" id="seiteninhalt">
    <div class="OddsDateInput">
        <select><option value="2026-06-05">05.06. (Freitag)</option></select>
        <select><option value="2026">2026</option></select>
    </div>
    <div class="DrawContainer">
        <div class="WinningNumbers WinningNumbers--eurojackpot">
            <span class="WinningNumbers__date">Ziehung vom Freitag, 12.06.2026</span>
            <div class="DrawNumbersCollection">
                <div class="DrawNumbersCollection__container">
                    <span class="LottoBall__circle" aria-label="2">2</span>
                    <span class="LottoBall__circle" aria-label="4">4</span>
                    <span class="LottoBall__circle" aria-label="14">14</span>
                    <span class="LottoBall__circle" aria-label="18">18</span>
                    <span class="LottoBall__circle" aria-label="28">28</span>
                </div>
                <div class="DrawNumbersCollection__container">
                    <div class="DrawNumbersCollection__label">Eurozahlen</div>
                    <span class="LottoBall__circle" aria-label="9">9</span>
                    <span class="LottoBall__circle" aria-label="11">11</span>
                </div>
            </div>
        </div>
        <div class="OddsTableContainer">
            <div class="GameAmount">Spieleinsatz: 42.621.542,00&nbsp;&euro;</div>
            <table><tbody>
                <tr><td>1</td><td>5 Richtige + 2 Eurozahlen</td><td>--</td><td>--</td></tr>
                <tr><td>2</td><td>5 Richtige + 1 Eurozahl</td><td>4 x</td><td>452.853,80&nbsp;&euro;</td></tr>
            </tbody></table>
        </div>
    </div>
</div>
HTML),
        ]);

        $draw = app(LotteryDrawScrapingService::class)->scrapeGame(
            LotteryDraw::GAME_EUROJACKPOT,
            'https://www.lotto.de/eurojackpot/zahlen',
        );

        $this->assertSame('2026-06-12', $draw->draw_date->toDateString());
        $this->assertSame([2, 4, 14, 18, 28], $draw->numbers);
        $this->assertSame([9, 11], $draw->bonus_numbers['euro_numbers']);
        $this->assertSame(4262154200, $draw->stake_cents);
        $this->assertSame(4, $draw->prize_classes[1]['winners']);
        $this->assertSame(45285380, $draw->prize_classes[1]['quote_cents']);
        $this->assertSame('scrape', $draw->raw_data['source']);
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
