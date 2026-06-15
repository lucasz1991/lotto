<?php

namespace App\Services\Lottery;

use App\Models\LotteryDraw;
use Illuminate\Support\Collection;

class LotteryRecommendationService
{
    public function recommendations(): array
    {
        return [
            LotteryDraw::GAME_LOTTO_6AUS49 => $this->buildForGame(
                game: LotteryDraw::GAME_LOTTO_6AUS49,
                mainRange: range(1, 49),
                mainPickCount: 6,
                bonusRange: range(0, 9),
                bonusPickCount: 1,
                bonusKey: 'superzahl',
            ),
            LotteryDraw::GAME_EUROJACKPOT => $this->buildForGame(
                game: LotteryDraw::GAME_EUROJACKPOT,
                mainRange: range(1, 50),
                mainPickCount: 5,
                bonusRange: range(1, 12),
                bonusPickCount: 2,
                bonusKey: 'euro_numbers',
            ),
        ];
    }

    protected function buildForGame(
        string $game,
        array $mainRange,
        int $mainPickCount,
        array $bonusRange,
        int $bonusPickCount,
        string $bonusKey,
    ): array {
        $draws = LotteryDraw::query()
            ->where('game', $game)
            ->orderByDesc('draw_date')
            ->get(['draw_date', 'numbers', 'bonus_numbers']);

        if ($draws->isEmpty()) {
            return [
                'game' => $game,
                'label' => LotteryDraw::gameLabels()[$game] ?? $game,
                'draw_count' => 0,
                'latest_draw_date' => null,
                'main_numbers' => [],
                'bonus_numbers' => [],
                'main_stats' => [],
                'bonus_stats' => [],
                'confidence' => 'Keine Daten',
            ];
        }

        $mainStats = $this->scoreNumbers($draws, $mainRange, $mainPickCount, fn (LotteryDraw $draw): array => $draw->numbers ?? []);
        $bonusStats = $this->scoreNumbers(
            $draws,
            $bonusRange,
            $bonusPickCount,
            fn (LotteryDraw $draw): array => $this->extractBonusNumbers($draw, $bonusKey),
        );

        return [
            'game' => $game,
            'label' => LotteryDraw::gameLabels()[$game] ?? $game,
            'draw_count' => $draws->count(),
            'latest_draw_date' => $draws->first()?->draw_date,
            'main_numbers' => collect($mainStats)->take($mainPickCount)->pluck('number')->sort()->values()->all(),
            'bonus_numbers' => collect($bonusStats)->take($bonusPickCount)->pluck('number')->sort()->values()->all(),
            'main_stats' => array_slice($mainStats, 0, 12),
            'bonus_stats' => array_slice($bonusStats, 0, 8),
            'confidence' => $this->confidenceLabel($draws->count()),
        ];
    }

    protected function scoreNumbers(Collection $draws, array $range, int $pickCount, callable $numberExtractor): array
    {
        $totalDraws = max(1, $draws->count());
        $recentDraws = $draws->take(min(50, $totalDraws));
        $lastSeen = array_fill_keys($range, null);
        $frequency = array_fill_keys($range, 0);
        $recentFrequency = array_fill_keys($range, 0);

        foreach ($draws as $index => $draw) {
            foreach ($numberExtractor($draw) as $number) {
                if (! array_key_exists($number, $frequency)) {
                    continue;
                }

                $frequency[$number]++;
                $lastSeen[$number] ??= $index;
            }
        }

        foreach ($recentDraws as $draw) {
            foreach ($numberExtractor($draw) as $number) {
                if (array_key_exists($number, $recentFrequency)) {
                    $recentFrequency[$number]++;
                }
            }
        }

        $expectedFrequency = ($totalDraws * $pickCount) / count($range);
        $stats = [];

        foreach ($range as $number) {
            $missedDraws = $lastSeen[$number] ?? $totalDraws;
            $score = ($frequency[$number] * 1.0)
                + ($recentFrequency[$number] * 1.8)
                + min(10, $missedDraws / 8)
                + (($frequency[$number] - $expectedFrequency) * 0.35);

            $stats[] = [
                'number' => $number,
                'score' => round($score, 3),
                'frequency' => $frequency[$number],
                'recent_frequency' => $recentFrequency[$number],
                'missed_draws' => $missedDraws,
            ];
        }

        usort($stats, fn (array $left, array $right): int => $right['score'] <=> $left['score']
            ?: $right['frequency'] <=> $left['frequency']
            ?: $left['number'] <=> $right['number']);

        return $stats;
    }

    protected function extractBonusNumbers(LotteryDraw $draw, string $bonusKey): array
    {
        $bonusNumbers = $draw->bonus_numbers ?? [];
        $value = $bonusNumbers[$bonusKey] ?? null;

        if (is_array($value)) {
            return array_values(array_filter($value, fn (mixed $number): bool => is_numeric($number)));
        }

        return is_numeric($value) ? [(int) $value] : [];
    }

    protected function confidenceLabel(int $drawCount): string
    {
        return match (true) {
            $drawCount >= 300 => 'hoch',
            $drawCount >= 100 => 'mittel',
            $drawCount > 0 => 'niedrig',
            default => 'keine Daten',
        };
    }
}
