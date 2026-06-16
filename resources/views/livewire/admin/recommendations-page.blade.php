<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Empfehlungen</h1>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-2 2xl:hidden">
        @foreach ($recommendations as $recommendation)
            @php
                $game = $recommendation['game'];
                $isEuroJackpot = $game === \App\Models\LotteryDraw::GAME_EUROJACKPOT;
            @endphp

            <button
                type="button"
                wire:click="showMobileGame('{{ $game }}')"
                class="flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm font-semibold {{ $activeMobileGame === $game ? ($isEuroJackpot ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-blue-200 bg-blue-50 text-blue-800') : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}"
            >
                <span class="inline-flex h-6 w-6 items-center justify-center rounded {{ $isEuroJackpot ? 'bg-emerald-600' : 'bg-blue-600' }} text-xs font-bold text-white">
                    {{ $isEuroJackpot ? 'EJ' : '6' }}
                </span>
                <span class="truncate">{{ $recommendation['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="grid gap-6 2xl:grid-cols-2">
        @foreach ($recommendations as $recommendation)
            @php
                $game = $recommendation['game'];
                $isEuroJackpot = $game === \App\Models\LotteryDraw::GAME_EUROJACKPOT;
            @endphp

            <section class="{{ $activeMobileGame === $game ? 'block' : 'hidden' }} overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm 2xl:block">
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

                        <div class="grid grid-cols-3 gap-2 xl:min-w-[520px]">
                            <x-ui.forms.select wire:model.live="gameOptions.{{ $game }}.method" title="Auswertungsart" compact-mobile>
                                <x-slot name="icon">
                                    <i class="mdi mdi-chart-bell-curve-cumulative text-lg"></i>
                                </x-slot>

                                <option disabled>Auswertungsart</option>
                                @foreach ($methodLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-ui.forms.select>

                            <x-ui.forms.select wire:model.live="gameOptions.{{ $game }}.row_count" title="Anzahl Felder" compact-mobile>
                                <x-slot name="icon">
                                    <i class="mdi mdi-view-grid-outline text-lg"></i>
                                </x-slot>

                                <option disabled>Felder</option>
                                @foreach ($rowCountOptions as $count)
                                    <option value="{{ $count }}">{{ $count }} Felder</option>
                                @endforeach
                            </x-ui.forms.select>

                            <x-ui.forms.select wire:model.live="gameOptions.{{ $game }}.stats_limit" title="Anzahl Statistikzeilen" compact-mobile>
                                <x-slot name="icon">
                                    <i class="mdi mdi-format-list-numbered text-lg"></i>
                                </x-slot>

                                <option disabled>Top-Zahlen</option>
                                @foreach ($statsLimitOptions as $count)
                                    <option value="{{ $count }}">Top {{ $count }}</option>
                                @endforeach
                            </x-ui.forms.select>
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

                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                wire:click="openStatsModal('{{ $game }}', 'main')"
                                class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-left hover:border-blue-200 hover:bg-blue-50"
                            >
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-white text-gray-600 shadow-sm">
                                        <i class="mdi mdi-numeric text-lg"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-gray-900">Hauptzahlen</span>
                                        <span class="block truncate text-xs text-gray-500">Top {{ count($recommendation['main_stats']) }} Statistikwerte</span>
                                    </span>
                                </span>
                                <i class="mdi mdi-open-in-new text-lg text-gray-400"></i>
                            </button>

                            <button
                                type="button"
                                wire:click="openStatsModal('{{ $game }}', 'bonus')"
                                class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-left hover:border-amber-200 hover:bg-amber-50"
                            >
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-white text-gray-600 shadow-sm">
                                        <i class="mdi mdi-star-four-points-outline text-lg"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-gray-900">{{ $isEuroJackpot ? 'Eurozahlen' : 'Superzahl' }}</span>
                                        <span class="block truncate text-xs text-gray-500">Top {{ count($recommendation['bonus_stats']) }} Statistikwerte</span>
                                    </span>
                                </span>
                                <i class="mdi mdi-open-in-new text-lg text-gray-400"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    <x-dialog-modal wire:model.live="statsModalOpen" maxWidth="3xl">
        <x-slot name="title">
            <div>
                <div>{{ $selectedStatsModal['title'] ?? 'Statistik' }}</div>
                @if ($selectedStatsModal)
                    <div class="mt-1 text-sm font-normal text-gray-500">{{ $selectedStatsModal['subtitle'] }}</div>
                @endif
            </div>
        </x-slot>

        <x-slot name="content">
            @if ($selectedStatsModal)
                <div class="-mx-6 -my-4 max-h-[70vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="sticky top-0 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Zahl</th>
                                <th class="px-5 py-3">Gesamt</th>
                                <th class="px-5 py-3">Letzte 50</th>
                                <th class="px-5 py-3">Faellig</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($selectedStatsModal['stats'] as $stat)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 font-semibold text-gray-900">{{ $stat['number'] }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $stat['frequency'] }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $stat['recent_frequency'] }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $stat['missed_draws'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <button
                type="button"
                wire:click="closeStatsModal"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Schliessen
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
