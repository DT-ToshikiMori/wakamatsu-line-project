<x-filament-widgets::widget>
  <x-filament::section>
    <div class="space-y-2">
      @forelse($items as $i => $r)
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2 min-w-0">
            <div class="text-sm font-bold opacity-80 whitespace-nowrap">{{ $i+1 }}位</div>
            <div class="text-sm font-semibold truncate">
              {{ $r->title }}
              <span class="text-xs opacity-60">（{{ $r->type }}）</span>
            </div>
          </div>

          <div class="flex items-center gap-3 whitespace-nowrap">
            <div class="text-xs opacity-70">発行 {{ $r->issued }}</div>
            <div class="text-xs opacity-70">使用 {{ $r->used }}</div>
            <div class="text-sm font-bold">{{ $r->rate }}%</div>
          </div>
        </div>
      @empty
        <div class="text-sm opacity-70">データがありません</div>
      @endforelse
    </div>
  </x-filament::section>
</x-filament-widgets::widget>