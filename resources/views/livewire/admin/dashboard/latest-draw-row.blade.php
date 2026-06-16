@php
    /** @var \App\Models\LotteryDraw $item */
    $bonusNumbers = $item->bonus_numbers ?? [];
    $bonus = $bonusNumbers['euro_numbers'] ?? [$bonusNumbers['superzahl'] ?? null];
    $bonus = array_values(array_filter((array) $bonus, fn ($value) => $value !== null && $value !== ''));
@endphp

<div class="px-2 py-2 font-semibold text-gray-900 {{ $hideClass($columnsMeta[0]['hideOn']) }}">
    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 md:hidden">Spielart</span>
    {{ $item->gameLabel() }}
</div>

<div class="px-2 py-2 text-gray-600 {{ $hideClass($columnsMeta[1]['hideOn']) }}">
    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 md:hidden">Ziehung</span>
    {{ $item->draw_date?->format('d.m.Y') }}
</div>

<div class="px-2 py-2 {{ $hideClass($columnsMeta[2]['hideOn']) }}">
    <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500 md:hidden">Zahlen</span>
    <div class="flex min-w-[220px] flex-wrap gap-1.5">
        @foreach ($item->numbers ?? [] as $number)
            <x-ui.lottery.number-ball :number="$number" :game="$item->game" size="xs" />
        @endforeach

        @foreach ($bonus as $number)
            <x-ui.lottery.number-ball :number="$number" :game="$item->game" size="xs" bonus />
        @endforeach
    </div>
</div>

<div class="px-2 py-2 text-gray-600 {{ $hideClass($columnsMeta[3]['hideOn']) }}">
    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 md:hidden">Aktualisiert</span>
    {{ $item->updated_at?->format('d.m.Y H:i') }}
</div>
