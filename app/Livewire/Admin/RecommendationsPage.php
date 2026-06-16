<?php

namespace App\Livewire\Admin;

use App\Services\Lottery\LotteryRecommendationService;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecommendationsPage extends Component
{
    public array $gameOptions = [];

    public bool $statsModalOpen = false;

    public ?string $selectedStatsGame = null;

    public string $selectedStatsType = 'main';

    public string $activeMobileGame = '';

    public function mount(LotteryRecommendationService $recommendations): void
    {
        $this->gameOptions = collect($recommendations->defaultGameOptions())
            ->map(fn (array $options): array => [
                'method' => $options['method'],
                'row_count' => $options['row_count'],
                'stats_limit' => $options['stats_limit'],
                'reuse_strategy' => $options['reuse_strategy'],
            ])
            ->all();
        $this->activeMobileGame = array_key_first($this->gameOptions) ?? '';
    }

    public function render(LotteryRecommendationService $recommendations)
    {
        $this->gameOptions = $recommendations->normalizeGameOptions($this->gameOptions);
        $this->activeMobileGame = array_key_exists($this->activeMobileGame, $this->gameOptions)
            ? $this->activeMobileGame
            : (array_key_first($this->gameOptions) ?? '');
        $recommendationsByGame = $recommendations->recommendationsForGames($this->gameOptions);

        return view('livewire.admin.recommendations-page', [
            'methodLabels' => $recommendations->methodLabels(),
            'methodSelectOptions' => $this->methodSelectOptions($recommendations->methodLabels()),
            'reuseStrategySelectOptions' => $this->reuseStrategySelectOptions($recommendations->reuseStrategyLabels()),
            'recommendations' => $recommendationsByGame,
            'selectedStatsModal' => $this->selectedStatsModal($recommendationsByGame),
            'rowCountOptions' => [1, 2, 3, 4, 5, 6, 8, 10],
            'rowCountSelectOptions' => $this->rowCountSelectOptions(),
            'statsLimitOptions' => [10, 12, 20, 30, 40, 50],
            'statsLimitSelectOptions' => $this->statsLimitSelectOptions(),
        ])->layout('layouts.master', ['title' => 'Empfehlungen']);
    }

    public function openStatsModal(string $game, string $type): void
    {
        if (! array_key_exists($game, $this->gameOptions) || ! in_array($type, ['main', 'bonus'], true)) {
            return;
        }

        $this->selectedStatsGame = $game;
        $this->selectedStatsType = $type;
        $this->statsModalOpen = true;
    }

    public function closeStatsModal(): void
    {
        $this->statsModalOpen = false;
    }

    public function showMobileGame(string $game): void
    {
        if (array_key_exists($game, $this->gameOptions)) {
            $this->activeMobileGame = $game;
        }
    }

    public function exportTxt(LotteryRecommendationService $recommendations): StreamedResponse
    {
        $this->gameOptions = $recommendations->normalizeGameOptions($this->gameOptions);
        $recommendationsByGame = $recommendations->recommendationsForGames($this->gameOptions);
        $content = $this->buildTxtExport($recommendationsByGame);
        $filename = 'lotto-empfehlungen-'.now()->format('Y-m-d-His').'.txt';

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $filename,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    protected function selectedStatsModal(array $recommendations): ?array
    {
        if (! $this->selectedStatsGame || ! isset($recommendations[$this->selectedStatsGame])) {
            return null;
        }

        $recommendation = $recommendations[$this->selectedStatsGame];
        $isBonus = $this->selectedStatsType === 'bonus';
        $isEuroJackpot = $recommendation['game'] === \App\Models\LotteryDraw::GAME_EUROJACKPOT;

        return [
            'title' => $isBonus ? ($isEuroJackpot ? 'Eurozahlen' : 'Superzahl') : 'Hauptzahlen',
            'subtitle' => $recommendation['label'].' - '.$recommendation['method_label'],
            'game' => $recommendation['game'],
            'is_bonus' => $isBonus,
            'stats' => $isBonus ? $recommendation['bonus_stats'] : $recommendation['main_stats'],
        ];
    }

    protected function buildTxtExport(array $recommendations): string
    {
        $lines = [
            'Lotto Empfehlungen',
            'Exportiert am: '.now()->format('d.m.Y H:i:s'),
            '',
        ];

        foreach ($recommendations as $recommendation) {
            $options = $this->gameOptions[$recommendation['game']] ?? [];
            $bonusLabel = $recommendation['game'] === \App\Models\LotteryDraw::GAME_EUROJACKPOT
                ? 'Eurozahlen'
                : 'Superzahl';

            $lines[] = str_repeat('=', 48);
            $lines[] = $recommendation['label'];
            $lines[] = str_repeat('=', 48);
            $lines[] = 'Datenbasis: '.$recommendation['draw_count'].' Ziehungen';
            $lines[] = 'Letzte Ziehung: '.($recommendation['latest_draw_date']?->format('d.m.Y') ?? '-');
            $lines[] = 'Auswertungsart: '.$recommendation['method_label'].' ('.($options['method'] ?? $recommendation['method']).')';
            $lines[] = 'Zahlenverteilung: '.$recommendation['reuse_strategy_label'].' ('.($options['reuse_strategy'] ?? $recommendation['reuse_strategy']).')';
            $lines[] = '';
            $lines[] = 'Empfohlene Felder:';

            if ($recommendation['rows'] === []) {
                $lines[] = '- Keine Empfehlungen vorhanden.';
            }

            foreach ($recommendation['rows'] as $index => $row) {
                $lines[] = sprintf(
                    '%d. Hauptzahlen: %s | %s: %s',
                    $index + 1,
                    implode(', ', $row['main_numbers']),
                    $bonusLabel,
                    implode(', ', $row['bonus_numbers'])
                );
            }

            
            $lines[] = '';
        }


        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    protected function methodSelectOptions(array $methodLabels): array
    {
        $icons = [
            LotteryRecommendationService::METHOD_BALANCED => 'mdi mdi-scale-balance',
            LotteryRecommendationService::METHOD_OVERDUE => 'mdi mdi-clock-alert-outline',
            LotteryRecommendationService::METHOD_HOT => 'mdi mdi-fire',
            LotteryRecommendationService::METHOD_RECENT => 'mdi mdi-history',
            LotteryRecommendationService::METHOD_RARE => 'mdi mdi-chart-scatter-plot',
        ];

        return collect($methodLabels)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
                'icon' => $icons[$value] ?? 'mdi mdi-chart-bell-curve-cumulative',
            ])
            ->values()
            ->all();
    }

    protected function rowCountSelectOptions(): array
    {
        return collect([1, 2, 3, 4, 5, 6, 8, 10])
            ->map(fn (int $count): array => [
                'value' => $count,
                'label' => (string) $count,
                'icon' => 'mdi mdi-view-grid-outline',
            ])
            ->all();
    }

    protected function statsLimitSelectOptions(): array
    {
        return collect([10, 12, 20, 30, 40, 50])
            ->map(fn (int $count): array => [
                'value' => $count,
                'label' => 'Top '.$count,
                'icon' => 'mdi mdi-format-list-numbered',
            ])
            ->all();
    }

    protected function reuseStrategySelectOptions(array $strategyLabels): array
    {
        $icons = [
            LotteryRecommendationService::REUSE_ALLOW => 'mdi mdi-repeat',
            LotteryRecommendationService::REUSE_BALANCED => 'mdi mdi-call-split',
            LotteryRecommendationService::REUSE_AVOID => 'mdi mdi-set-none',
        ];

        return collect($strategyLabels)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
                'icon' => $icons[$value] ?? 'mdi mdi-repeat-variant',
            ])
            ->values()
            ->all();
    }
}
