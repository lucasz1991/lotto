<?php

namespace App\Livewire\Admin;

use App\Models\LotteryDraw;
use Livewire\Component;
use Livewire\WithPagination;

class HistoryPage extends Component
{
    use WithPagination;

    public string $game = '';

    public function updatedGame(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $draws = LotteryDraw::query()
            ->when($this->game !== '', fn ($query) => $query->where('game', $this->game))
            ->orderByDesc('draw_date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.admin.history-page', [
            'draws' => $draws,
            'gameLabels' => LotteryDraw::gameLabels(),
        ])->layout('layouts.master');
    }
}
