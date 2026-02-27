<x-filament-widgets::widget>
  <x-filament::section :heading="$this->getHeading()">

    {{-- ① 新規会員登録数 --}}
    <div class="mb-4">
      <h4 class="text-sm font-semibold mb-2">新規会員登録数</h4>
      <div class="space-y-2">
        @forelse($registrations as $i => $r)
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="text-sm font-bold opacity-80">{{ $i+1 }}位</div>
              <div class="text-sm font-semibold">{{ $r->store_name }}</div>
            </div>
            <div class="text-sm font-bold">{{ (int)$r->count }}</div>
          </div>
        @empty
          <div class="text-sm opacity-70">データがありません</div>
        @endforelse
      </div>
    </div>

    {{-- ② スタンプ押下数 --}}
    <div>
      <h4 class="text-sm font-semibold mb-2">スタンプ押下数</h4>
      <div class="space-y-2">
        @forelse($stamps as $i => $r)
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="text-sm font-bold opacity-80">{{ $i+1 }}位</div>
              <div class="text-sm font-semibold">{{ $r->store_name }}</div>
            </div>
            <div class="text-sm font-bold">{{ (int)$r->count }}</div>
          </div>
        @empty
          <div class="text-sm opacity-70">データがありません</div>
        @endforelse
      </div>
    </div>

  </x-filament::section>
</x-filament-widgets::widget>
