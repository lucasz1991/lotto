<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Historie</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Gespeicherte Ziehungen, Quellen und Quotenstatus.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.settings') }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        <i class="mdi mdi-cog-outline text-lg"></i>
                        Einstellungen
                    </a>
                    <a href="{{ route('admin.recommendations') }}" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        <i class="mdi mdi-lightbulb-on-outline text-lg"></i>
                        Empfehlungen
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-3 px-5 py-5 sm:grid-cols-3">
            <div class="rounded-md bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Eintraege</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $totalDraws }}</p>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Neueste Ziehung</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $latestDraw?->draw_date?->format('d.m.Y') ?? '-' }}</p>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Aelteste Ziehung</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $oldestDraw?->draw_date?->format('d.m.Y') ?? '-' }}</p>
            </div>
        </div>

        <div class="border-t border-slate-100 px-5 py-4">
            <div class="grid gap-3 md:grid-cols-[minmax(220px,320px)_140px_1fr] md:items-end">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Spielart</span>
                    <select
                        wire:model.live="game"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white p-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Alle Spielarten</option>
                        @foreach ($gameLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pro Seite</span>
                    <select
                        wire:model.live="perPage"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white p-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        @foreach ([10, 25, 50, 100] as $count)
                            <option value="{{ $count }}">{{ $count }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="text-sm text-gray-500 md:text-right">
                    Sortierung: <span class="font-semibold text-gray-900">{{ $sortField }}</span>
                    <span class="font-semibold text-gray-900">{{ $sortDirection === 'asc' ? 'aufsteigend' : 'absteigend' }}</span>
                </div>
            </div>
        </div>
    </div>

    @php
        $sortIcon = fn (string $field): string => $sortField === $field
            ? ($sortDirection === 'asc' ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down')
            : 'mdi mdi-swap-vertical';
    @endphp

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">
                            <button type="button" wire:click="sortBy('draw_date')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                Datum <i class="{{ $sortIcon('draw_date') }} text-base"></i>
                            </button>
                        </th>
                        <th class="px-5 py-3">
                            <button type="button" wire:click="sortBy('game')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                Spielart <i class="{{ $sortIcon('game') }} text-base"></i>
                            </button>
                        </th>
                        <th class="px-5 py-3">Gewinnzahlen</th>
                        <th class="px-5 py-3">Zusatz</th>
                        <th class="px-5 py-3">
                            <button type="button" wire:click="sortBy('stake_cents')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                Spieleinsatz <i class="{{ $sortIcon('stake_cents') }} text-base"></i>
                            </button>
                        </th>
                        <th class="px-5 py-3">
                            <button type="button" wire:click="sortBy('source_file')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                Quelle <i class="{{ $sortIcon('source_file') }} text-base"></i>
                            </button>
                        </th>
                        <th class="px-5 py-3">
                            <button type="button" wire:click="sortBy('updated_at')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                Aktualisiert <i class="{{ $sortIcon('updated_at') }} text-base"></i>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($draws as $draw)
                        @php($isEuroJackpot = $draw->game === \App\Models\LotteryDraw::GAME_EUROJACKPOT)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-900">
                                {{ $draw->draw_date?->format('d.m.Y') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex items-center gap-2 rounded-full {{ $isEuroJackpot ? 'bg-emerald-50 text-emerald-800' : 'bg-blue-50 text-blue-800' }} px-3 py-1 text-xs font-semibold">
                                    <span class="h-2 w-2 rounded-full {{ $isEuroJackpot ? 'bg-emerald-500' : 'bg-blue-500' }}"></span>
                                    {{ $gameLabels[$draw->game] ?? $draw->game }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex min-w-[220px] flex-wrap gap-1.5">
                                    @foreach ($draw->numbers ?? [] as $number)
                                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full {{ $isEuroJackpot ? 'bg-emerald-600' : 'bg-blue-600' }} px-2 text-sm font-semibold text-white shadow-sm">
                                            {{ $number }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-700">
                                @if ($isEuroJackpot)
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach (($draw->bonus_numbers['euro_numbers'] ?? []) as $number)
                                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-amber-400 px-2 text-sm font-semibold text-gray-900 shadow-sm">
                                                {{ $number }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="grid min-w-[180px] gap-1 text-xs text-gray-600">
                                        <div>Superzahl <span class="font-semibold text-gray-900">{{ $draw->bonus_numbers['superzahl'] ?? '-' }}</span></div>
                                        <div>Spiel77 <span class="font-semibold text-gray-900">{{ $draw->bonus_numbers['spiel77'] ?? '-' }}</span></div>
                                        <div>Super6 <span class="font-semibold text-gray-900">{{ $draw->bonus_numbers['super6'] ?? '-' }}</span></div>
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-700">
                                {{ $draw->stake_cents !== null ? number_format($draw->stake_cents / 100, 2, ',', '.').' EUR' : '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="max-w-xs truncate text-gray-500" title="{{ $draw->source_file }}">
                                    {{ $draw->source_file ?: '-' }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-500">
                                {{ $draw->updated_at?->format('d.m.Y H:i') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">
                                Noch keine Ziehungen importiert.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($draws->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $draws->links() }}
            </div>
        @endif
    </div>
</div>
