<?php

namespace App\Livewire\Admin\Config;

use App\Jobs\ScrapeLotteryDraw;
use App\Models\LotteryDraw;
use App\Models\LotteryImport;
use App\Models\Setting;
use App\Services\Lottery\LotteryCsvImportService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class SettingsPage extends Component
{
    use WithFileUploads;

    public string $activeTab = 'csv-import';

    public $csvFile = null;

    public ?array $lastImportSummary = null;

    public string $lottoScrapingUrl = '';

    public string $euroJackpotScrapingUrl = '';

    public function mount(): void
    {
        $this->loadGameSettings();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['general', 'games', 'csv-import'], true) ? $tab : 'general';
    }

    public function saveGameSettings(): void
    {
        $validated = $this->validate([
            'lottoScrapingUrl' => ['nullable', 'url', 'max:2048'],
            'euroJackpotScrapingUrl' => ['nullable', 'url', 'max:2048'],
        ]);

        Setting::setValue('lottery', 'games', [
            LotteryDraw::GAME_LOTTO_6AUS49 => [
                'scraping_url' => trim((string) $validated['lottoScrapingUrl']),
            ],
            LotteryDraw::GAME_EUROJACKPOT => [
                'scraping_url' => trim((string) $validated['euroJackpotScrapingUrl']),
            ],
        ]);

        session()->flash('success', 'Spiel-Einstellungen wurden gespeichert.');
    }

    public function scrapeGame(string $game): void
    {
        if (! array_key_exists($game, LotteryDraw::gameLabels())) {
            session()->flash('error', 'Unbekannte Spielart.');

            return;
        }

        $this->saveGameSettings();

        $url = $game === LotteryDraw::GAME_LOTTO_6AUS49
            ? $this->lottoScrapingUrl
            : $this->euroJackpotScrapingUrl;

        if (trim($url) === '') {
            session()->flash('error', 'Bitte zuerst eine Scraping-URL fuer '.(LotteryDraw::gameLabels()[$game] ?? $game).' hinterlegen.');

            return;
        }

        ScrapeLotteryDraw::dispatch($game, trim($url));

        session()->flash('success', 'Scraping-Job fuer '.(LotteryDraw::gameLabels()[$game] ?? $game).' wurde gestartet.');
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
        ])->layout('layouts.master');
    }

    protected function loadGameSettings(): void
    {
        $settings = Setting::getValue('lottery', 'games');
        $settings = is_array($settings) ? $settings : [];

        $this->lottoScrapingUrl = trim((string) ($settings[LotteryDraw::GAME_LOTTO_6AUS49]['scraping_url'] ?? ''));
        $this->euroJackpotScrapingUrl = trim((string) ($settings[LotteryDraw::GAME_EUROJACKPOT]['scraping_url'] ?? ''));
    }
}
