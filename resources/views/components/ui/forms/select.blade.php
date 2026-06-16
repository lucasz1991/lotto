@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'help' => null,
    'errorFor' => null,
    'disabled' => false,
    'compactMobile' => false,
    'model' => null,
    'value' => null,
    'options' => [],
])

@php
    $inputId = $id ?? $name ?? 'select_'.uniqid();
    $hasIcon = isset($icon);
    $errorKey = $errorFor ?? $name;
    $selectedOption = collect($options)->first(fn (array $option): bool => (string) ($option['value'] ?? '') === (string) $value);
    $selectedOption = $selectedOption ?: ($options[0] ?? null);
    $title = $attributes->get('title');
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif

    <div class="{{ $label ? 'mt-1' : '' }} relative">
        @if($options !== [])
            <x-ui.dropdown.anchor-dropdown
                align="right"
                width="auto"
                :offset="8"
                :match-trigger-width="true"
                dropdownClasses="mx-0"
                contentClasses="max-h-72 overflow-y-auto py-1 bg-white"
            >
                <x-slot name="trigger">
                    <button
                        id="{{ $inputId }}"
                        type="button"
                        @if($disabled) disabled @endif
                        title="{{ $title }}"
                        class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            @if($selectedOption && ($selectedOption['icon'] ?? null))
                                <i class="{{ $selectedOption['icon'] }} shrink-0 text-lg text-gray-500"></i>
                            @elseif($hasIcon)
                                <span class="shrink-0 text-gray-500">{{ $icon }}</span>
                            @endif

                            <span class="{{ $compactMobile ? 'hidden sm:block' : 'block' }} truncate">
                                {{ $selectedOption['label'] ?? $title ?? '-' }}
                            </span>
                        </span>
                        <i class="mdi mdi-chevron-down shrink-0 text-lg text-gray-400"></i>
                    </button>
                </x-slot>

                <x-slot name="content">
                    @foreach($options as $option)
                        @php
                            $isSelected = (string) ($option['value'] ?? '') === (string) $value;
                        @endphp

                        <button
                            type="button"
                            x-on:click="$wire.set(@js($model), @js($option['value'])); open = false"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm {{ $isSelected ? 'bg-blue-50 font-semibold text-blue-800' : 'text-gray-700 hover:bg-gray-50' }}"
                        >
                            @if($option['icon'] ?? null)
                                <i class="{{ $option['icon'] }} w-5 shrink-0 text-lg {{ $isSelected ? 'text-blue-700' : 'text-gray-500' }}"></i>
                            @endif
                            <span class="min-w-0 flex-1 truncate">{{ $option['label'] ?? $option['value'] }}</span>
                            @if($isSelected)
                                <i class="mdi mdi-check text-lg text-blue-700"></i>
                            @endif
                        </button>
                    @endforeach
                </x-slot>
            </x-ui.dropdown.anchor-dropdown>
        @else
            @if($hasIcon)
                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-500">
                    {{ $icon }}
                </div>
            @endif

            <select
                id="{{ $inputId }}"
                @if($name) name="{{ $name }}" @endif
                @if($disabled) disabled @endif
                {!! $attributes->merge([
                    'class' => 'block h-10 w-full rounded-md border-gray-300 bg-white '.($hasIcon ? 'pl-9' : 'pl-3').' pr-8 text-sm font-medium shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500 '.($compactMobile ? 'text-transparent sm:text-gray-800' : 'text-gray-800'),
                ]) !!}
            >
                {{ $slot }}
            </select>
        @endif
    </div>

    @if($help)
        <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif

    @if($errorKey)
        @error($errorKey)
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
