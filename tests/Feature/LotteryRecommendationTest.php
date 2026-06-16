<?php

namespace Tests\Feature;

use App\Livewire\Admin\RecommendationsPage;
use App\Models\LotteryDraw;
use App\Services\Lottery\LotteryRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
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

        $recommendations = app(LotteryRecommendationService::class)->recommendations([
            'method' => LotteryRecommendationService::METHOD_BALANCED,
        ]);

        $this->assertCount(6, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['main_numbers']);
        $this->assertCount(1, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['bonus_numbers']);
        $this->assertContains(1, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['main_numbers']);
        $this->assertSame([7], $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['bonus_numbers']);

        $this->assertCount(5, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['main_numbers']);
        $this->assertCount(2, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['bonus_numbers']);
        $this->assertContains(1, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['main_numbers']);
        $this->assertContains(1, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['bonus_numbers']);
    }

    public function test_recommendations_can_use_overdue_method_and_multiple_rows(): void
    {
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => '2026-06-01',
            'numbers' => [1, 2, 3, 4, 5, 6],
            'bonus_numbers' => ['superzahl' => 1],
        ]);
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => '2026-06-08',
            'numbers' => [7, 8, 9, 10, 11, 12],
            'bonus_numbers' => ['superzahl' => 2],
        ]);

        $recommendations = app(LotteryRecommendationService::class)->recommendations([
            'method' => LotteryRecommendationService::METHOD_OVERDUE,
            'row_count' => 3,
            'stats_limit' => 20,
            'reuse_strategy' => LotteryRecommendationService::REUSE_AVOID,
        ]);

        $lotto = $recommendations[LotteryDraw::GAME_LOTTO_6AUS49];

        $this->assertSame(LotteryRecommendationService::METHOD_OVERDUE, $lotto['method']);
        $this->assertCount(3, $lotto['rows']);
        $this->assertCount(6, $lotto['rows'][0]['main_numbers']);
        $this->assertGreaterThanOrEqual(13, min($lotto['rows'][0]['main_numbers']));
        $this->assertSame(2, $lotto['main_stats'][0]['missed_draws']);
        $this->assertEmpty(array_intersect($lotto['rows'][0]['main_numbers'], $lotto['rows'][1]['main_numbers']));
    }

    public function test_default_recommendation_method_is_rare_and_games_can_use_separate_options(): void
    {
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => '2026-06-01',
            'numbers' => [1, 2, 3, 4, 5, 6],
            'bonus_numbers' => ['superzahl' => 1],
        ]);
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_EUROJACKPOT,
            'draw_date' => '2026-06-01',
            'numbers' => [1, 2, 3, 4, 5],
            'bonus_numbers' => ['euro_numbers' => [1, 2]],
        ]);

        $defaultRecommendations = app(LotteryRecommendationService::class)->recommendations();

        $this->assertSame(LotteryRecommendationService::METHOD_RARE, $defaultRecommendations[LotteryDraw::GAME_LOTTO_6AUS49]['method']);
        $this->assertCount(1, $defaultRecommendations[LotteryDraw::GAME_LOTTO_6AUS49]['rows']);
        $this->assertCount(49, $defaultRecommendations[LotteryDraw::GAME_LOTTO_6AUS49]['main_stats']);

        $recommendations = app(LotteryRecommendationService::class)->recommendationsForGames([
            LotteryDraw::GAME_LOTTO_6AUS49 => [
                'method' => LotteryRecommendationService::METHOD_RARE,
                'row_count' => 2,
                'stats_limit' => 10,
                'reuse_strategy' => LotteryRecommendationService::REUSE_BALANCED,
            ],
            LotteryDraw::GAME_EUROJACKPOT => [
                'method' => LotteryRecommendationService::METHOD_HOT,
                'row_count' => 1,
                'stats_limit' => 10,
                'reuse_strategy' => LotteryRecommendationService::REUSE_ALLOW,
            ],
        ]);

        $this->assertSame(LotteryRecommendationService::METHOD_RARE, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['method']);
        $this->assertSame(LotteryRecommendationService::METHOD_HOT, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['method']);
        $this->assertSame(LotteryRecommendationService::REUSE_BALANCED, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['reuse_strategy']);
        $this->assertSame(LotteryRecommendationService::REUSE_ALLOW, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['reuse_strategy']);
        $this->assertCount(2, $recommendations[LotteryDraw::GAME_LOTTO_6AUS49]['rows']);
        $this->assertCount(1, $recommendations[LotteryDraw::GAME_EUROJACKPOT]['rows']);
    }

    public function test_recommendations_can_be_exported_as_txt(): void
    {
        Carbon::setTestNow('2026-06-16 12:30:00');

        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => '2026-06-01',
            'numbers' => [1, 2, 3, 4, 5, 6],
            'bonus_numbers' => ['superzahl' => 1],
        ]);

        Livewire::test(RecommendationsPage::class)
            ->set('gameOptions.'.LotteryDraw::GAME_LOTTO_6AUS49.'.method', LotteryRecommendationService::METHOD_OVERDUE)
            ->set('gameOptions.'.LotteryDraw::GAME_LOTTO_6AUS49.'.row_count', 2)
            ->set('gameOptions.'.LotteryDraw::GAME_LOTTO_6AUS49.'.stats_limit', 10)
            ->call('exportTxt')
            ->assertFileDownloaded(
                'lotto-empfehlungen-2026-06-16-123000.txt',
                null,
                'text/plain; charset=UTF-8'
            );

        Carbon::setTestNow();
    }
}
