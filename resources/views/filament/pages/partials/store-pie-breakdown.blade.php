@php
    $rows = collect($rows);
    $colors = $colors ?? ['#3b82f6', '#ec4899', '#f59e0b', '#6b7280'];
    $total = (int) $rows->sum('count');
    $radius = 42;
    $circumference = 2 * pi() * $radius;
    $offset = 25;
    $segments = [];

    foreach ($rows->values() as $index => $row) {
        $count = (int) ($row['count'] ?? 0);

        if ($total <= 0 || $count <= 0) {
            continue;
        }

        $length = ($count / $total) * $circumference;
        $segments[] = [
            'color' => $colors[$index % count($colors)] ?? '#6b7280',
            'dasharray' => round($length, 4) . ' ' . round($circumference - $length, 4),
            'dashoffset' => round(-$offset, 4),
        ];
        $offset += $length;
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-6 items-center" wire:loading.class="opacity-50">
    <div class="flex justify-center">
        <svg width="192" height="192" viewBox="0 0 100 100" role="img" aria-label="構成比グラフ">
            <circle cx="50" cy="50" r="{{ $radius }}" fill="none" stroke="#374151" stroke-width="16" />

            @foreach($segments as $segment)
                <circle
                    cx="50"
                    cy="50"
                    r="{{ $radius }}"
                    fill="none"
                    stroke="{{ $segment['color'] }}"
                    stroke-width="16"
                    stroke-dasharray="{{ $segment['dasharray'] }}"
                    stroke-dashoffset="{{ $segment['dashoffset'] }}"
                    stroke-linecap="butt"
                    transform="rotate(-90 50 50)"
                />
            @endforeach
        </svg>
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
