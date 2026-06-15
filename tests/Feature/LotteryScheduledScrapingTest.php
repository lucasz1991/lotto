<?php

namespace Tests\Feature;

use App\Jobs\ScrapeLotteryDraw;
use App\Models\LotteryDraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
}
