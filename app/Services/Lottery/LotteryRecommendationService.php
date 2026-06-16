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

    public const REUSE_ALLOW = 'allow';

    public const REUSE_BALANCED = 'balanced';

    public const REUSE_AVOID = 'avoid';

    public function recommendations(array $options = []): array
    {
        $method = $this->normalizeMethod($options['method'] ?? self::METHOD_RARE);
        $rowCount = $this->clampInt($options['row_count'] ?? 1, 1, 10);
        $statsLimit = $this->clampInt($options['stats_limit'] ?? 50, 5, 50);
        $reuseStrategy = $this->normalizeReuseStrategy($options['reuse_strategy'] ?? self::REUSE_AVOID);

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
                reuseStrategy: $reuseStrategy,
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
                reuseStrategy: $reuseStrategy,
            ),
        ];
    }

    public function recommendationsForGames(array $gameOptions): array
    {
        $gameOptions = $this->normalizeGameOptions($gameOptions);
        $recommendations = [];

        foreach (array_keys(LotteryDraw::gameLabels()) as $game) {
            $recommendations[$game] = $this->recommendations($gameOptions[$game])[$game];
        }

        return $recommendations;
    }

    public function defaultGameOptions(): array
    {
        return collect(array_keys(LotteryDraw::gameLabels()))
            ->mapWithKeys(fn (string $game): array => [
                $game => [
                    'method' => self::METHOD_RARE,
                    'row_count' => 1,
                    'stats_limit' => 50,
                    'reuse_strategy' => self::REUSE_AVOID,
                ],
            ])
            ->all();
    }

    public function normalizeGameOptions(array $gameOptions): array
    {
        $defaults = $this->defaultGameOptions();

        foreach ($defaults as $game => $defaultOptions) {
            $options = is_array($gameOptions[$game] ?? null) ? $gameOptions[$game] : [];

            $defaults[$game] = [
                'method' => $this->normalizeMethod($options['method'] ?? $defaultOptions['method']),
                'row_count' => $this->clampInt($options['row_count'] ?? $defaultOptions['row_count'], 1, 10),
                'stats_limit' => $this->clampInt($options['stats_limit'] ?? $defaultOptions['stats_limit'], 5, 50),
                'reuse_strategy' => $this->normalizeReuseStrategy($options['reuse_strategy'] ?? $defaultOptions['reuse_strategy']),
            ];
        }

        return $defaults;
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

    public function reuseStrategyLabels(): array
    {
        return [
            self::REUSE_ALLOW => 'Gleiche Zahlen erlauben',
            self::REUSE_BALANCED => 'Ausgewogen verteilen',
            self::REUSE_AVOID => 'Gleiche Zahlen vermeiden',
        ];
    }

    public function analyzeCombination(string $game, array $mainNumbers, array $bonusNumbers): array
    {
        $config = $this->gameConfig($game);
        $draws = LotteryDraw::query()
            ->where('game', $game)
            ->orderByDesc('draw_date')
            ->get(['draw_date', 'numbers', 'bonus_numbers']);

        if ($draws->isEmpty()) {
            return [
                'score' => 0,
                'rating' => 'Keine Daten',
                'draw_count' => 0,
                'main_stats' => [],
                'bonus_stats' => [],
                'history' => null,
            ];
        }

        $mainStats = $this->scoreNumbers(
            $draws,
            $config['main_range'],
            $config['main_pick_count'],
            fn (LotteryDraw $draw): array => $draw->numbers ?? [],
            self::METHOD_BALANCED,
        );
        $bonusStats = $this->scoreNumbers(
            $draws,
            $config['bonus_range'],
            $config['bonus_pick_count'],
            fn (LotteryDraw $draw): array => $this->extractBonusNumbers($draw, $config['bonus_key']),
            self::METHOD_BALANCED,
        );

        $mainByNumber = collect($mainStats)->keyBy('number');
        $bonusByNumber = collect($bonusStats)->keyBy('number');
        $mainRanks = collect($mainStats)->pluck('number')->flip();
        $bonusRanks = collect($bonusStats)->pluck('number')->flip();

        $mainScore = $this->averageRankScore($mainNumbers, $mainRanks, count($config['main_range']));
        $bonusScore = $this->averageRankScore($bonusNumbers, $bonusRanks, count($config['bonus_range']));
        $score = (int) round(($mainScore * 0.8) + ($bonusScore * 0.2));

        return [
            'score' => $score,
            'rating' => $this->scoreRating($score),
            'draw_count' => $draws->count(),
            'latest_draw_date' => $draws->first()?->draw_date?->toDateString(),
            'main_stats' => collect($mainNumbers)
                ->map(fn (int $number): array => $mainByNumber->get($number, ['number' => $number]))
                ->values()
                ->all(),
            'bonus_stats' => collect($bonusNumbers)
                ->map(fn (int $number): array => $bonusByNumber->get($number, ['number' => $number]))
                ->values()
                ->all(),
            'history' => $this->combinationHistory($draws, $mainNumbers, $bonusNumbers, $config['bonus_key']),
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
        string $reuseStrategy,
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
                'reuse_strategy' => $reuseStrategy,
                'reuse_strategy_label' => $this->reuseStrategyLabels()[$reuseStrategy],
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
        $rows = $this->buildRows($mainStats, $bonusStats, $mainPickCount, $bonusPickCount, $rowCount, $reuseStrategy);
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
            'reuse_strategy' => $reuseStrategy,
            'reuse_strategy_label' => $this->reuseStrategyLabels()[$reuseStrategy],
            'confidence' => $this->confidenceLabel($draws->count()),
        ];
    }

    protected function gameConfig(string $game): array
    {
        return match ($game) {
            LotteryDraw::GAME_EUROJACKPOT => [
                'main_range' => range(1, 50),
                'main_pick_count' => 5,
                'bonus_range' => range(1, 12),
                'bonus_pick_count' => 2,
                'bonus_key' => 'euro_numbers',
            ],
            default => [
                'main_range' => range(1, 49),
                'main_pick_count' => 6,
                'bonus_range' => range(0, 9),
                'bonus_pick_count' => 1,
                'bonus_key' => 'superzahl',
            ],
        };
    }

    protected function averageRankScore(array $numbers, Collection $ranks, int $rangeCount): float
    {
        if ($numbers === [] || $rangeCount <= 1) {
            return 0;
        }

        return collect($numbers)
            ->map(function (int $number) use ($ranks, $rangeCount): float {
                $rank = (int) ($ranks->get($number, $rangeCount - 1));

                return max(0, 100 - (($rank / ($rangeCount - 1)) * 100));
            })
            ->average() ?? 0;
    }

    protected function scoreRating(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Sehr stark',
            $score >= 60 => 'Stark',
            $score >= 45 => 'Mittel',
            $score > 0 => 'Schwach',
            default => 'Keine Daten',
        };
    }

    protected function combinationHistory(Collection $draws, array $mainNumbers, array $bonusNumbers, string $bonusKey): array
    {
        $best = [
            'main_hits' => 0,
            'bonus_hits' => 0,
            'draw_date' => null,
            'exact_match' => false,
        ];

        foreach ($draws as $draw) {
            $mainHits = count(array_intersect($mainNumbers, $draw->numbers ?? []));
            $drawBonusNumbers = $this->extractBonusNumbers($draw, $bonusKey);
            $bonusHits = count(array_intersect($bonusNumbers, $drawBonusNumbers));

            if ($mainHits > $best['main_hits'] || ($mainHits === $best['main_hits'] && $bonusHits > $best['bonus_hits'])) {
                $best = [
                    'main_hits' => $mainHits,
                    'bonus_hits' => $bonusHits,
                    'draw_date' => $draw->draw_date?->toDateString(),
                    'exact_match' => $this->sameNumbers($mainNumbers, $draw->numbers ?? [])
                        && $this->sameNumbers($bonusNumbers, $drawBonusNumbers),
                ];
            }
        }

        return $best;
    }

    protected function sameNumbers(array $left, array $right): bool
    {
        $left = array_map('intval', $left);
        $right = array_map('intval', $right);
        sort($left);
        sort($right);

        return $left === $right;
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
            $lastSeenDraw = $lastSeen[$number] === null ? null : $draws->get($lastSeen[$number]);
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
                'last_seen_date' => $lastSeenDraw?->draw_date,
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

    protected function buildRows(array $mainStats, array $bonusStats, int $mainPickCount, int $bonusPickCount, int $rowCount, string $reuseStrategy): array
    {
        $rows = [];
        $seen = [];
        $usedMainNumbers = [];
        $usedBonusNumbers = [];

        for ($rowIndex = 0; count($rows) < $rowCount && $rowIndex < $rowCount * 3; $rowIndex++) {
            $mainNumbers = $this->selectNumbersForRow($mainStats, $mainPickCount, $rowIndex, $reuseStrategy, $usedMainNumbers);
            $bonusNumbers = $this->selectNumbersForRow($bonusStats, $bonusPickCount, $rowIndex, $reuseStrategy, $usedBonusNumbers);

            $row = [
                'main_numbers' => $mainNumbers,
                'bonus_numbers' => $bonusNumbers,
                'main_number_stats' => $this->statsForNumbers($mainStats, $mainNumbers),
                'bonus_number_stats' => $this->statsForNumbers($bonusStats, $bonusNumbers),
            ];
            $key = implode(',', $row['main_numbers']).'|'.implode(',', $row['bonus_numbers']);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            foreach ($mainNumbers as $number) {
                $usedMainNumbers[$number] = ($usedMainNumbers[$number] ?? 0) + 1;
            }
            foreach ($bonusNumbers as $number) {
                $usedBonusNumbers[$number] = ($usedBonusNumbers[$number] ?? 0) + 1;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function statsForNumbers(array $stats, array $numbers): array
    {
        $statsByNumber = collect($stats)->keyBy('number');

        return collect($numbers)
            ->mapWithKeys(fn (int $number): array => [
                $number => $statsByNumber->get($number, []),
            ])
            ->all();
    }

    protected function selectNumbersForRow(array $stats, int $pickCount, int $rowIndex, string $reuseStrategy, array $usedNumbers = []): array
    {
        $rankedNumbers = array_values(array_map(fn (array $stat): int => (int) $stat['number'], $stats));

        if (count($rankedNumbers) <= $pickCount) {
            sort($rankedNumbers);

            return $rankedNumbers;
        }

        if ($reuseStrategy === self::REUSE_AVOID) {
            $unusedNumbers = array_values(array_filter(
                $rankedNumbers,
                fn (int $number): bool => ! isset($usedNumbers[$number])
            ));

            if (count($unusedNumbers) >= $pickCount) {
                $rankedNumbers = $unusedNumbers;
            }
        } elseif ($reuseStrategy === self::REUSE_BALANCED && $usedNumbers !== []) {
            $rankPositions = array_flip($rankedNumbers);

            usort($rankedNumbers, fn (int $left, int $right): int => ($usedNumbers[$left] ?? 0) <=> ($usedNumbers[$right] ?? 0)
                ?: $rankPositions[$left] <=> $rankPositions[$right]);
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

        return array_key_exists($method, $this->methodLabels()) ? $method : self::METHOD_RARE;
    }

    protected function normalizeReuseStrategy(mixed $strategy): string
    {
        $strategy = (string) $strategy;

        return array_key_exists($strategy, $this->reuseStrategyLabels()) ? $strategy : self::REUSE_AVOID;
    }

    protected function clampInt(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }
}
