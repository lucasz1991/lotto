<?php

namespace App\Livewire\Admin;

use App\Models\LotteryDraw;
use Livewire\Component;
use Livewire\WithPagination;

class HistoryPage extends Component
{
    use WithPagination;

    public string $game = '';

    public string $sortField = 'draw_date';

    public string $sortDirection = 'desc';

    public int $perPage = 25;

    public function updatedGame(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = (int) $this->perPage;
        $this->perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortableFields(), true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'draw_date' ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $query = LotteryDraw::query()
            ->when($this->game !== '', fn ($query) => $query->where('game', $this->game));

        $draws = (clone $query)
            ->orderBy($this->sortField, $this->sortDirection)
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.admin.history-page', [
            'draws' => $draws,
            'gameLabels' => LotteryDraw::gameLabels(),
            'totalDraws' => (clone $query)->count(),
            'latestDraw' => (clone $query)->latest('draw_date')->first(),
            'oldestDraw' => (clone $query)->oldest('draw_date')->first(),
        ])->layout('layouts.master', ['title' => 'Historie']);
    }

    protected function sortableFields(): array
    {
        return ['draw_date', 'game', 'stake_cents', 'source_file', 'updated_at'];
    }
}
