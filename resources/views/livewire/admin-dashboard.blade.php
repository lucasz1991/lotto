<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Lotto-Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">
                Datenbestand, automatische Abfrage und letzte Ziehungen im Ueberblick.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.settings') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Einstellungen
            </a>
            <a href="{{ route('admin.recommendations') }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Empfehlungen
            </a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ziehungen</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $totalDraws }}</p>
            <p class="mt-1 text-xs text-gray-500">Gespeicherte Datensaetze</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auto-Abfrage</p>
            <p class="mt-3 text-base font-semibold text-gray-900">{{ $scheduleSummary }}</p>
            <p class="mt-1 text-xs text-gray-500">Laravel Scheduler</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Letzter Import</p>
            <p class="mt-3 text-base font-semibold text-gray-900">
                {{ $latestImport?->created_at?->format('d.m.Y H:i') ?? '-' }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
                {{ $latestImport?->original_filename ?? 'Noch kein CSV-Import' }}
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Letzter Abruf</p>
            <p class="mt-3 text-base font-semibold text-gray-900">
                {{ $latestScrapedDraw?->updated_at?->format('d.m.Y H:i') ?? '-' }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
                {{ $latestScrapedDraw ? ($latestScrapedDraw->gameLabel().' '.$latestScrapedDraw->draw_date?->format('d.m.Y')) : 'Noch kein Scraping' }}
            </p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($gameSummaries as $summary)
            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $summary['label'] }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $summary['draw_count'] }} gespeicherte Ziehungen</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ $summary['latest_draw_date']?->format('d.m.Y') ?? 'Keine Daten' }}
                        </span>
                    </div>
                </div>

                <div class="space-y-4 px-5 py-5">
                    @if ($summary['latest_draw_date'])
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Letzte Gewinnzahlen</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($summary['latest_numbers'] as $number)
                                    <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-full bg-blue-600 px-3 text-sm font-bold text-white shadow-sm">
                                        {{ $number }}
                                    </span>
                                @endforeach

                                @php
                                    $bonusNumbers = $summary['latest_bonus_numbers'];
                                    $bonus = $bonusNumbers['euro_numbers'] ?? [$bonusNumbers['superzahl'] ?? null];
                                    $bonus = array_values(array_filter((array) $bonus, fn ($value) => $value !== null && $value !== ''));
                                @endphp

                                @foreach ($bonus as $number)
                                    <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-full bg-yellow-400 px-3 text-sm font-bold text-gray-900 shadow-sm">
                                        {{ $number }}
                                    </span>
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
                        <p class="text-sm text-gray-500">
                            Fuer diese Spielart sind noch keine Ziehungen gespeichert.
                        </p>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Letzte gespeicherte Ziehungen</h2>
            <a href="{{ route('admin.history') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Historie ansehen</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Spielart</th>
                        <th class="px-5 py-3">Ziehung</th>
                        <th class="px-5 py-3">Zahlen</th>
                        <th class="px-5 py-3">Quelle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($latestDraws as $draw)
                        <tr>
                            <td class="px-5 py-3 font-semibold text-gray-900">{{ $draw->gameLabel() }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $draw->draw_date?->format('d.m.Y') }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ implode(' - ', $draw->numbers ?? []) }}</td>
                            <td class="max-w-xs truncate px-5 py-3 text-gray-600" title="{{ $draw->source_file }}">{{ $draw->source_file ?: '-' }}</td>
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
