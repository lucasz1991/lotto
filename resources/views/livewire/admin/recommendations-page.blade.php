<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Empfehlungen</h1>
            <p class="mt-1 max-w-3xl text-sm text-gray-500">
                Zahlenvorschlaege je Spielart mit eigener Auswertungsart, Feldanzahl und Statistik-Tiefe.
            </p>
        </div>
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">
            Standard: Selten gezogen
        </div>
    </div>

    <div class="grid gap-6 2xl:grid-cols-2">
        @foreach ($recommendations as $recommendation)
            @php
                $game = $recommendation['game'];
                $isEuroJackpot = $game === \App\Models\LotteryDraw::GAME_EUROJACKPOT;
            @endphp

            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-4 sm:px-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md {{ $isEuroJackpot ? 'bg-emerald-600' : 'bg-blue-600' }} text-lg font-bold text-white">
                                    {{ $isEuroJackpot ? 'EJ' : '6' }}
                                </span>
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900">{{ $recommendation['label'] }}</h2>
                                    <p class="mt-1 text-sm text-gray-500">
                                        @if ($recommendation['draw_count'] > 0)
                                            {{ $recommendation['draw_count'] }} Ziehungen, letzte am {{ $recommendation['latest_draw_date']?->format('d.m.Y') }}
                                        @else
                                            Noch keine Daten vorhanden
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-3 xl:min-w-[520px]">
                            <label class="relative block">
                                <span class="sr-only">Auswertungsart</span>
                                <i class="mdi mdi-chart-bell-curve-cumulative pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-lg text-gray-500"></i>
                                <select
                                    wire:model.live="gameOptions.{{ $game }}.method"
                                    class="block h-10 w-full rounded-md border-gray-300 bg-white pl-9 pr-8 text-sm font-medium text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    title="Auswertungsart"
                                >
                                    @foreach ($methodLabels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="relative block">
                                <span class="sr-only">Felder</span>
                                <i class="mdi mdi-view-grid-outline pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-lg text-gray-500"></i>
                                <select
                                    wire:model.live="gameOptions.{{ $game }}.row_count"
                                    class="block h-10 w-full rounded-md border-gray-300 bg-white pl-9 pr-8 text-sm font-medium text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    title="Anzahl Felder"
                                >
                                    @foreach ($rowCountOptions as $count)
                                        <option value="{{ $count }}">{{ $count }} Felder</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="relative block">
                                <span class="sr-only">Top-Zahlen</span>
                                <i class="mdi mdi-format-list-numbered pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-lg text-gray-500"></i>
                                <select
                                    wire:model.live="gameOptions.{{ $game }}.stats_limit"
                                    class="block h-10 w-full rounded-md border-gray-300 bg-white pl-9 pr-8 text-sm font-medium text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    title="Anzahl Statistikzeilen"
                                >
                                    @foreach ($statsLimitOptions as $count)
                                        <option value="{{ $count }}">Top {{ $count }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>
                </div>

                @if ($recommendation['draw_count'] === 0)
                    <div class="px-5 py-10 text-sm text-gray-500">
                        Importiere zuerst CSV-Daten unter <a href="{{ route('admin.settings') }}" class="font-semibold text-blue-600 hover:text-blue-700">Einstellungen</a>, um Empfehlungen zu erhalten.
                    </div>
                @else
                    <div class="space-y-6 px-4 py-5 sm:px-5">

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Empfohlene Felder</h3>
                                <span class="text-xs font-medium text-gray-400">{{ $isEuroJackpot ? 'Gelb = Eurozahlen' : 'Gelb = Superzahl' }}</span>
                            </div>

                            <div class="mt-3 grid gap-3">
                                @foreach ($recommendation['rows'] as $index => $row)
                                    <div class="rounded-md border border-gray-200 px-3 py-3 sm:px-4">
                                        <div class="grid gap-3 sm:grid-cols-[72px_1fr] sm:items-center">
                                            <div class="text-sm font-semibold text-gray-500">Feld {{ $index + 1 }}</div>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($row['main_numbers'] as $number)
                                                    <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-full {{ $isEuroJackpot ? 'bg-emerald-600' : 'bg-blue-600' }} px-3 text-sm font-bold text-white shadow-sm">
                                                        {{ $number }}
                                                    </span>
                                                @endforeach

                                                @foreach ($row['bonus_numbers'] as $number)
                                                    <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-full bg-amber-400 px-3 text-sm font-bold text-gray-900 shadow-sm">
                                                        {{ $number }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="overflow-hidden rounded-md border border-gray-200">
                                <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                                    <i class="mdi mdi-numeric text-lg text-gray-500"></i>
                                    <h3 class="text-sm font-semibold text-gray-900">Hauptzahlen</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                                        <thead class="sticky top-0 bg-white text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-4 py-2">Zahl</th>
                                                <th class="px-4 py-2">Gesamt</th>
                                                <th class="px-4 py-2">Letzte 50</th>
                                                <th class="px-4 py-2">Faellig</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($recommendation['main_stats'] as $stat)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 font-semibold text-gray-900">{{ $stat['number'] }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ $stat['frequency'] }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ $stat['recent_frequency'] }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ $stat['missed_draws'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-md border border-gray-200">
                                <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                                    <i class="mdi mdi-star-four-points-outline text-lg text-gray-500"></i>
                                    <h3 class="text-sm font-semibold text-gray-900">{{ $isEuroJackpot ? 'Eurozahlen' : 'Superzahl' }}</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                                        <thead class="sticky top-0 bg-white text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-4 py-2">Zahl</th>
                                                <th class="px-4 py-2">Gesamt</th>
                                                <th class="px-4 py-2">Letzte 50</th>
                                                <th class="px-4 py-2">Faellig</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($recommendation['bonus_stats'] as $stat)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 font-semibold text-gray-900">{{ $stat['number'] }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ $stat['frequency'] }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ $stat['recent_frequency'] }}</td>
                                                    <td class="px-4 py-2 text-gray-600">{{ $stat['missed_draws'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
