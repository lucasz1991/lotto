@props([
    'target' => null,
    'mode' => 'global',
    'text' => 'Wird aktualisiert...',
    'showText' => true,
])

@php
    $drum = <<<'HTML'
        <div class="relative flex h-20 w-20 items-center justify-center">
            <div class="absolute bottom-1 left-1/2 h-2 w-10 -translate-x-1/2 rounded-full bg-slate-200"></div>

            <div class="relative h-14 w-14 animate-spin rounded-full [animation-duration:1.15s]">
                <span class="absolute left-1/2 top-0 flex h-5 w-5 -translate-x-1/2 -translate-y-1 items-center justify-center rounded-full bg-white text-[10px] font-bold text-slate-700 shadow-sm ring-1 ring-slate-300">6</span>
                <span class="absolute right-0 top-1/2 flex h-5 w-5 -translate-y-1/2 translate-x-1 items-center justify-center rounded-full bg-white text-[10px] font-bold text-slate-700 shadow-sm ring-1 ring-slate-300">21</span>
                <span class="absolute bottom-0 left-1/2 flex h-5 w-5 -translate-x-1/2 translate-y-1 items-center justify-center rounded-full bg-white text-[10px] font-bold text-slate-700 shadow-sm ring-1 ring-slate-300">9</span>
                <span class="absolute left-0 top-1/2 flex h-5 w-5 -translate-x-1 -translate-y-1/2 items-center justify-center rounded-full bg-white text-[10px] font-bold text-slate-700 shadow-sm ring-1 ring-slate-300">34</span>
            </div>

            <div class="pointer-events-none absolute inset-2 rounded-full border border-white/80"></div>
            <div class="pointer-events-none absolute right-5 top-5 h-4 w-2 rounded-full bg-white/70 blur-[1px]"></div>
        </div>
    HTML;
@endphp

@if ($mode === 'static')
    {!! $drum !!}
@elseif ($mode === 'overlay')
    <div
        wire:loading.delay
        @if($target) wire:target="{{ $target }}" @endif
        wire:loading.class.remove="hidden"
        wire:loading.class="flex"
        class="absolute inset-0 z-20 items-center justify-center rounded-lg bg-white/70 backdrop-blur-[2px] hidden"
    >
        {!! $drum !!}

        @if($showText)
            <span class="sr-only">{{ $text }}</span>
        @endif
    </div>
@else
    <div
        wire:loading.delay.longer
        @if($target) wire:target="{{ $target }}" @endif
        wire:loading.class.remove="hidden"
        wire:loading.class="flex"
        class="pointer-events-none fixed inset-0 z-[9999] hidden items-center justify-center bg-white/50 backdrop-blur-[1px]"
    >
        {!! $drum !!}

        @if($showText)
            <span class="sr-only">{{ $text }}</span>
        @endif
    </div>
@endif
