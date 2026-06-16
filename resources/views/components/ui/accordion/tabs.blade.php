@props([
    'tabs' => [],
    'default' => null,
    'persistKey' => null,
    'class' => '',
])

@php
    use Illuminate\Support\Str;

    $items = collect($tabs)->map(function ($tab, string $key): array {
        return [
            'id' => $key,
            'label' => is_array($tab) ? ($tab['label'] ?? Str::title($key)) : (string) $tab,
        ];
    })->values()->all();

    $initial = $default ?? ($items[0]['id'] ?? 'section');
    $routeName = optional(request()->route())->getName() ?? request()->path();
    $key = $persistKey ?: 'accordion:'.$routeName.':'.implode(',', array_column($items, 'id'));
@endphp

<div
    x-data="{ openSection: $persist(@js($initial)).as(@js($key)) }"
    {{ $attributes->merge(['class' => 'space-y-3 '.$class]) }}
>
    @foreach ($items as $item)
        @php($slotName = $item['id'])

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left text-sm font-semibold text-gray-900 transition hover:bg-gray-50"
                x-on:click="openSection = openSection === @js($item['id']) ? null : @js($item['id'])"
                :aria-expanded="(openSection === @js($item['id'])).toString()"
            >
                <span>{{ $item['label'] }}</span>
                <i
                    class="mdi mdi-chevron-down text-lg text-gray-400 transition-transform"
                    :class="openSection === @js($item['id']) ? 'rotate-180' : ''"
                ></i>
            </button>

            <div
                x-show="openSection === @js($item['id'])"
                x-collapse
                x-cloak
                class="border-t border-gray-100 px-5 py-5"
            >
                {{ ${$slotName} ?? '' }}
            </div>
        </section>
    @endforeach
</div>
