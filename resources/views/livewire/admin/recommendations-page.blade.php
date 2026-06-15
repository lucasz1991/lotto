<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">Empfehlungen</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-500">
            Datenbasierte Zahlenvorschlaege fuer die naechste Ziehung. Die Berechnung nutzt Haeufigkeit, neuere Ziehungen und laenger nicht gezogene Zahlen aus der importierten Historie.
        </p>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <label class="block">
                <span class="text-sm font-medium text-gray-700">Auswertungsart</span>
                <select wire:model.live="method" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach ($methodLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-medium text-gray-700">Felder</span>
                <select wire:model.live="rowCount" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach ([1, 2, 3, 4, 5, 6, 8, 10] as $count)
                        <option value="{{ $count }}">{{ $count }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-medium text-gray-700">Top-Zahlen</span>
                <select wire:model.live="statsLimit" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach ([10, 12, 20, 30, 40, 50] as $count)
                        <option value="{{ $count }}">{{ $count }}</option>
                    @endforeach
                </select>
            </label>
        </div>
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
                            {{ $recommendation['method_label'] }} - Vertrauen: {{ $recommendation['confidence'] }}
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
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Empfohlene Felder</p>
                            <div class="mt-3 space-y-3">
                                @foreach ($recommendation['rows'] as $index => $row)
                                    <div class="rounded-lg border border-gray-200 px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="mr-2 text-sm font-semibold text-gray-500">Feld {{ $index + 1 }}</span>
                                            @foreach ($row['main_numbers'] as $number)
                                                <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-full bg-blue-600 px-3 text-sm font-bold text-white shadow-sm">
                                                    {{ $number }}
                                                </span>
                                            @endforeach

                                            @foreach ($row['bonus_numbers'] as $number)
                                                <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-full bg-yellow-400 px-3 text-sm font-bold text-gray-900 shadow-sm">
                                                    {{ $number }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-lg border border-gray-200">
                                <div class="border-b border-gray-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold text-gray-900">Hauptzahlen: {{ $recommendation['method_label'] }}</h3>
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
                                    <h3 class="text-sm font-semibold text-gray-900">Zusatzzahlen: {{ $recommendation['method_label'] }}</h3>
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
