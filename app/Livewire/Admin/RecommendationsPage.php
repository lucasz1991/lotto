<?php

namespace App\Livewire\Admin;

use App\Services\Lottery\LotteryRecommendationService;
use Livewire\Component;

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
            'recommendations' => $recommendationsByGame,
            'selectedStatsModal' => $this->selectedStatsModal($recommendationsByGame),
            'rowCountOptions' => [1, 2, 3, 4, 5, 6, 8, 10],
            'rowCountSelectOptions' => $this->rowCountSelectOptions(),
            'statsLimitOptions' => [10, 12, 20, 30, 40, 50],
            'statsLimitSelectOptions' => $this->statsLimitSelectOptions(),
        ])->layout('layouts.master');
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
            'stats' => $isBonus ? $recommendation['bonus_stats'] : $recommendation['main_stats'],
        ];
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
                'label' => $count.' Felder',
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
}
