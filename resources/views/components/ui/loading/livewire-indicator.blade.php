@props([
    'target' => null,
    'mode' => 'global',
    'text' => 'Wird aktualisiert...',
])

@if ($mode === 'overlay')
    <div
        wire:loading.delay
        @if($target) wire:target="{{ $target }}" @endif
        class="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-white/75 backdrop-blur-sm"
    >
        <div class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm">
            <i class="mdi mdi-loading animate-spin text-lg text-blue-600"></i>
            {{ $text }}
        </div>
    </div>
@else
    <div
        x-data="{
            visible: false,
            timeout: null,
            start() {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => this.visible = true, 350);
            },
            stop() {
                clearTimeout(this.timeout);
                this.visible = false;
            },
            register() {
                if (! window.Livewire || ! Livewire.hook) {
                    return;
                }

                Livewire.hook('request', ({ respond, fail }) => {
                    this.start();

                    if (typeof respond === 'function') {
                        respond(() => this.stop());
                    }

                    if (typeof fail === 'function') {
                        fail(() => this.stop());
                    }
                });
            }
        }"
        x-init="window.Livewire ? register() : document.addEventListener('livewire:init', () => register(), { once: true })"
        x-show="visible"
        x-transition.opacity.duration.150ms
        style="display: none;"
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
