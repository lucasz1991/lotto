<?php

namespace Tests\Feature;

use App\Jobs\ScrapeLotteryDraw;
use App\Livewire\Admin\Config\SettingsPage;
use App\Models\LotteryDraw;
use App\Models\Setting;
use App\Services\Lottery\LotteryScrapingSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class LotteryScheduledScrapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrape_current_command_dispatches_jobs_for_all_games(): void
    {
        Bus::fake();

        $this->artisan('lottery:scrape-current')
            ->assertExitCode(0);

        Bus::assertDispatched(ScrapeLotteryDraw::class, fn (ScrapeLotteryDraw $job): bool => $job->game === LotteryDraw::GAME_LOTTO_6AUS49);
        Bus::assertDispatched(ScrapeLotteryDraw::class, fn (ScrapeLotteryDraw $job): bool => $job->game === LotteryDraw::GAME_EUROJACKPOT);
    }

    public function test_scrape_current_command_can_dispatch_one_game(): void
    {
        Bus::fake();

        $this->artisan('lottery:scrape-current', ['--game' => [LotteryDraw::GAME_EUROJACKPOT]])
            ->assertExitCode(0);

        Bus::assertDispatched(ScrapeLotteryDraw::class, 1);
        Bus::assertDispatched(ScrapeLotteryDraw::class, fn (ScrapeLotteryDraw $job): bool => $job->game === LotteryDraw::GAME_EUROJACKPOT);
    }

    public function test_settings_page_can_store_scraping_schedule(): void
    {
        Livewire::test(SettingsPage::class)
            ->set('scrapingScheduleEnabled', true)
            ->set('scrapingScheduleTime', '21:30')
            ->set('scrapingScheduleWeekdays', ['2', '5'])
            ->call('saveGameSettings')
            ->assertHasNoErrors();

        $settings = Setting::getValue('lottery', 'scraping_schedule');

        $this->assertTrue($settings['enabled']);
        $this->assertSame('21:30', $settings['time']);
        $this->assertSame([2, 5], $settings['weekdays']);
        $this->assertSame('Dienstag, Freitag um 21:30 Uhr', app(LotteryScrapingSchedule::class)->summary($settings));
    }
}
