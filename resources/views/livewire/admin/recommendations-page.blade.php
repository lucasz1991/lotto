<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">Empfehlungen</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-500">
            Datenbasierte Zahlenvorschlaege fuer die naechste Ziehung. Die Berechnung nutzt Haeufigkeit, neuere Ziehungen und laenger nicht gezogene Zahlen aus der importierten Historie.
        </p>
    </div>


    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($recommendations as $recommendation)
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">{{ $recommendation['label'] }}</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                @if ($recommendation['draw_count'] > 0)
                                    {{ $recommendation['draw_count'] }} Ziehungen analysiert, letzte Ziehung am {{ $recommendation['latest_draw_date']?->format('d.m.Y') }}.
                                @else
                                    Noch keine importierten Ziehungen vorhanden.
                                @endif
                            </p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">
                            Vertrauen: {{ $recommendation['confidence'] }}
                        </span>
                    </div>
                </div>

                @if ($recommendation['draw_count'] === 0)
                    <div class="px-6 py-8 text-sm text-gray-500">
                        Importiere zuerst CSV-Daten unter <a href="{{ route('admin.settings') }}" class="font-semibold text-blue-600 hover:text-blue-700">Einstellungen</a>, um Empfehlungen zu erhalten.
                    </div>
                @else
                    <div class="space-y-6 px-6 py-6">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Empfohlene Gewinnzahlen</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($recommendation['main_numbers'] as $number)
                                    <span class="inline-flex h-11 min-w-11 items-center justify-center rounded-full bg-blue-600 px-3 text-base font-bold text-white shadow-sm">
                                        {{ $number }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                {{ $recommendation['game'] === \App\Models\LotteryDraw::GAME_EUROJACKPOT ? 'Empfohlene Eurozahlen' : 'Empfohlene Superzahl' }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($recommendation['bonus_numbers'] as $number)
                                    <span class="inline-flex h-11 min-w-11 items-center justify-center rounded-full bg-yellow-400 px-3 text-base font-bold text-gray-900 shadow-sm">
                                        {{ $number }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-lg border border-gray-200">
                                <div class="border-b border-gray-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold text-gray-900">Top-Hauptzahlen nach Score</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-4 py-2">Zahl</th>
                                                <th class="px-4 py-2">Gesamt</th>
                                                <th class="px-4 py-2">Letzte 50</th>
                                                <th class="px-4 py-2">Faellig</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($recommendation['main_stats'] as $stat)
                                                <tr>
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

                            <div class="rounded-lg border border-gray-200">
                                <div class="border-b border-gray-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold text-gray-900">Top-Zusatzzahlen nach Score</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-4 py-2">Zahl</th>
                                                <th class="px-4 py-2">Gesamt</th>
                                                <th class="px-4 py-2">Letzte 50</th>
                                                <th class="px-4 py-2">Faellig</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($recommendation['bonus_stats'] as $stat)
                                                <tr>
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
