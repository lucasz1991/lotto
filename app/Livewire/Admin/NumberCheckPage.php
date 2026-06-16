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

    public string $mainNumbersInput = '';

    public string $bonusNumbersInput = '';

    public ?array $currentAnalysis = null;

    public function updatedGame(): void
    {
        $this->reset(['mainNumbersInput', 'bonusNumbersInput', 'currentAnalysis']);
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
        ])->layout('layouts.master', ['title' => 'Zahlencheck']);
    }

    protected function validatedNumbers(): array
    {
        $this->validate([
            'game' => ['required', 'in:'.implode(',', array_keys(LotteryDraw::gameLabels()))],
            'label' => ['nullable', 'string', 'max:80'],
            'mainNumbersInput' => ['required', 'string'],
            'bonusNumbersInput' => ['required', 'string'],
        ]);

        $mainNumbers = $this->parseNumbers($this->mainNumbersInput);
        $bonusNumbers = $this->parseNumbers($this->bonusNumbersInput);
        $requirements = $this->requirements();

        $this->assertNumberSet($mainNumbers, $requirements['main_count'], $requirements['main_min'], $requirements['main_max'], 'mainNumbersInput');
        $this->assertNumberSet($bonusNumbers, $requirements['bonus_count'], $requirements['bonus_min'], $requirements['bonus_max'], 'bonusNumbersInput');

        return [$mainNumbers, $bonusNumbers];
    }

    protected function parseNumbers(string $input): array
    {
        $numbers = preg_split('/[^0-9]+/', $input, -1, PREG_SPLIT_NO_EMPTY);

        $numbers = array_map('intval', $numbers ?: []);
        $numbers = array_values(array_unique($numbers));
        sort($numbers);

        return $numbers;
    }

    protected function assertNumberSet(array $numbers, int $count, int $min, int $max, string $field): void
    {
        if (count($numbers) !== $count) {
            throw ValidationException::withMessages([
                $field => 'Bitte genau '.$count.' unterschiedliche Zahlen eingeben.',
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
