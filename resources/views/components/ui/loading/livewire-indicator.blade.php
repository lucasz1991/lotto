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
        class="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-white/70 backdrop-blur-[2px] hidden"
    >
        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200">
            <span class="absolute h-12 w-12 animate-spin rounded-full border-2 border-blue-100 border-t-blue-600"></span>
            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
            <span class="absolute -right-1 top-3 h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
            <span class="absolute bottom-3 left-0 h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400"></span>
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
