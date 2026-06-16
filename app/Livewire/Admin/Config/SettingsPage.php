<?php

namespace App\Livewire\Admin\Config;

use App\Jobs\ScrapeLotteryDraw;
use App\Jobs\ScrapeLotteryHistoricalYear;
use App\Models\LotteryDraw;
use App\Models\LotteryImport;
use App\Models\Setting;
use App\Services\Lottery\LotteryCsvImportService;
use App\Services\Lottery\LotteryDrawScrapingService;
use App\Services\Lottery\LotteryScrapingSchedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class SettingsPage extends Component
{
    use WithFileUploads;

    public string $activeTab = 'general';

    public $csvFile = null;

    public ?array $lastImportSummary = null;

    public ?array $lastScrapeResult = null;

    public string $lottoScrapingUrl = '';

    public string $euroJackpotScrapingUrl = '';

    public bool $scrapingScheduleEnabled = true;

    public string $scrapingScheduleTime = LotteryScrapingSchedule::DEFAULT_TIME;

    public array $scrapingScheduleWeekdays = LotteryScrapingSchedule::DEFAULT_WEEKDAYS;

    public bool $historicalScrapeModalOpen = false;

    public array $historicalScrapeYears = [];

    public array $historicalScrapeGames = [];

    public array $historicalYearOptions = [];

    public ?array $lastHistoricalScrapeDispatch = null;

    public function mount(): void
    {
        $this->loadGameSettings(app(LotteryScrapingSchedule::class));
        $this->historicalScrapeYears = [(int) now()->year];
        $this->historicalScrapeGames = array_keys(LotteryDraw::gameLabels());
        $this->historicalYearOptions = $this->fallbackHistoricalYearOptions();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['general', 'games'], true) ? $tab : 'general';
    }

    public function saveGameSettings(): void
    {
        $this->persistGameSettings();

        session()->flash('success', 'Spiel-Einstellungen wurden gespeichert.');
    }

    public function scrapeGame(string $game): void
    {
        if (! array_key_exists($game, LotteryDraw::gameLabels())) {
            session()->flash('error', 'Unbekannte Spielart.');

            return;
        }

        $this->persistGameSettings();
        $url = $this->scrapingUrlFor($game);

        if (trim($url) === '') {
            session()->flash('error', 'Bitte zuerst eine Scraping-URL fuer '.(LotteryDraw::gameLabels()[$game] ?? $game).' hinterlegen.');

            return;
        }

        ScrapeLotteryDraw::dispatch($game, trim($url));

        session()->flash('success', 'Scraping-Job fuer '.(LotteryDraw::gameLabels()[$game] ?? $game).' wurde gestartet.');
    }

    public function testScrapeGame(string $game): void
    {
        $this->lastScrapeResult = null;

        if (! array_key_exists($game, LotteryDraw::gameLabels())) {
            session()->flash('error', 'Unbekannte Spielart.');

            return;
        }

        $this->persistGameSettings();
        $url = $this->scrapingUrlFor($game);

        if (trim($url) === '') {
            session()->flash('error', 'Bitte zuerst eine Scraping-URL fuer '.(LotteryDraw::gameLabels()[$game] ?? $game).' hinterlegen.');

            return;
        }

        try {
            $draw = app(LotteryDrawScrapingService::class)->scrapeGame($game, trim($url));

            $this->lastScrapeResult = [
                'game' => LotteryDraw::gameLabels()[$draw->game] ?? $draw->game,
                'draw_date' => $draw->draw_date?->format('d.m.Y'),
                'numbers' => implode(' - ', $draw->numbers ?? []),
                'bonus_numbers' => $this->formatBonusNumbers($draw->bonus_numbers ?? []),
                'source_url' => $url,
                'stored_at' => now()->format('d.m.Y H:i:s'),
            ];

            session()->flash('success', 'Direkter Scrape fuer '.(LotteryDraw::gameLabels()[$game] ?? $game).' war erfolgreich.');
        } catch (Throwable $exception) {
            session()->flash('error', 'Direkter Scrape fehlgeschlagen: '.$exception->getMessage());
        }
    }

    public function openHistoricalScrapeModal(): void
    {
        $this->resetErrorBag();
        $this->loadHistoricalYearOptions();
        $this->historicalScrapeModalOpen = true;
    }

    public function closeHistoricalScrapeModal(): void
    {
        $this->historicalScrapeModalOpen = false;
    }

    public function startHistoricalYearScrape(): void
    {
        $this->persistGameSettings();

        $validated = $this->validate([
            'historicalScrapeYears' => ['required', 'array', 'min:1'],
            'historicalScrapeYears.*' => ['required', 'integer', 'min:1955', 'max:'.now()->year],
            'historicalScrapeGames' => ['required', 'array', 'min:1'],
            'historicalScrapeGames.*' => ['required', Rule::in(array_keys(LotteryDraw::gameLabels()))],
        ]);

        $years = collect($validated['historicalScrapeYears'])
            ->map(fn (mixed $year): int => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
        $games = array_values(array_unique($validated['historicalScrapeGames']));
        $urls = collect($games)
            ->mapWithKeys(fn (string $game): array => [$game => trim($this->scrapingUrlFor($game))])
            ->all();

        foreach ($years as $year) {
            ScrapeLotteryHistoricalYear::dispatch($year, $games, $urls);
        }

        $this->lastHistoricalScrapeDispatch = [
            'years' => $years,
            'job_count' => count($years),
            'games' => collect($games)
                ->map(fn (string $game): string => LotteryDraw::gameLabels()[$game] ?? $game)
                ->values()
                ->all(),
            'started_at' => now()->format('d.m.Y H:i:s'),
        ];
        $this->historicalScrapeModalOpen = false;

        session()->flash('success', count($years).' historische Jahres-Scan-Jobs wurden gestartet.');
    }

    public function importCsv(LotteryCsvImportService $importer): void
    {
        $validated = $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
        ]);

        $uploadedFile = $validated['csvFile'];
        $storedPath = $uploadedFile->store('imports/lottery-csv', 'private');
        $absolutePath = Storage::disk('private')->path($storedPath);

        try {
            $import = $importer->import(
                path: $absolutePath,
                originalFilename: $uploadedFile->getClientOriginalName(),
                storedPath: $storedPath,
                disk: 'private',
            );

            $this->lastImportSummary = [
                'game' => LotteryDraw::gameLabels()[$import->game] ?? $import->game,
                'rows_total' => $import->rows_total,
                'rows_imported' => $import->rows_imported,
                'rows_updated' => $import->rows_updated,
                'rows_skipped' => $import->rows_skipped,
            ];

            $this->reset('csvFile');
            session()->flash('success', 'CSV-Datei wurde importiert.');
        } catch (Throwable $exception) {
            session()->flash('error', 'CSV-Import fehlgeschlagen: '.$exception->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.config.settings-page', [
            'drawCount' => LotteryDraw::query()->count(),
            'latestImports' => LotteryImport::query()->latest()->limit(8)->get(),
            'gameLabels' => LotteryDraw::gameLabels(),
            'historicalYearOptions' => $this->historicalYearOptions ?: $this->fallbackHistoricalYearOptions(),
            'weekdayLabels' => app(LotteryScrapingSchedule::class)->weekdayLabels(),
        ])->layout('layouts.master', ['title' => 'Einstellungen']);
    }

    protected function loadHistoricalYearOptions(): void
    {
        $scraper = app(LotteryDrawScrapingService::class);
        $years = collect(array_keys(LotteryDraw::gameLabels()))
            ->flatMap(fn (string $game): array => $scraper->availableHistoricalYears($game, $this->scrapingUrlFor($game)))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $this->historicalYearOptions = $years ?: $this->fallbackHistoricalYearOptions();
    }

    protected function fallbackHistoricalYearOptions(): array
    {
        return range((int) now()->year, 1955);
    }

    protected function loadGameSettings(LotteryScrapingSchedule $schedule): void
    {
        $settings = Setting::getValue('lottery', 'games');
        $settings = is_array($settings) ? $settings : [];

        $this->lottoScrapingUrl = trim((string) ($settings[LotteryDraw::GAME_LOTTO_6AUS49]['scraping_url'] ?? LotteryDrawScrapingService::DEFAULT_URLS[LotteryDraw::GAME_LOTTO_6AUS49]));
        $this->euroJackpotScrapingUrl = trim((string) ($settings[LotteryDraw::GAME_EUROJACKPOT]['scraping_url'] ?? LotteryDrawScrapingService::DEFAULT_URLS[LotteryDraw::GAME_EUROJACKPOT]));

        $scheduleSettings = $schedule->settings();
        $this->scrapingScheduleEnabled = (bool) $scheduleSettings['enabled'];
        $this->scrapingScheduleTime = $scheduleSettings['time'];
        $this->scrapingScheduleWeekdays = array_map('strval', $scheduleSettings['weekdays']);
    }

    protected function persistGameSettings(): void
    {
        $validated = $this->validate([
            'lottoScrapingUrl' => ['nullable', 'url', 'max:2048'],
            'euroJackpotScrapingUrl' => ['nullable', 'url', 'max:2048'],
            'scrapingScheduleEnabled' => ['boolean'],
            'scrapingScheduleTime' => ['required', 'date_format:H:i'],
            'scrapingScheduleWeekdays' => ['required', 'array', 'min:1'],
            'scrapingScheduleWeekdays.*' => ['integer', 'between:0,6'],
        ]);

        Setting::setValue('lottery', 'games', [
            LotteryDraw::GAME_LOTTO_6AUS49 => [
                'scraping_url' => trim((string) $validated['lottoScrapingUrl']),
            ],
            LotteryDraw::GAME_EUROJACKPOT => [
                'scraping_url' => trim((string) $validated['euroJackpotScrapingUrl']),
            ],
        ]);

        app(LotteryScrapingSchedule::class)->save(
            enabled: (bool) $validated['scrapingScheduleEnabled'],
            time: $validated['scrapingScheduleTime'],
            weekdays: $validated['scrapingScheduleWeekdays'],
        );
    }

    protected function scrapingUrlFor(string $game): string
    {
        return $game === LotteryDraw::GAME_LOTTO_6AUS49
            ? $this->lottoScrapingUrl
            : $this->euroJackpotScrapingUrl;
    }

    protected function formatBonusNumbers(array $bonusNumbers): string
    {
        if (array_key_exists('superzahl', $bonusNumbers)) {
            return 'Superzahl: '.($bonusNumbers['superzahl'] ?? '-');
        }

        if (array_key_exists('euro_numbers', $bonusNumbers)) {
            return 'Eurozahlen: '.implode(' - ', $bonusNumbers['euro_numbers'] ?? []);
        }

        if ($bonusNumbers === []) {
            return '-';
        }

        return collect($bonusNumbers)
            ->map(fn ($value, string $key) => $key.': '.(is_array($value) ? implode(' - ', $value) : $value))
            ->implode(' | ');
    }
}
