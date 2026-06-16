@props([
    'target' => null,
    'mode' => 'global',
    'text' => 'Wird aktualisiert...',
    'showText' => true,
])

@if ($mode === 'overlay')
    <div
        wire:loading.delay
        @if($target) wire:target="{{ $target }}" @endif
        wire:loading.class.remove="hidden"
        wire:loading.class="flex"
        class="absolute inset-0 z-20 items-center justify-center rounded-lg bg-white/70 backdrop-blur-[2px] hidden"
    >
        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200">
            <span class="absolute h-12 w-12 animate-spin rounded-full border border-dashed border-slate-300"></span>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 ring-1 ring-slate-200">
                <i class="mdi mdi-numeric text-base text-slate-500"></i>
            </span>
            <span class="absolute left-1/2 top-1 h-5 w-5 -translate-x-1/2 animate-bounce rounded-full bg-blue-600 text-center text-[10px] font-bold leading-5 text-white shadow-sm [animation-delay:-0.25s]">6</span>
            <span class="absolute bottom-2 left-2 h-5 w-5 animate-bounce rounded-full bg-emerald-600 text-center text-[10px] font-bold leading-5 text-white shadow-sm [animation-delay:-0.12s]">9</span>
            <span class="absolute bottom-2 right-2 h-5 w-5 animate-bounce rounded-full bg-amber-400 text-center text-[10px] font-bold leading-5 text-gray-950 shadow-sm">1</span>
        </div>

        @if($showText)
            <span class="sr-only">{{ $text }}</span>
        @endif
    </div>
@else
    <div
        wire:loading.delay.longer
        @if($target) wire:target="{{ $target }}" @endif
        class="pointer-events-none fixed left-0 right-0 top-0 z-[9999]"
    >
        <div class="h-1 w-full overflow-hidden bg-blue-100">
            <div class="h-full w-1/3 animate-pulse bg-blue-600"></div>
        </div>
        <div class="absolute right-4 top-4 inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-lg">
            <i class="mdi mdi-loading animate-spin text-base text-blue-600"></i>
            {{ $text }}
        </div>
    </div>
@endif
