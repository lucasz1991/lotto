@props([
  // 1) Liste: [ ['id'=>'basic','label'=>'…'], ... ]
  // 2) Map :  ['basic'=>['label'=>'…'], ... ]
  'tabs' => [],

  'class' => 'flex items-center gap-2 overflow-x-auto',

  'buttonClass' => 'inline-flex min-h-[40px] items-center justify-center rounded-md px-4 py-2 text-sm font-semibold transition-colors border border-transparent',

  // Breakpoint fuer Collapse-Variante
  'collapseAt' => 'md',
])

@php
    use Illuminate\Support\Str;

    $items = [];
    if (array_is_list($tabs)) {
        foreach ($tabs as $t) {
            $id    = $t['id'] ?? Str::slug($t['label'] ?? 'tab', '_');
            $label = $t['label'] ?? Str::title($id);
            $items[] = compact('id','label');
        }
    } else {
        foreach ($tabs as $key => $t) {
            $items[] = is_array($t)
                ? ['id'=>$key, 'label'=>$t['label'] ?? Str::title($key)]
                : ['id'=>$key, 'label'=>(string)$t];
        }
    }
@endphp

<div
  x-data="{
    items: @js($items),
    get active(){
      return this.items.find(i => i.id === this.selectedTab) ?? this.items[0] ?? null;
    },
    get others(){
      return this.items.filter(i => i.id !== (this.active?.id ?? ''));
    },
    collapsed:false, mq:null,
    setupMQ(bp){
      if(!bp) { this.collapsed=false; return; }
      const map={sm:640, md:768, lg:1024, xl:1280, '2xl':1536};
      const px = map[bp]; if(!px) return;
      this.mq = window.matchMedia(`(min-width:${px}px)`);
      const update = () => { this.collapsed = !this.mq.matches; };
      this.mq.addEventListener?.('change', update); update();
    }
  }"
  x-init="setupMQ(@js($collapseAt))"
  x-on:keydown.right.prevent="$focus.wrap().next()"
  x-on:keydown.left.prevent="$focus.wrap().previous()"
  role="tablist"
  aria-label="tab options"
  {{ $attributes->merge(['class' => $class]) }}
>
  {{-- Standard-Darstellung --}}
  <template x-if="!collapsed">
    <template x-for="t in items" :key="t.id">
      <button
        type="button"
        role="tab"
        :aria-selected="selectedTab === t.id"
        :tabindex="selectedTab === t.id ? '0' : '-1'"
        x-on:click="selectedTab = t.id"
        class="{{ $buttonClass }}"
        x-bind:class="selectedTab === t.id
          ? 'border-blue-200 bg-white text-blue-700 shadow-sm'
          : 'bg-transparent text-gray-600 hover:bg-white hover:text-gray-900'"
        :aria-controls="`tabpanel-${t.id}`"
        :title="t.label"
      >
        <span x-text="t.label"></span>
      </button>
    </template>
  </template>

  {{-- Collapse-Variante --}}
  <template x-if="collapsed">
    <div class="flex w-full items-center gap-2" x-data="{ open:false }">
      <button
        type="button"
        role="tab"
        aria-selected="true"
        tabindex="0"
        class="{{ $buttonClass }} flex-1 border-blue-200 bg-white text-blue-700 shadow-sm"
        :title="active?.label ?? ''"
      >
        <span class="truncate" x-text="active?.label ?? ''"></span>
      </button>

      <button
        x-ref="moreBtn"
        type="button"
        @click="open=!open"
        @keydown.escape.window="open=false"
        class="{{ $buttonClass }} bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900"
        :aria-expanded="open.toString()"
        aria-haspopup="menu"
        title="Weitere Tabs"
      >
        <span class="whitespace-nowrap">Mehr</span>
      </button>

      <template x-teleport="body">
        <div
          x-cloak
          x-show="open"
          x-transition.opacity.duration.120ms
          x-on:click.away="open=false"
          x-anchor.bottom-start.offset.6="$refs.moreBtn"
          class="z-50 rounded-md border border-gray-200 bg-white shadow"
          role="menu"
        >
          <ul class="max-h-[60vh] overflow-auto py-1">
            <template x-for="t in others" :key="t.id">
              <li>
                <button
                  type="button"
                  class="inline-flex w-full items-center justify-between gap-4 px-3 py-2 text-sm hover:bg-gray-50"
                  role="menuitem"
                  @click="open=false; $nextTick(() => selectedTab = t.id)"
                >
                  <span class="truncate" x-text="t.label"></span>
                </button>
              </li>
            </template>
          </ul>
        </div>
      </template>
    </div>
  </template>
</div>
