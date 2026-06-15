<?php

namespace Tests\Feature;

use App\Models\LotteryDraw;
use App\Services\Lottery\LotteryRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendations_are_built_for_lotto_and_eurojackpot(): void
    {
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => '2026-06-01',
            'numbers' => [1, 2, 3, 4, 5, 6],
            'bonus_numbers' => ['superzahl' => 7],
        ]);
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => '2026-06-08',
            'numbers' => [1, 8, 9, 10, 11, 12],
            'bonus_numbers' => ['superzahl' => 7],
        ]);
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_EUROJACKPOT,
            'draw_date' => '2026-06-06',
            'numbers' => [1, 2, 3, 4, 5],
            'bonus_numbers' => ['euro_numbers' => [1, 2]],
        ]);
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_EUROJACKPOT,
            'draw_date' => '2026-06-13',
            'numbers' => [1, 6, 7, 8, 9],
            'bonus_numbers' => ['euro_numbers' => [1, 3]],
        ]);

        $recommendations = app(LotteryRecommendationService::class)->recommendations();

        $this->assertCount(6, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['main_numbers']);
        $this->assertCount(1, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['bonus_numbers']);
        $this->assertContains(1, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['main_numbers']);
        $this->assertSame([7], $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['bonus_numbers']);

        $this->assertCount(5, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['main_numbers']);
        $this->assertCount(2, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['bonus_numbers']);
        $this->assertContains(1, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['main_numbers']);
        $this->assertContains(1, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['bonus_numbers']);
    }
}
