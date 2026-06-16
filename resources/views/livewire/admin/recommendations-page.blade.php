<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-5">
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-gray-900">Empfehlungen</h1>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Zahlenreihen je Spielart, Auswertungsart und Verteilungsstrategie.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.history') }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    <i class="mdi mdi-history text-lg"></i>
                    Historie
                </a>
                <a href="{{ route('admin.settings') }}" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    <i class="mdi mdi-cog-outline text-lg"></i>
                    Einstellungen
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-2 rounded-lg border border-gray-200 bg-white p-1 shadow-sm 2xl:hidden">
        @foreach ($recommendations as $recommendation)
            @php
                $game = $recommendation['game'];
                $isEuroJackpot = $game === \App\Models\LotteryDraw::GAME_EUROJACKPOT;
            @endphp

            <button
                type="button"
                wire:click="showMobileGame('{{ $game }}')"
                class="flex items-center justify-center gap-2 rounded-md px-3 py-2.5 text-sm font-semibold {{ $activeMobileGame === $game ? ($isEuroJackpot ? 'bg-emerald-600 text-white shadow-sm' : 'bg-blue-600 text-white shadow-sm') : 'text-gray-600 hover:bg-gray-50' }}"
            >
                <span class="inline-flex h-6 w-6 items-center justify-center rounded {{ $activeMobileGame === $game ? 'bg-white/20' : ($isEuroJackpot ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white') }} text-xs font-bold">
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
                <div class="border-t-4 {{ $isEuroJackpot ? 'border-t-emerald-500 bg-emerald-50/40' : 'border-t-blue-500 bg-blue-50/40' }} px-4 py-4 sm:px-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md {{ $isEuroJackpot ? 'bg-emerald-600' : 'bg-blue-600' }} text-lg font-bold text-white">
                                    {{ $isEuroJackpot ? 'EJ' : '6' }}
                                </span>
                                <div class="min-w-0">
                                    <h2 class="text-xl font-semibold text-gray-900">{{ $recommendation['label'] }}</h2>
                                    <p class="mt-1 truncate text-sm text-gray-500">
                                        @if ($recommendation['draw_count'] > 0)
                                            {{ $recommendation['draw_count'] }} Ziehungen, letzte am {{ $recommendation['latest_draw_date']?->format('d.m.Y') }}
                                        @else
                                            Noch keine Daten vorhanden
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-gray-700 shadow-sm ring-1 ring-gray-200">
                                    <i class="mdi mdi-chart-bell-curve-cumulative text-base text-gray-500"></i>
                                    {{ $recommendation['method_label'] }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-gray-700 shadow-sm ring-1 ring-gray-200">
                                    <i class="mdi mdi-call-split text-base text-gray-500"></i>
                                    {{ $recommendation['reuse_strategy_label'] }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-gray-700 shadow-sm ring-1 ring-gray-200">
                                    <i class="mdi mdi-shield-check-outline text-base text-gray-500"></i>
                                    Datenbasis {{ $recommendation['confidence'] }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 xl:min-w-[640px]">
                            <x-ui.forms.select
                                model="gameOptions.{{ $game }}.method"
                                :value="$gameOptions[$game]['method']"
                                :options="$methodSelectOptions"
                                title="Auswertungsart"
                                compact-mobile
                            />

                            <x-ui.forms.select
                                model="gameOptions.{{ $game }}.row_count"
                                :value="$gameOptions[$game]['row_count']"
                                :options="$rowCountSelectOptions"
                                title="Anzahl Felder"
                                compact-mobile
                            />

                            <x-ui.forms.select
                                model="gameOptions.{{ $game }}.reuse_strategy"
                                :value="$gameOptions[$game]['reuse_strategy']"
                                :options="$reuseStrategySelectOptions"
                                title="Zahlenverteilung"
                                compact-mobile
                            />
                        </div>
                    </div>
                </div>

                @if ($recommendation['draw_count'] === 0)
                    <div class="border-t border-gray-100 px-5 py-10 text-sm text-gray-500">
                        Importiere zuerst CSV-Daten unter <a href="{{ route('admin.settings') }}" class="font-semibold text-blue-600 hover:text-blue-700">Einstellungen</a>, um Empfehlungen zu erhalten.
                    </div>
                @else
                    <div class="space-y-5 border-t border-gray-100 px-4 py-5 sm:px-5">
                        <div>

                            <div class="grid gap-3">
                                @foreach ($recommendation['rows'] as $index => $row)
                                    <div class="rounded-md border border-gray-200 bg-white px-3 py-3 shadow-sm transition hover:border-gray-300 hover:shadow-md sm:px-4">
                                        <div class="grid gap-3 sm:grid-cols-[80px_1fr] sm:items-center">
                                            <div class="inline-flex w-max items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-sm font-semibold text-gray-700">
                                                <i class="mdi mdi-view-grid-outline text-base text-gray-500"></i>
                                                Feld {{ $index + 1 }}
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($row['main_numbers'] as $number)
                                                    @php($stat = $row['main_number_stats'][$number] ?? [])
                                                    <x-ui.dropdown.anchor-dropdown
                                                        align="top"
                                                        width="auto"
                                                        :offset="8"
                                                        dropdownClasses="mx-0"
                                                        contentClasses="bg-white"
                                                    >
                                                        <x-slot name="trigger">
                                                            <x-ui.lottery.number-ball
                                                                :number="$number"
                                                                :game="$game"
                                                                as="button"
                                                                :label="'Details zu Zahl '.$number"
                                                            />
                                                        </x-slot>

                                                        <x-slot name="content">
                                                            <div class="w-56 p-3 text-left text-xs font-normal text-gray-600">
                                                                <span class="mb-2 block font-semibold text-gray-900">Zahl {{ $number }}</span>
                                                            @if ($recommendation['method'] === \App\Services\Lottery\LotteryRecommendationService::METHOD_RARE)
                                                                <span class="block">Nur {{ $stat['frequency'] ?? 0 }}x insgesamt gezogen.</span>
                                                                <span class="block">Erwartet: {{ $stat['expected_frequency'] ?? '-' }}x.</span>
                                                            @elseif ($recommendation['method'] === \App\Services\Lottery\LotteryRecommendationService::METHOD_OVERDUE)
                                                                <span class="block">{{ $stat['missed_draws'] ?? 0 }} Ziehungen nicht gezogen.</span>
                                                                <span class="block">Zuletzt: {{ ($stat['last_seen_date'] ?? null)?->format('d.m.Y') ?? 'noch nie' }}.</span>
                                                            @elseif ($recommendation['method'] === \App\Services\Lottery\LotteryRecommendationService::METHOD_HOT)
                                                                <span class="block">{{ $stat['frequency'] ?? 0 }}x insgesamt gezogen.</span>
                                                                <span class="block">{{ $stat['recent_frequency'] ?? 0 }}x in den letzten 50 Ziehungen.</span>
                                                            @elseif ($recommendation['method'] === \App\Services\Lottery\LotteryRecommendationService::METHOD_RECENT)
                                                                <span class="block">{{ $stat['recent_frequency'] ?? 0 }}x in den letzten 50 Ziehungen.</span>
                                                                <span class="block">Zuletzt: {{ ($stat['last_seen_date'] ?? null)?->format('d.m.Y') ?? 'noch nie' }}.</span>
                                                            @else
                                                                <span class="block">Score: {{ $stat['score'] ?? '-' }}.</span>
                                                                <span class="block">{{ $stat['frequency'] ?? 0 }}x insgesamt, {{ $stat['missed_draws'] ?? 0 }} Ziehungen faellig.</span>
                                                            @endif
                                                            </div>
                                                        </x-slot>
                                                    </x-ui.dropdown.anchor-dropdown>
                                                @endforeach

                                                @foreach ($row['bonus_numbers'] as $number)
                                                    @php($stat = $row['bonus_number_stats'][$number] ?? [])
                                                    <x-ui.dropdown.anchor-dropdown
                                                        align="top"
                                                        width="auto"
                                                        :offset="8"
                                                        dropdownClasses="mx-0"
                                                        contentClasses="bg-white"
                                                    >
                                                        <x-slot name="trigger">
                                                            <x-ui.lottery.number-ball
                                                                :number="$number"
                                                                :game="$game"
                                                                as="button"
                                                                bonus
                                                                :label="'Details zu Zusatzzahl '.$number"
                                                            />
                                                        </x-slot>

                                                        <x-slot name="content">
                                                            <div class="w-56 p-3 text-left text-xs font-normal text-gray-600">
                                                                <span class="mb-2 block font-semibold text-gray-900">{{ $isEuroJackpot ? 'Eurozahl' : 'Superzahl' }} {{ $number }}</span>
                                                            @if ($recommendation['method'] === \App\Services\Lottery\LotteryRecommendationService::METHOD_RARE)
                                                                <span class="block">Nur {{ $stat['frequency'] ?? 0 }}x insgesamt gezogen.</span>
                                                                <span class="block">Erwartet: {{ $stat['expected_frequency'] ?? '-' }}x.</span>
                                                            @elseif ($recommendation['method'] === \App\Services\Lottery\LotteryRecommendationService::METHOD_OVERDUE)
                                                                <span class="block">{{ $stat['missed_draws'] ?? 0 }} Ziehungen nicht gezogen.</span>
                                                                <span class="block">Zuletzt: {{ ($stat['last_seen_date'] ?? null)?->format('d.m.Y') ?? 'noch nie' }}.</span>
                                                            @elseif ($recommendation['method'] === \App\Services\Lottery\LotteryRecommendationService::METHOD_HOT)
                                                                <span class="block">{{ $stat['frequency'] ?? 0 }}x insgesamt gezogen.</span>
                                                                <span class="block">{{ $stat['recent_frequency'] ?? 0 }}x in den letzten 50 Ziehungen.</span>
                                                            @elseif ($recommendation['method'] === \App\Services\Lottery\LotteryRecommendationService::METHOD_RECENT)
                                                                <span class="block">{{ $stat['recent_frequency'] ?? 0 }}x in den letzten 50 Ziehungen.</span>
                                                                <span class="block">Zuletzt: {{ ($stat['last_seen_date'] ?? null)?->format('d.m.Y') ?? 'noch nie' }}.</span>
                                                            @else
                                                                <span class="block">Score: {{ $stat['score'] ?? '-' }}.</span>
                                                                <span class="block">{{ $stat['frequency'] ?? 0 }}x insgesamt, {{ $stat['missed_draws'] ?? 0 }} Ziehungen faellig.</span>
                                                            @endif
                                                            </div>
                                                        </x-slot>
                                                    </x-ui.dropdown.anchor-dropdown>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-3 grid-cols-2">
                            <button
                                type="button"
                                wire:click="openStatsModal('{{ $game }}', 'main')"
                                class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-white px-4 py-3 text-left shadow-sm hover:border-blue-200 hover:bg-blue-50"
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
                                class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-white px-4 py-3 text-left shadow-sm hover:border-amber-200 hover:bg-amber-50"
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
                                    <td class="px-5 py-3">
                                        <x-ui.lottery.number-ball
                                            :number="$stat['number']"
                                            :game="$selectedStatsModal['game']"
                                            :bonus="$selectedStatsModal['is_bonus']"
                                            size="xs"
                                        />
                                    </td>
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
            <x-buttons.button-basic
                type="button"
                wire:click="closeStatsModal"
                mode="close"
                size="sm"
            >
                <i class="mdi mdi-close-circle-outline me-1 text-lg"></i>
                Schliessen
            </x-buttons.button-basic>
        </x-slot>
    </x-dialog-modal>
</div>
