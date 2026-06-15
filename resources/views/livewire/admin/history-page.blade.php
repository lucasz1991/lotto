<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Historie</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Importierte Ziehungen fuer Lotto 6aus49 und EuroJackpot.
                </p>
            </div>

            <div class="w-full sm:w-64">
                <label for="history-game-filter" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Spielart</label>
                <select
                    id="history-game-filter"
                    wire:model.live="game"
                    class="mt-1 block w-full rounded-md border border-gray-300 bg-white p-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">Alle Spielarten</option>
                    @foreach ($gameLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Datum</th>
                        <th class="px-5 py-3">Spielart</th>
                        <th class="px-5 py-3">Gewinnzahlen</th>
                        <th class="px-5 py-3">Zusatz</th>
                        <th class="px-5 py-3">Spieleinsatz</th>
                        <th class="px-5 py-3">Quelle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($draws as $draw)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-900">
                                {{ $draw->draw_date?->format('d.m.Y') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-700">
                                {{ $gameLabels[$draw->game] ?? $draw->game }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($draw->numbers ?? [] as $number)
                                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-blue-600 px-2 text-sm font-semibold text-white">
                                            {{ $number }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-700">
                                @if ($draw->game === \App\Models\LotteryDraw::GAME_EUROJACKPOT)
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach (($draw->bonus_numbers['euro_numbers'] ?? []) as $number)
                                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-yellow-400 px-2 text-sm font-semibold text-gray-900">
                                                {{ $number }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="space-y-1 text-xs">
                                        <div>Superzahl: <span class="font-semibold text-gray-900">{{ $draw->bonus_numbers['superzahl'] ?? '-' }}</span></div>
                                        <div>Spiel77: <span class="font-semibold text-gray-900">{{ $draw->bonus_numbers['spiel77'] ?? '-' }}</span></div>
                                        <div>Super6: <span class="font-semibold text-gray-900">{{ $draw->bonus_numbers['super6'] ?? '-' }}</span></div>
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-700">
                                {{ $draw->stake_cents !== null ? number_format($draw->stake_cents / 100, 2, ',', '.').' EUR' : '-' }}
                            </td>
                            <td class="max-w-xs truncate px-5 py-4 text-gray-500" title="{{ $draw->source_file }}">
                                {{ $draw->source_file ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">
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
