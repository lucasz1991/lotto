<div class="space-y-6" wire:loading.class="opacity-60 pointer-events-none cursor-wait">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Einstellungen</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Projektkonfiguration, Datenquellen und automatische Lotto-Abfragen.
                </p>
            </div>
            <div class="rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Gespeicherte Ziehungen:
                <span class="font-semibold text-slate-950">{{ $drawCount }}</span>
            </div>
        </div>
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

    <x-ui.tabsnav.container
        storageKey="admin-settings.tabs"
        :default="$activeTab"
        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
    >
        <div class="border-b border-gray-200 bg-gray-50 px-5 pt-5">
            <x-ui.tabsnav.nav
                :tabs="[
                    ['id' => 'general', 'label' => 'Allgemein'],
                    ['id' => 'games', 'label' => 'Spiele'],
                ]"
            />
        </div>

        <div class="p-5">
            <x-ui.tabsnav.panel name="general" class="space-y-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Allgemein</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Grundlegende Projektinformationen und Hinweise zur Datenpflege.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Datenbestand</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $drawCount }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Historie</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">Jahresweise per Job scanbar</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Scheduler</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $scrapingScheduleEnabled ? 'Aktiv' : 'Inaktiv' }}</p>
                    </div>
                </div>

            </x-ui.tabsnav.panel>

            <x-ui.tabsnav.panel name="games" class="space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Spiele &amp; Scraping</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Steuerung der automatischen Abfrage, Quellen und historischen Scans.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="openHistoricalScrapeModal"
                        class="inline-flex items-center justify-center rounded-md border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100"
                    >
                        Historie scannen
                    </button>
                </div>

                <x-ui.accordion.tabs
                    :tabs="[
                        'scheduler' => 'Scheduler',
                        'sources' => 'Quellen',
                    ]"
                    default="scheduler"
                    persistKey="admin-settings.games.accordion"
                >
                    <x-slot name="scheduler">
                        <div class="space-y-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Automatische Abfrage</h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Lege fest, wann aktuelle Ziehungsdaten abgefragt werden.
                                    </p>
                                </div>

                                <x-ui.forms.toggle-button
                                    id="scraping-schedule-toggle"
                                    model="scrapingScheduleEnabled"
                                    modifier="defer"
                                    :label="$scrapingScheduleEnabled ? 'Aktiv' : 'Inaktiv'"
                                />
                            </div>

                            <div class="grid gap-5 lg:grid-cols-[220px_1fr]">
                                <label class="block">
                                    <span class="text-sm font-semibold text-gray-700">Uhrzeit</span>
                                    <input
                                        type="time"
                                        wire:model.defer="scrapingScheduleTime"
                                        class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    @error('scrapingScheduleTime')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </label>

                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Wochentage</p>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach ($weekdayLabels as $day => $label)
                                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3" wire:key="weekday-{{ $day }}">
                                                <x-ui.forms.toggle-button
                                                    :id="'weekday-' . $day"
                                                    model="scrapingScheduleWeekdays"
                                                    modifier="defer"
                                                    :value="$day"
                                                    :label="$label"
                                                />
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('scrapingScheduleWeekdays')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('scrapingScheduleWeekdays.*')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
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
                    </x-slot>

                    <x-slot name="sources">
                        <div class="grid gap-6 xl:grid-cols-2">
                            <div class="space-y-5 rounded-lg border border-gray-200 bg-gray-50 p-5">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Lotto 6aus49</h3>
                                    <p class="text-sm text-gray-500">
                                        URL mit aktuellen Lottozahlen inklusive Superzahl.
                                    </p>
                                </div>

                                <label for="lotto-scraping-url" class="block text-sm font-semibold text-gray-700">
                                    Scraping-URL
                                </label>
                                <input
                                    id="lotto-scraping-url"
                                    type="url"
                                    wire:model.defer="lottoScrapingUrl"
                                    placeholder="https://www.lotto.de/lotto-6aus49/lottozahlen"
                                    class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                @error('lottoScrapingUrl')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex flex-wrap justify-end gap-3">
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

                            <div class="space-y-5 rounded-lg border border-gray-200 bg-gray-50 p-5">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">EuroJackpot</h3>
                                    <p class="text-sm text-gray-500">
                                        URL mit aktuellen EuroJackpot-Zahlen.
                                    </p>
                                </div>

                                <label for="eurojackpot-scraping-url" class="block text-sm font-semibold text-gray-700">
                                    Scraping-URL
                                </label>
                                <input
                                    id="eurojackpot-scraping-url"
                                    type="url"
                                    wire:model.defer="euroJackpotScrapingUrl"
                                    placeholder="https://www.lotto.de/eurojackpot/zahlen"
                                    class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                @error('euroJackpotScrapingUrl')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex flex-wrap justify-end gap-3">
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
                    </x-slot>

                </x-ui.accordion.tabs>
            </x-ui.tabsnav.panel>

            {{--
            <x-ui.tabsnav.panel name="csv-import" class="space-y-8">
                CSV-Import bleibt vorerst deaktiviert.
            </x-ui.tabsnav.panel>
            --}}
        </div>
    </x-ui.tabsnav.container>

    <x-dialog-modal wire:model.live="historicalScrapeModalOpen" maxWidth="2xl">
        <x-slot name="title">
            Historische Jahresdaten scannen
        </x-slot>

        <x-slot name="content">
            <div class="space-y-5">
                <p class="text-sm text-gray-500">
                    Fuer jedes ausgewaehlte Jahr wird ein eigener Job gestartet. Jeder Job liest die verfuegbaren Ziehungstage aus und speichert jede Ziehung einzeln.
                </p>

                <div>
                    <p class="text-sm font-semibold text-gray-700">Jahre</p>
                    <div class="mt-2 grid max-h-56 gap-3 overflow-y-auto rounded-md border border-gray-200 bg-gray-50 p-3 sm:grid-cols-3">
                        @foreach ($historicalYearOptions as $year)
                            <div class="rounded-md border border-gray-200 bg-white p-3 shadow-sm" wire:key="historical-year-{{ $year }}">
                                <x-ui.forms.toggle-button
                                    :id="'historical-year-' . $year"
                                    model="historicalScrapeYears"
                                    modifier="defer"
                                    :value="$year"
                                    :label="$year"
                                />
                            </div>
                        @endforeach
                    </div>
                    @error('historicalScrapeYears')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('historicalScrapeYears.*')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700">Spiele</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach ($gameLabels as $game => $label)
                            <div class="rounded-md border border-gray-200 bg-white p-3 shadow-sm" wire:key="historical-game-{{ $game }}">
                                <x-ui.forms.toggle-button
                                    :id="'historical-game-' . $game"
                                    model="historicalScrapeGames"
                                    modifier="defer"
                                    :value="$game"
                                    :label="$label"
                                />
                            </div>
                        @endforeach
                    </div>
                    @error('historicalScrapeGames')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('historicalScrapeGames.*')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Die Jahresauswahl wird aus den Lotto.de-Selectfeldern gelesen. Die Jobs speichern jede Ziehung eindeutig.
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex flex-wrap justify-end gap-3">
                <x-buttons.button-basic
                    type="button"
                    wire:click="closeHistoricalScrapeModal"
                    mode="close"
                    size="sm"
                >
                    Abbrechen
                </x-buttons.button-basic>

                <x-buttons.button-basic
                    type="button"
                    wire:click="startHistoricalYearScrape"
                    wire:loading.attr="disabled"
                    wire:target="startHistoricalYearScrape"
                    mode="submit"
                    size="sm"
                >
                    <span wire:loading.remove wire:target="startHistoricalYearScrape">Job starten</span>
                    <span wire:loading wire:target="startHistoricalYearScrape">Starte...</span>
                </x-buttons.button-basic>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
