<div class="space-y-3" wire:loading.class="opacity-50">
    @php
        $max = collect($rows)->max('count') ?: 0;
    @endphp

    @forelse($rows as $row)
        @php
            $width = $max > 0 ? round(($row['count'] / $max) * 100, 1) : 0;
        @endphp
        <div>
            <div class="flex items-center justify-between gap-4 text-sm">
                <span class="font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</span>
                <span class="tabular-nums text-gray-600 dark:text-gray-300">{{ number_format($row['count']) }}人 / {{ $row['percent'] }}%</span>
            </div>
            <div class="mt-1 h-2 rounded bg-gray-100 dark:bg-white/10 overflow-hidden">
                <div class="h-full rounded bg-primary-500" style="width: {{ $width }}%"></div>
            </div>
        </div>
    @empty
        <div class="py-8 text-center text-sm text-gray-500">データがありません</div>
    @endforelse
</div>
