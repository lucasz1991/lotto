@props([
    'number',
    'game' => null,
    'bonus' => false,
    'size' => 'md',
    'as' => 'span',
    'label' => null,
])

@php
    $isEuroJackpot = $game === \App\Models\LotteryDraw::GAME_EUROJACKPOT;
    $sizeClasses = [
        'xs' => 'h-7 min-w-[1.75rem] px-2 text-xs',
        'sm' => 'h-8 min-w-[2rem] px-2 text-sm',
        'md' => 'h-10 min-w-[2.5rem] px-3 text-sm',
        'lg' => 'h-11 min-w-[2.75rem] px-3 text-base',
    ][$size] ?? 'h-10 min-w-[2.5rem] px-3 text-sm';
    $toneClasses = $bonus
        ? 'bg-amber-400 text-gray-950 ring-amber-200'
        : ($isEuroJackpot ? 'bg-emerald-600 text-white ring-emerald-200' : 'bg-blue-600 text-white ring-blue-200');
    $baseClasses = 'inline-flex aspect-square shrink-0 items-center justify-center rounded-full font-bold shadow-sm ring-1 ring-inset '.$sizeClasses.' '.$toneClasses;
@endphp

@if ($as === 'button')
    <button
        type="button"
        aria-label="{{ $label ?? 'Details zu Zahl '.$number }}"
        {{ $attributes->merge([
            'class' => $baseClasses.' transition hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-offset-2',
        ]) }}
    >
        {{ $number }}
    </button>
@else
    <span {{ $attributes->merge(['class' => $baseClasses]) }}>
        {{ $number }}
    </span>
@endif
