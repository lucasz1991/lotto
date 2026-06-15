<div class="space-y-6" wire:loading.class="opacity-60 pointer-events-none cursor-wait">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">Einstellungen</h1>
        <p class="mt-2 text-sm text-gray-500">
            Projektkonfiguration und Datenimport fuer das Lotto-Projekt.
        </p>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="switchTab('general')"
                    class="rounded-md px-4 py-2 text-sm font-semibold {{ $activeTab === 'general' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    Allgemein
                </button>

                <button
                    type="button"
                    wire:click="switchTab('csv-import')"
                    class="rounded-md px-4 py-2 text-sm font-semibold {{ $activeTab === 'csv-import' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    CSV-Import
                </button>

                <button
                    type="button"
                    wire:click="switchTab('games')"
                    class="rounded-md px-4 py-2 text-sm font-semibold {{ $activeTab === 'games' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    Spiele
                </button>
            </div>
        </div>

        @if ($activeTab === 'general')
            <div class="space-y-4 px-6 py-6">
                <h2 class="text-lg font-semibold text-gray-900">Allgemein</h2>
                <p class="text-sm text-gray-500">
                    Weitere Einstellungen koennen hier spaeter ergaenzt werden.
                </p>
            </div>
        @endif

        @if ($activeTab === 'games')
            <div class="space-y-8 px-6 py-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Spiele & Scraping</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Hinterlege pro Spielart eine URL, aus der die aktuell gezogenen Zahlen ausgelesen werden sollen.
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Automatische Abfrage</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Legt fest, wann der Laravel Scheduler die aktuellen Ziehungsdaten abfragt.
                            </p>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                            <input
                                type="checkbox"
                                wire:model.defer="scrapingScheduleEnabled"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            >
                            Aktiv
                        </label>
                    </div>

                    <div class="mt-5 grid gap-5 lg:grid-cols-[220px_1fr]">
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Uhrzeit</span>
                            <input
                                type="time"
                                wire:model.defer="scrapingScheduleTime"
                                class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            @error('scrapingScheduleTime') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </label>

                        <div>
                            <p class="text-sm font-semibold text-gray-700">Wochentage</p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($weekdayLabels as $day => $label)
                                    <label class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm">
                                        <input
                                            type="checkbox"
                                            value="{{ $day }}"
                                            wire:model.defer="scrapingScheduleWeekdays"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                        >
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('scrapingScheduleWeekdays') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('scrapingScheduleWeekdays.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
                        <h3 class="text-base font-semibold text-gray-900">Lotto 6aus49</h3>
                        <label for="lotto-scraping-url" class="mt-5 block text-sm font-semibold text-gray-700">Scraping-URL</label>
                        <input
                            id="lotto-scraping-url"
                            type="url"
                            wire:model.defer="lottoScrapingUrl"
                            placeholder="https://www.lotto.de/lotto-6aus49/lottozahlen"
                            class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        @error('lottoScrapingUrl') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-5 flex flex-wrap justify-end gap-3">
                            <button
                                type="button"
                                wire:click="testScrapeGame('{{ \App\Models\LotteryDraw::GAME_LOTTO_6AUS49 }}')"
                                wire:loading.attr="disabled"
                                wire:target="testScrapeGame('{{ \App\Models\LotteryDraw::GAME_LOTTO_6AUS49 }}')"
                                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="testScrapeGame('{{ \App\Models\LotteryDraw::GAME_LOTTO_6AUS49 }}')">Direkt testen</span>
                                <span wire:loading wire:target="testScrapeGame('{{ \App\Models\LotteryDraw::GAME_LOTTO_6AUS49 }}')">Teste...</span>
                            </button>
                            <button
                                type="button"
                                wire:click="scrapeGame('{{ \App\Models\LotteryDraw::GAME_LOTTO_6AUS49 }}')"
                                wire:loading.attr="disabled"
                                class="rounded-md border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-60"
                            >
                                Job starten
                            </button>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
                        <h3 class="text-base font-semibold text-gray-900">EuroJackpot</h3>
                        <label for="eurojackpot-scraping-url" class="mt-5 block text-sm font-semibold text-gray-700">Scraping-URL</label>
                        <input
                            id="eurojackpot-scraping-url"
                            type="url"
                            wire:model.defer="euroJackpotScrapingUrl"
                            placeholder="https://www.lotto.de/eurojackpot/zahlen"
                            class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        @error('euroJackpotScrapingUrl') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-5 flex flex-wrap justify-end gap-3">
                            <button
                                type="button"
                                wire:click="testScrapeGame('{{ \App\Models\LotteryDraw::GAME_EUROJACKPOT }}')"
                                wire:loading.attr="disabled"
                                wire:target="testScrapeGame('{{ \App\Models\LotteryDraw::GAME_EUROJACKPOT }}')"
                                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="testScrapeGame('{{ \App\Models\LotteryDraw::GAME_EUROJACKPOT }}')">Direkt testen</span>
                                <span wire:loading wire:target="testScrapeGame('{{ \App\Models\LotteryDraw::GAME_EUROJACKPOT }}')">Teste...</span>
                            </button>
                            <button
                                type="button"
                                wire:click="scrapeGame('{{ \App\Models\LotteryDraw::GAME_EUROJACKPOT }}')"
                                wire:loading.attr="disabled"
                                class="rounded-md border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-60"
                            >
                                Job starten
                            </button>
                        </div>
                    </div>
                </div>

                @if ($lastScrapeResult)
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold text-emerald-950">Letztes Scraping-Ergebnis</h3>
                                <p class="mt-1 text-sm text-emerald-800">
                                    Direkt aus der GUI abgerufen und in der Historie gespeichert.
                                </p>
                            </div>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-900">
                                {{ $lastScrapeResult['stored_at'] }}
                            </span>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-4">
                            <div class="rounded-md bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Spielart</p>
                                <p class="mt-2 text-sm font-semibold text-gray-900">{{ $lastScrapeResult['game'] }}</p>
                            </div>
                            <div class="rounded-md bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Datum</p>
                                <p class="mt-2 text-sm font-semibold text-gray-900">{{ $lastScrapeResult['draw_date'] }}</p>
                            </div>
                            <div class="rounded-md bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Zahlen</p>
                                <p class="mt-2 text-sm font-semibold text-gray-900">{{ $lastScrapeResult['numbers'] }}</p>
                            </div>
                            <div class="rounded-md bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Zusatz</p>
                                <p class="mt-2 text-sm font-semibold text-gray-900">{{ $lastScrapeResult['bonus_numbers'] }}</p>
                            </div>
                        </div>

                        <p class="mt-4 break-all text-xs text-emerald-800">
                            Quelle: {{ $lastScrapeResult['source_url'] }}
                        </p>
                    </div>
                @endif

                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                    Lotto.de-URLs werden automatisch ueber die internen JSON-Endpunkte von Lotto.de verarbeitet. Andere Seiten werden weiterhin ueber erkennbare Texte wie <strong>Gewinnzahlen</strong>, <strong>Superzahl</strong> oder <strong>Eurozahlen</strong> geparst.
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        wire:click="saveGameSettings"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-md bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:opacity-60"
                    >
                        Speichern
                    </button>
                </div>
            </div>
        @endif

        @if ($activeTab === 'csv-import')
            <div class="space-y-8 px-6 py-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Ziehungen importieren</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Importiert CSV-Dateien fuer Lotto 6aus49 oder EuroJackpot. Die Spielart wird automatisch anhand der Datei erkannt.
                    </p>
                </div>

                <form wire:submit.prevent="importCsv" class="rounded-lg border border-gray-200 bg-gray-50 p-5">
                    <label for="csv-file" class="block text-sm font-semibold text-gray-700">CSV-Datei</label>
                    <input
                        id="csv-file"
                        type="file"
                        wire:model="csvFile"
                        accept=".csv,.txt,text/csv,text/plain"
                        class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @error('csvFile') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    <div class="mt-4 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                        Erwartete Formate: <strong>Lotto 6aus49</strong> mit sechs Gewinnzahlen und Superzahl oder <strong>EuroJackpot</strong> mit 5 aus 50 plus zwei Eurozahlen.
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">
                            Aktuell gespeicherte Ziehungen: <span class="font-semibold text-gray-900">{{ $drawCount }}</span>
                        </p>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="csvFile,importCsv"
                            class="inline-flex items-center justify-center rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="importCsv">Importieren</span>
                            <span wire:loading wire:target="importCsv">Import laeuft...</span>
                        </button>
                    </div>
                </form>

                @if ($lastImportSummary)
                    <div class="grid gap-4 md:grid-cols-5">
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:col-span-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Spielart</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $lastImportSummary['game'] }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Zeilen</p>
                            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $lastImportSummary['rows_total'] }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Neu</p>
                            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $lastImportSummary['rows_imported'] }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Aktualisiert</p>
                            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $lastImportSummary['rows_updated'] }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Uebersprungen</p>
                            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $lastImportSummary['rows_skipped'] }}</p>
                        </div>
                    </div>
                @endif

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-900">Letzte Importe</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-5 py-3">Datei</th>
                                    <th class="px-5 py-3">Spielart</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Neu</th>
                                    <th class="px-5 py-3">Aktualisiert</th>
                                    <th class="px-5 py-3">Uebersprungen</th>
                                    <th class="px-5 py-3">Datum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($latestImports as $import)
                                    <tr>
                                        <td class="px-5 py-3 text-gray-900">{{ $import->original_filename ?: '-' }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $gameLabels[$import->game] ?? '-' }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $import->status }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $import->rows_imported }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $import->rows_updated }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $import->rows_skipped }}</td>
                                        <td class="px-5 py-3 text-gray-600">{{ $import->created_at?->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-5 py-8 text-center text-gray-500">Noch keine Importe vorhanden.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
