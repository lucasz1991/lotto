<?php

namespace App\Livewire;

use App\Models\LotteryDraw;
use App\Models\LotteryImport;
use App\Models\User;
use App\Services\Lottery\LotteryScrapingSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AdminDashboard extends Component
{
    public int $totalUsers = 0;

    public int $totalDraws = 0;

    public array $gameSummaries = [];

    public array $scheduleSettings = [];

    public string $scheduleSummary = '';

    public int $drawsThisYear = 0;

    public int $importsTotal = 0;

    public int $scrapedDrawsTotal = 0;

    public function mount(): void
    {
        $this->totalUsers = Schema::hasTable('users') ? User::count() : 0;
        $this->totalDraws = Schema::hasTable('lottery_draws') ? LotteryDraw::query()->count() : 0;
        $this->drawsThisYear = Schema::hasTable('lottery_draws')
            ? LotteryDraw::query()->whereYear('draw_date', now()->year)->count()
            : 0;
        $this->importsTotal = Schema::hasTable('lottery_imports') ? LotteryImport::query()->count() : 0;
        $this->scrapedDrawsTotal = Schema::hasTable('lottery_draws')
            ? LotteryDraw::query()->whereNull('lottery_import_id')->count()
            : 0;

        $schedule = app(LotteryScrapingSchedule::class);
        $this->scheduleSettings = $schedule->settings();
        $this->scheduleSummary = $schedule->summary($this->scheduleSettings);
        $this->gameSummaries = $this->buildGameSummaries();
    }

    public function render()
    {
        return view('livewire.admin-dashboard', [
            'latestDraws' => Schema::hasTable('lottery_draws')
                ? LotteryDraw::query()->latest('draw_date')->limit(6)->get()
                : collect(),
            'latestImport' => Schema::hasTable('lottery_imports')
                ? LotteryImport::query()->latest()->first()
                : null,
            'latestScrapedDraw' => Schema::hasTable('lottery_draws')
                ? LotteryDraw::query()->whereNull('lottery_import_id')->latest('updated_at')->first()
                : null,
            'recentScrapedDraws' => Schema::hasTable('lottery_draws')
                ? LotteryDraw::query()->whereNull('lottery_import_id')->latest('updated_at')->limit(5)->get()
                : collect(),
        ])->layout('layouts.master');
    }

    protected function buildGameSummaries(): array
    {
        if (! Schema::hasTable('lottery_draws')) {
            return [];
        }

        return collect(LotteryDraw::gameLabels())
            ->map(function (string $label, string $game): array {
                $latestDraw = LotteryDraw::query()
                    ->where('game', $game)
                    ->latest('draw_date')
                    ->first();

                return [
                    'game' => $game,
                    'label' => $label,
                    'draw_count' => LotteryDraw::query()->where('game', $game)->count(),
                    'draws_this_year' => LotteryDraw::query()
                        ->where('game', $game)
                        ->whereYear('draw_date', now()->year)
                        ->count(),
                    'expected_this_year' => $this->expectedDrawsThisYear($game),
                    'latest_draw_date' => $latestDraw?->draw_date,
                    'latest_numbers' => $latestDraw?->numbers ?? [],
                    'latest_bonus_numbers' => $latestDraw?->bonus_numbers ?? [],
                    'source_file' => $latestDraw?->source_file,
                    'source_type' => $latestDraw?->lottery_import_id ? 'CSV' : ($latestDraw ? 'Scraping' : '-'),
                    'updated_at' => $latestDraw?->updated_at,
                ];
            })
            ->values()
            ->all();
    }

    protected function expectedDrawsThisYear(string $game): int
    {
        $drawWeekdays = match ($game) {
            LotteryDraw::GAME_LOTTO_6AUS49 => [3, 6],
            LotteryDraw::GAME_EUROJACKPOT => [2, 5],
            default => [],
        };

        $date = CarbonImmutable::create(now()->year, 1, 1, 0, 0, 0, config('app.timezone'))->startOfDay();
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $count = 0;

        while ($date->lessThanOrEqualTo($today)) {
            if (in_array($date->dayOfWeekIso, $drawWeekdays, true)) {
                $count++;
            }

            $date = $date->addDay();
        }

        return $count;
    }
}
