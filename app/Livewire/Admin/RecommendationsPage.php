<?php

namespace App\Livewire\Admin;

use App\Services\Lottery\LotteryRecommendationService;
use Livewire\Component;

class RecommendationsPage extends Component
{
    public function render(LotteryRecommendationService $recommendations)
    {
        return view('livewire.admin.recommendations-page', [
            'recommendations' => $recommendations->recommendations(),
        ])->layout('layouts.master');
    }
}
