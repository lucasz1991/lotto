<?php

namespace App\Services\Lottery;

use App\Models\LotteryDraw;
use Illuminate\Support\Collection;

class LotteryRecommendationService
{
    public const METHOD_BALANCED = 'balanced';

    public const METHOD_OVERDUE = 'overdue';

    public const METHOD_HOT = 'hot';

    public const METHOD_RECENT = 'recent';

    public const METHOD_RARE = 'rare';

    public function recommendations(array $options = []): array
    {
        $method = $this->normalizeMethod($options['method'] ?? self::METHOD_BALANCED);
        $rowCount = $this->clampInt($options['row_count'] ?? 1, 1, 10);
        $statsLimit = $this->clampInt($options['stats_limit'] ?? 12, 5, 50);

        return [
            LotteryDraw::GAME_LOTTO_6AUS49 => $this->buildForGame(
                game: LotteryDraw::GAME_LOTTO_6AUS49,
                mainRange: range(1, 49),
                mainPickCount: 6,
                bonusRange: range(0, 9),
                bonusPickCount: 1,
                bonusKey: 'superzahl',
                method: $method,
                rowCount: $rowCount,
                statsLimit: $statsLimit,
            ),
            LotteryDraw::GAME_EUROJACKPOT => $this->buildForGame(
                game: LotteryDraw::GAME_EUROJACKPOT,
                mainRange: range(1, 50),
                mainPickCount: 5,
                bonusRange: range(1, 12),
                bonusPickCount: 2,
                bonusKey: 'euro_numbers',
                method: $method,
                rowCount: $rowCount,
                statsLimit: $statsLimit,
            ),
        ];
    }

    public function methodLabels(): array
    {
        return [
            self::METHOD_BALANCED => 'Ausgewogen',
            self::METHOD_OVERDUE => 'Lange nicht gezogen',
            self::METHOD_HOT => 'Haeufig gezogen',
            self::METHOD_RECENT => 'Haeufig in letzter Zeit',
            self::METHOD_RARE => 'Selten gezogen',
        ];
    }

    protected function buildForGame(
        string $game,
        array $mainRange,
        int $mainPickCount,
        array $bonusRange,
        int $bonusPickCount,
        string $bonusKey,
        string $method,
        int $rowCount,
        int $statsLimit,
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
                'rows' => [],
                'main_stats' => [],
                'bonus_stats' => [],
                'method' => $method,
                'method_label' => $this->methodLabels()[$method],
                'confidence' => 'Keine Daten',
            ];
        }

        $mainStats = $this->scoreNumbers(
            $draws,
            $mainRange,
            $mainPickCount,
            fn (LotteryDraw $draw): array => $draw->numbers ?? [],
            $method,
        );
        $bonusStats = $this->scoreNumbers(
            $draws,
            $bonusRange,
            $bonusPickCount,
            fn (LotteryDraw $draw): array => $this->extractBonusNumbers($draw, $bonusKey),
            $method,
        );
        $rows = $this->buildRows($mainStats, $bonusStats, $mainPickCount, $bonusPickCount, $rowCount);
        $firstRow = $rows[0] ?? ['main_numbers' => [], 'bonus_numbers' => []];

        return [
            'game' => $game,
            'label' => LotteryDraw::gameLabels()[$game] ?? $game,
            'draw_count' => $draws->count(),
            'latest_draw_date' => $draws->first()?->draw_date,
            'main_numbers' => $firstRow['main_numbers'],
            'bonus_numbers' => $firstRow['bonus_numbers'],
            'rows' => $rows,
            'main_stats' => array_slice($mainStats, 0, $statsLimit),
            'bonus_stats' => array_slice($bonusStats, 0, min($statsLimit, count($bonusRange))),
            'method' => $method,
            'method_label' => $this->methodLabels()[$method],
            'confidence' => $this->confidenceLabel($draws->count()),
        ];
    }

    protected function scoreNumbers(Collection $draws, array $range, int $pickCount, callable $numberExtractor, string $method): array
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
                'expected_frequency' => round($expectedFrequency, 3),
                'frequency_gap' => round($expectedFrequency - $frequency[$number], 3),
            ];
        }

        $this->sortStats($stats, $method);

        return $stats;
    }

    protected function sortStats(array &$stats, string $method): void
    {
        usort($stats, match ($method) {
            self::METHOD_OVERDUE => fn (array $left, array $right): int => $right['missed_draws'] <=> $left['missed_draws']
                ?: $left['frequency'] <=> $right['frequency']
                ?: $left['number'] <=> $right['number'],
            self::METHOD_HOT => fn (array $left, array $right): int => $right['frequency'] <=> $left['frequency']
                ?: $right['recent_frequency'] <=> $left['recent_frequency']
                ?: $left['number'] <=> $right['number'],
            self::METHOD_RECENT => fn (array $left, array $right): int => $right['recent_frequency'] <=> $left['recent_frequency']
                ?: $right['frequency'] <=> $left['frequency']
                ?: $left['number'] <=> $right['number'],
            self::METHOD_RARE => fn (array $left, array $right): int => $left['frequency'] <=> $right['frequency']
                ?: $right['missed_draws'] <=> $left['missed_draws']
                ?: $left['number'] <=> $right['number'],
            default => fn (array $left, array $right): int => $right['score'] <=> $left['score']
                ?: $right['frequency'] <=> $left['frequency']
                ?: $left['number'] <=> $right['number'],
        });
    }

    protected function buildRows(array $mainStats, array $bonusStats, int $mainPickCount, int $bonusPickCount, int $rowCount): array
    {
        $rows = [];
        $seen = [];

        for ($rowIndex = 0; count($rows) < $rowCount && $rowIndex < $rowCount * 3; $rowIndex++) {
            $row = [
                'main_numbers' => $this->selectNumbersForRow($mainStats, $mainPickCount, $rowIndex),
                'bonus_numbers' => $this->selectNumbersForRow($bonusStats, $bonusPickCount, $rowIndex),
            ];
            $key = implode(',', $row['main_numbers']).'|'.implode(',', $row['bonus_numbers']);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = $row;
        }

        return $rows;
    }

    protected function selectNumbersForRow(array $stats, int $pickCount, int $rowIndex): array
    {
        $rankedNumbers = array_values(array_map(fn (array $stat): int => (int) $stat['number'], $stats));

        if (count($rankedNumbers) <= $pickCount) {
            sort($rankedNumbers);

            return $rankedNumbers;
        }

        $poolSize = min(count($rankedNumbers), max($pickCount, $pickCount + $rowIndex + 4));
        $pool = array_slice($rankedNumbers, 0, $poolSize);
        $selected = [];
        $cursor = $rowIndex;

        while (count($selected) < $pickCount && count($selected) < count($pool)) {
            $number = $pool[$cursor % count($pool)];

            if (! in_array($number, $selected, true)) {
                $selected[] = $number;
            }

            $cursor++;
        }

        sort($selected);

        return $selected;
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

    protected function normalizeMethod(mixed $method): string
    {
        $method = (string) $method;

        return array_key_exists($method, $this->methodLabels()) ? $method : self::METHOD_BALANCED;
    }

    protected function clampInt(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }
}
