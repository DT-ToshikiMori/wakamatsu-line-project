<x-filament-widgets::widget>
  <x-filament::section :heading="$this->getHeading()">
    <div class="space-y-2">
      @forelse($rows as $i => $r)
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="text-sm font-bold opacity-80">
              {{ $i+1 }}位
            </div>
            <div class="text-sm font-semibold">
              {{ $r->store_name }}
            </div>
          </div>
          <div class="text-sm font-bold">
            {{ (int)$r->visits }}
          </div>
        </div>
      @empty
        <div class="text-sm opacity-70">データがありません</div>
      @endforelse
    </div>
  </x-filament::section>
</x-filament-widgets::widget>