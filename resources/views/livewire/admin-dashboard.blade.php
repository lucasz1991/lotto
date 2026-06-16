<div class="space-y-6 p-2" wire:poll.5000ms="refreshDashboardData">

    <div class="grid gap-4 grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ziehungen</p>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-blue-50 text-blue-700">
                    <i class="mdi mdi-counter text-lg"></i>
                </span>
            </div>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $totalDraws }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $drawsThisYear }} davon im Jahr {{ now()->year }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auto-Abfrage</p>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md {{ $scheduleSettings['enabled'] ?? false ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                    <i class="mdi mdi-calendar-clock text-lg"></i>
                </span>
            </div>
            <p class="mt-3 text-base font-semibold text-gray-900">{{ $scheduleSummary }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $scheduleSettings['enabled'] ?? false ? 'Scheduler aktiv' : 'Scheduler deaktiviert' }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Scraping</p>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-indigo-50 text-indigo-700">
                    <i class="mdi mdi-cloud-sync-outline text-lg"></i>
                </span>
            </div>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $scrapedDrawsTotal }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $latestScrapedDraw?->updated_at?->format('d.m.Y H:i') ?? 'Noch kein Abruf' }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($gameSummaries as $summary)
            @php
                $isEuroJackpot = $summary['game'] === \App\Models\LotteryDraw::GAME_EUROJACKPOT;
                $coverage = $summary['expected_this_year'] > 0
                    ? min(100, round(($summary['draws_this_year'] / $summary['expected_this_year']) * 100))
                    : 0;
                $bonusNumbers = $summary['latest_bonus_numbers'];
                $bonus = $bonusNumbers['euro_numbers'] ?? [$bonusNumbers['superzahl'] ?? null];
                $bonus = array_values(array_filter((array) $bonus, fn ($value) => $value !== null && $value !== ''));
            @endphp

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-t-4 {{ $isEuroJackpot ? 'border-t-emerald-500' : 'border-t-blue-500' }} px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md {{ $isEuroJackpot ? 'bg-emerald-600' : 'bg-blue-600' }} text-sm font-bold text-white">
                                {{ $isEuroJackpot ? 'EJ' : '6' }}
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold text-gray-900">{{ $summary['label'] }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $summary['draw_count'] }} gespeicherte Ziehungen</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ $summary['source_type'] }}
                        </span>
                    </div>
                </div>

                <div class="space-y-5 border-t border-gray-100 px-5 py-5">
                    @if ($summary['latest_draw_date'])
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Letzte Ziehung {{ $summary['latest_draw_date']?->format('d.m.Y') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($summary['latest_numbers'] as $number)
                                    <x-ui.lottery.number-ball :number="$number" :game="$summary['game']" />
                                @endforeach

                                @foreach ($bonus as $number)
                                    <x-ui.lottery.number-ball :number="$number" :game="$summary['game']" bonus />
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-md bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Aktualisiert</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $summary['updated_at']?->format('d.m.Y H:i') }}</p>
                            </div>
                            <div class="rounded-md bg-gray-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Quelle</p>
                                <p class="mt-1 truncate text-sm font-semibold text-gray-900" title="{{ $summary['source_file'] }}">{{ $summary['source_file'] ?: '-' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Fuer diese Spielart sind noch keine Ziehungen gespeichert.</p>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <div>
        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Letzte Ziehungen</h2>
                <a href="{{ route('admin.history') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Alle ansehen</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Spielart</th>
                            <th class="px-5 py-3">Ziehung</th>
                            <th class="px-5 py-3">Zahlen</th>
                            <th class="px-5 py-3">Aktualisiert</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($latestDraws as $draw)
                            @php
                                $isEuroJackpot = $draw->game === \App\Models\LotteryDraw::GAME_EUROJACKPOT;
                                $bonusNumbers = $draw->bonus_numbers ?? [];
                                $bonus = $bonusNumbers['euro_numbers'] ?? [$bonusNumbers['superzahl'] ?? null];
                                $bonus = array_values(array_filter((array) $bonus, fn ($value) => $value !== null && $value !== ''));
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-3 font-semibold text-gray-900">{{ $draw->gameLabel() }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $draw->draw_date?->format('d.m.Y') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex min-w-[220px] flex-wrap gap-1.5">
                                        @foreach ($draw->numbers ?? [] as $number)
                                            <x-ui.lottery.number-ball :number="$number" :game="$draw->game" size="xs" />
                                        @endforeach

                                        @foreach ($bonus as $number)
                                            <x-ui.lottery.number-ball :number="$number" :game="$draw->game" size="xs" bonus />
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $draw->updated_at?->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-500">Noch keine Ziehungen vorhanden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
