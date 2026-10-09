@php
    $rows = collect($rows);
    $colors = $colors ?? ['#3b82f6', '#ec4899', '#f59e0b', '#6b7280'];
    $total = (int) $rows->sum('count');
    $cursor = 0.0;
    $segments = [];

    foreach ($rows->values() as $index => $row) {
        $count = (int) ($row['count'] ?? 0);

        if ($total <= 0 || $count <= 0) {
            continue;
        }

        $start = $cursor;
        $cursor += ($count / $total) * 100;
        $segments[] = ($colors[$index % count($colors)] ?? '#6b7280') . ' ' . round($start, 2) . '% ' . round($cursor, 2) . '%';
    }

    $pieBackground = count($segments) > 0
        ? 'conic-gradient(' . implode(', ', $segments) . ')'
        : 'linear-gradient(135deg, #374151, #1f2937)';
@endphp

<div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-6 items-center" wire:loading.class="opacity-50">
    <div class="flex justify-center">
        <div
            class="h-48 w-48 rounded-full border border-gray-200 dark:border-white/10 shadow-sm"
            style="background: {{ $pieBackground }};"
            role="img"
            aria-label="構成比グラフ"
        ></div>
    </div>

    <div class="space-y-3">
        @forelse($rows as $index => $row)
            <div class="flex items-center justify-between gap-4 text-sm">
                <div class="flex items-center gap-2 min-w-0">
                    <span
                        class="h-3 w-3 shrink-0 rounded-sm"
                        style="background: {{ $colors[$index % count($colors)] ?? '#6b7280' }};"
                    ></span>
                    <span class="font-medium text-gray-950 dark:text-white truncate">{{ $row['label'] }}</span>
                </div>
                <span class="shrink-0 tabular-nums text-gray-600 dark:text-gray-300">
                    {{ number_format($row['count']) }}人 / {{ $row['percent'] }}%
                </span>
            </div>
        @empty
            <div class="py-8 text-center text-sm text-gray-500">データがありません</div>
        @endforelse
    </div>
</div>
