<?php

namespace App\Livewire\Admin;

use App\Services\Lottery\LotteryRecommendationService;
use Livewire\Component;

class RecommendationsPage extends Component
{
    public array $gameOptions = [];

    public function mount(LotteryRecommendationService $recommendations): void
    {
        $this->gameOptions = collect($recommendations->defaultGameOptions())
            ->map(fn (array $options): array => [
                'method' => $options['method'],
                'row_count' => $options['row_count'],
                'stats_limit' => $options['stats_limit'],
            ])
            ->all();
    }

    public function render(LotteryRecommendationService $recommendations)
    {
        $this->gameOptions = $recommendations->normalizeGameOptions($this->gameOptions);

        return view('livewire.admin.recommendations-page', [
            'methodLabels' => $recommendations->methodLabels(),
            'recommendations' => $recommendations->recommendationsForGames($this->gameOptions),
            'rowCountOptions' => [1, 2, 3, 4, 5, 6, 8, 10],
            'statsLimitOptions' => [10, 12, 20, 30, 40, 50],
        ])->layout('layouts.master');
    }
}
