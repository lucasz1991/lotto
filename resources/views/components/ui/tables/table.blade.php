@props([
    'columns' => [],
    'items' => [],
    'empty' => 'Keine Eintraege gefunden.',
    'class' => '',
    'sortBy' => null,
    'sortDir' => 'asc',
    'sortMethod' => null,
    'rowView' => null,
    'actionsView' => null,
])

@php
    use Illuminate\Support\Str;

    $columns = collect($columns)->map(function ($column) {
        if (is_string($column)) {
            return [
                'label' => $column,
                'key' => Str::slug($column, '_'),
                'width' => '1fr',
                'sortable' => false,
                'hideOn' => 'none',
            ];
        }

        return [
            'label' => $column['label'] ?? '',
            'key' => $column['key'] ?? ($column['label'] ?? ''),
            'width' => $column['width'] ?? '1fr',
            'sortable' => (bool) ($column['sortable'] ?? false),
            'hideOn' => $column['hideOn'] ?? 'none',
        ];
    });

    $hideClass = function (string $hideOn): string {
        return match ($hideOn) {
            'sm' => 'hidden sm:block',
            'md' => 'hidden md:block',
            'lg' => 'hidden lg:block',
            'xl' => 'hidden xl:block',
            default => '',
        };
    };

    $isVisibleAt = function (string $hideOn, string $breakpoint): bool {
        $order = ['sm' => 0, 'md' => 1, 'lg' => 2, 'xl' => 3];

        return $hideOn === 'none' || $order[$breakpoint] >= $order[$hideOn];
    };

    $buildTemplate = function (string $breakpoint) use ($columns, $isVisibleAt, $actionsView): string {
        $tracks = [];

        foreach ($columns as $column) {
            if ($isVisibleAt($column['hideOn'], $breakpoint)) {
                $tracks[] = $column['width'] ?: '1fr';
            }
        }

        if ($tracks === []) {
            $tracks[] = '1fr';
        }

        if ($actionsView) {
            $tracks[] = 'min-content';
        }

        return implode(' ', $tracks);
    };

    $templateMd = $buildTemplate('md');
    $templateLg = $buildTemplate('lg');
    $templateXl = $buildTemplate('xl');

    $sortDirectionFor = function ($columnKey, $sortBy, $sortDir): string {
        return ($sortBy == $columnKey && $sortDir === 'asc') ? 'desc' : 'asc';
    };

    $sortIconFor = function ($columnKey, $sortBy, $sortDir): string {
        if ($sortBy !== $columnKey) {
            return 'mdi mdi-swap-vertical';
        }

        return $sortDir === 'asc' ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down';
    };
@endphp

<div
    {{ $attributes->merge(['class' => 'relative mt-4 w-full '.$class]) }}
    x-data="{
        colsMd: '{{ $templateMd }}',
        colsLg: '{{ $templateLg }}',
        colsXl: '{{ $templateXl }}',
        headerStyle: '',
        rowStyle: '',
        updateTemplates() {
            const width = window.innerWidth || document.documentElement.clientWidth;

            if (width >= 1280) {
                this.headerStyle = `grid-template-columns: ${this.colsXl};`;
                this.rowStyle = `grid-template-columns: ${this.colsXl};`;
            } else if (width >= 1024) {
                this.headerStyle = `grid-template-columns: ${this.colsLg};`;
                this.rowStyle = `grid-template-columns: ${this.colsLg};`;
            } else if (width >= 768) {
                this.headerStyle = `grid-template-columns: ${this.colsMd};`;
                this.rowStyle = `grid-template-columns: ${this.colsMd};`;
            } else {
                this.headerStyle = '';
                this.rowStyle = '';
            }
        }
    }"
    x-init="
        updateTemplates();
        window.addEventListener('resize', () => updateTemplates(), { passive: true });
    "
>
    <div
        class="hidden border-b bg-gray-50 p-2 pr-8 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 md:grid"
        :style="headerStyle"
    >
        @foreach($columns as $column)
            @php($hidden = $hideClass($column['hideOn']))

            @if($column['sortable'])
                <button
                    type="button"
                    class="flex items-center gap-1 px-2 py-2 text-left hover:text-gray-950 {{ $hidden }}"
                    @if($sortMethod)
                        wire:click="{{ $sortMethod }}('{{ $column['key'] }}')"
                    @else
                        @click="$dispatch('table-sort', {
                            key: '{{ $column['key'] }}',
                            dir: '{{ $sortDirectionFor($column['key'], $sortBy, $sortDir) }}'
                        })"
                    @endif
                >
                    <span>{{ $column['label'] }}</span>
                    <i class="{{ $sortIconFor($column['key'], $sortBy, $sortDir) }} text-base opacity-70"></i>
                </button>
            @else
                <div class="px-2 py-2 {{ $hidden }}">{{ $column['label'] }}</div>
            @endif
        @endforeach

        @if($actionsView)
            <div class="px-2 py-2 text-right"></div>
        @endif
    </div>

    @forelse($items as $item)
        <div class="relative border-b py-2 text-sm hover:bg-blue-50">
            <div class="grid items-center pr-8" :style="rowStyle">
                @if($rowView)
                    @include($rowView, ['item' => $item, 'columnsMeta' => $columns, 'hideClass' => $hideClass])
                @else
                    @foreach($columns as $column)
                        <div class="px-2 py-2 {{ $hideClass($column['hideOn']) }}">-</div>
                    @endforeach
                @endif
            </div>

            @if($actionsView)
                <div class="absolute right-1 top-1 flex items-center md:bottom-0 md:top-0">
                    @include($actionsView, ['item' => $item])
                </div>
            @endif
        </div>
    @empty
        <div class="p-4 text-sm text-gray-500">{{ $empty }}</div>
    @endforelse
</div>
