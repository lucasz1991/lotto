<?php

namespace App\Livewire\Admin;

use App\Models\LotteryDraw;
use App\Models\LotteryNumberCheck;
use App\Services\Lottery\LotteryRecommendationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class NumberCheckPage extends Component
{
    public string $game = LotteryDraw::GAME_LOTTO_6AUS49;

    public string $label = '';

    public array $selectedMainNumbers = [];

    public array $selectedBonusNumbers = [];

    public ?array $currentAnalysis = null;

    public function updatedGame(): void
    {
        $this->reset(['selectedMainNumbers', 'selectedBonusNumbers', 'currentAnalysis']);
    }

    public function toggleMainNumber(int $number): void
    {
        $requirements = $this->requirements();

        if ($number < $requirements['main_min'] || $number > $requirements['main_max']) {
            return;
        }

        if (in_array($number, $this->selectedMainNumbers, true)) {
            $this->selectedMainNumbers = array_values(array_diff($this->selectedMainNumbers, [$number]));
        } elseif (count($this->selectedMainNumbers) < $requirements['main_count']) {
            $this->selectedMainNumbers[] = $number;
        }

        sort($this->selectedMainNumbers);
        $this->currentAnalysis = null;
    }

    public function toggleBonusNumber(int $number): void
    {
        $requirements = $this->requirements();

        if ($number < $requirements['bonus_min'] || $number > $requirements['bonus_max']) {
            return;
        }

        if (in_array($number, $this->selectedBonusNumbers, true)) {
            $this->selectedBonusNumbers = array_values(array_diff($this->selectedBonusNumbers, [$number]));
        } elseif (count($this->selectedBonusNumbers) < $requirements['bonus_count']) {
            $this->selectedBonusNumbers[] = $number;
        }

        sort($this->selectedBonusNumbers);
        $this->currentAnalysis = null;
    }

    public function clearSelection(): void
    {
        $this->reset(['selectedMainNumbers', 'selectedBonusNumbers', 'currentAnalysis']);
    }

    public function analyze(LotteryRecommendationService $service): void
    {
        [$mainNumbers, $bonusNumbers] = $this->validatedNumbers();

        $this->currentAnalysis = $service->analyzeCombination($this->game, $mainNumbers, $bonusNumbers);
        $this->currentAnalysis['main_numbers'] = $mainNumbers;
        $this->currentAnalysis['bonus_numbers'] = $bonusNumbers;
    }

    public function save(LotteryRecommendationService $service): void
    {
        [$mainNumbers, $bonusNumbers] = $this->validatedNumbers();
        $analysis = $service->analyzeCombination($this->game, $mainNumbers, $bonusNumbers);

        LotteryNumberCheck::query()->create([
            'user_id' => Auth::id(),
            'game' => $this->game,
            'label' => trim($this->label) !== '' ? trim($this->label) : null,
            'main_numbers' => $mainNumbers,
            'bonus_numbers' => $bonusNumbers,
            'score' => $analysis['score'],
            'rating' => $analysis['rating'],
            'analysis' => $analysis,
            'last_evaluated_at' => now(),
        ]);

        $this->currentAnalysis = $analysis + [
            'main_numbers' => $mainNumbers,
            'bonus_numbers' => $bonusNumbers,
        ];

        session()->flash('success', 'Zahlencheck wurde gespeichert.');
    }

    public function deleteCheck(int $id): void
    {
        LotteryNumberCheck::query()->whereKey($id)->delete();

        session()->flash('success', 'Zahlencheck wurde geloescht.');
    }

    public function render()
    {
        return view('livewire.admin.number-check-page', [
            'gameLabels' => LotteryDraw::gameLabels(),
            'savedChecks' => LotteryNumberCheck::query()
                ->latest()
                ->limit(20)
                ->get(),
            'requirements' => $this->requirements(),
            'mainRange' => range($this->requirements()['main_min'], $this->requirements()['main_max']),
            'bonusRange' => range($this->requirements()['bonus_min'], $this->requirements()['bonus_max']),
        ])->layout('layouts.master', ['title' => 'Zahlencheck']);
    }

    protected function validatedNumbers(): array
    {
        $this->validate([
            'game' => ['required', 'in:'.implode(',', array_keys(LotteryDraw::gameLabels()))],
            'label' => ['nullable', 'string', 'max:80'],
        ]);

        $mainNumbers = array_map('intval', $this->selectedMainNumbers);
        $bonusNumbers = array_map('intval', $this->selectedBonusNumbers);
        sort($mainNumbers);
        sort($bonusNumbers);
        $requirements = $this->requirements();

        $this->assertNumberSet($mainNumbers, $requirements['main_count'], $requirements['main_min'], $requirements['main_max'], 'selectedMainNumbers');
        $this->assertNumberSet($bonusNumbers, $requirements['bonus_count'], $requirements['bonus_min'], $requirements['bonus_max'], 'selectedBonusNumbers');

        return [$mainNumbers, $bonusNumbers];
    }

    protected function assertNumberSet(array $numbers, int $count, int $min, int $max, string $field): void
    {
        if (count($numbers) !== $count || count(array_unique($numbers)) !== $count) {
            throw ValidationException::withMessages([
                $field => 'Bitte genau '.$count.' unterschiedliche Zahlen auswaehlen.',
            ]);
        }

        foreach ($numbers as $number) {
            if ($number < $min || $number > $max) {
                throw ValidationException::withMessages([
                    $field => 'Erlaubt sind Zahlen von '.$min.' bis '.$max.'.',
                ]);
            }
        }
    }

    protected function requirements(): array
    {
        return $this->game === LotteryDraw::GAME_EUROJACKPOT
            ? [
                'main_count' => 5,
                'main_min' => 1,
                'main_max' => 50,
                'bonus_count' => 2,
                'bonus_min' => 1,
                'bonus_max' => 12,
                'bonus_label' => 'Eurozahlen',
            ]
            : [
                'main_count' => 6,
                'main_min' => 1,
                'main_max' => 49,
                'bonus_count' => 1,
                'bonus_min' => 0,
                'bonus_max' => 9,
                'bonus_label' => 'Superzahl',
            ];
    }
}
