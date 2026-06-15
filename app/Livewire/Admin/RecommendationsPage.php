<?php

namespace App\Livewire\Admin;

use App\Services\Lottery\LotteryRecommendationService;
use Livewire\Component;

class RecommendationsPage extends Component
{
    public string $method = LotteryRecommendationService::METHOD_BALANCED;

    public int $rowCount = 3;

    public int $statsLimit = 12;

    public function render(LotteryRecommendationService $recommendations)
    {
        $this->method = array_key_exists($this->method, $recommendations->methodLabels())
            ? $this->method
            : LotteryRecommendationService::METHOD_BALANCED;
        $this->rowCount = max(1, min(10, (int) $this->rowCount));
        $this->statsLimit = max(5, min(50, (int) $this->statsLimit));

        return view('livewire.admin.recommendations-page', [
            'methodLabels' => $recommendations->methodLabels(),
            'recommendations' => $recommendations->recommendations([
                'method' => $this->method,
                'row_count' => $this->rowCount,
                'stats_limit' => $this->statsLimit,
            ]),
        ])->layout('layouts.master');
    }
}
