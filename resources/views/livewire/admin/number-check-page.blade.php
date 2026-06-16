<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-5">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Zahlencheck</h1>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Pruefe eigene Zahlenreihen gegen historische Ziehungen und speichere Kombinationen fuer Langzeitvergleiche.
                </p>
            </div>
            <a href="{{ route('admin.recommendations') }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Empfehlungen
            </a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Kombination testen</h2>
            </div>

            <div class="space-y-5 px-5 py-5">
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Spielart</span>
                    <select wire:model.live="game" class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($gameLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Name</span>
                    <input
                        type="text"
                        wire:model.defer="label"
                        placeholder="Optional, z. B. Systemschein A"
                        class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @error('label') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Hauptzahlen</span>
                    <input
                        type="text"
                        wire:model.defer="mainNumbersInput"
                        placeholder="{{ $requirements['main_count'] }} Zahlen von {{ $requirements['main_min'] }} bis {{ $requirements['main_max'] }}"
                        class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @error('mainNumbersInput') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">{{ $requirements['bonus_label'] }}</span>
                    <input
                        type="text"
                        wire:model.defer="bonusNumbersInput"
                        placeholder="{{ $requirements['bonus_count'] }} Zahl{{ $requirements['bonus_count'] > 1 ? 'en' : '' }} von {{ $requirements['bonus_min'] }} bis {{ $requirements['bonus_max'] }}"
                        class="mt-2 block w-full rounded-md border border-gray-300 bg-white p-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @error('bonusNumbersInput') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </label>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Der Score ist eine statistische Einordnung aus vorhandenen Ziehungen, keine Garantie fuer eine kommende Ziehung.
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <button
                        type="button"
                        wire:click="analyze"
                        wire:loading.attr="disabled"
                        class="rounded-md border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 disabled:opacity-60"
                    >
                        Testen
                    </button>
                    <button
                        type="button"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                    >
                        Speichern
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Aktuelle Bewertung</h2>
            </div>

            <div class="px-5 py-5">
                @if ($currentAnalysis)
                    <div class="grid gap-5 lg:grid-cols-[180px_1fr]">
                        <div class="rounded-lg border border-gray-200 bg-slate-50 p-5 text-center">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Score</p>
                            <p class="mt-2 text-4xl font-semibold text-gray-900">{{ $currentAnalysis['score'] }}</p>
                            <p class="mt-1 text-sm font-semibold text-blue-700">{{ $currentAnalysis['rating'] }}</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Zahlenreihe</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($currentAnalysis['main_numbers'] as $number)
                                        <x-ui.lottery.number-ball :number="$number" :game="$game" />
                                    @endforeach
                                    @foreach ($currentAnalysis['bonus_numbers'] as $number)
                                        <x-ui.lottery.number-ball :number="$number" :game="$game" bonus />
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-md bg-gray-50 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Datenbasis</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $currentAnalysis['draw_count'] }} Ziehungen</p>
                                </div>
                                <div class="rounded-md bg-gray-50 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Beste Historie</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">
                                        {{ $currentAnalysis['history']['main_hits'] ?? 0 }} + {{ $currentAnalysis['history']['bonus_hits'] ?? 0 }}
                                    </p>
                                </div>
                                <div class="rounded-md bg-gray-50 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Datum</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $currentAnalysis['history']['draw_date'] ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="py-12 text-center text-sm text-gray-500">
                        Noch keine Zahlen getestet.
                    </div>
                @endif
            </div>
        </section>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Gespeicherte Zahlenchecks</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($savedChecks as $check)
                <div class="grid gap-4 px-5 py-4 lg:grid-cols-[1fr_140px_120px_auto] lg:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-gray-900">{{ $check->label ?: $check->gameLabel() }}</p>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $check->gameLabel() }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($check->main_numbers ?? [] as $number)
                                <x-ui.lottery.number-ball :number="$number" :game="$check->game" size="xs" />
                            @endforeach
                            @foreach ($check->bonus_numbers ?? [] as $number)
                                <x-ui.lottery.number-ball :number="$number" :game="$check->game" size="xs" bonus />
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Score</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $check->score }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Bewertung</p>
                        <p class="mt-1 text-sm font-semibold text-blue-700">{{ $check->rating }}</p>
                    </div>

                    <button
                        type="button"
                        wire:click="deleteCheck({{ $check->id }})"
                        wire:confirm="Zahlencheck wirklich loeschen?"
                        class="justify-self-start rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 lg:justify-self-end"
                    >
                        Loeschen
                    </button>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-gray-500">
                    Noch keine Zahlenchecks gespeichert.
                </div>
            @endforelse
        </div>
    </section>
</div>
