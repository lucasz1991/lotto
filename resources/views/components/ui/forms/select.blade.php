@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'help' => null,
    'errorFor' => null,
    'disabled' => false,
    'compactMobile' => false,
])

@php
    $inputId = $id ?? $name ?? 'select_'.uniqid();
    $hasIcon = isset($icon);
    $errorKey = $errorFor ?? $name;
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif

    <div class="{{ $label ? 'mt-1' : '' }} relative">
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
