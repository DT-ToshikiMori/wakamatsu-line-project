<x-filament-panels::page>
    {{-- リッチメニュー概要 --}}
    <x-filament::section heading="リッチメニュー概要">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <div class="text-xs text-gray-500">管理名</div>
                <div class="text-sm font-semibold">{{ $richMenu->name }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">ステータス</div>
                <div class="text-sm font-semibold">
                    @switch($richMenu->status)
                        @case('draft') 下書き @break
                        @case('synced') 同期済み @break
                        @case('active') 有効 @break
                        @default {{ $richMenu->status }}
                    @endswitch
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500">サイズ</div>
                <div class="text-sm font-semibold">{{ $richMenu->size_type === 'full' ? 'フル' : 'ハーフ' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">最終同期</div>
                <div class="text-sm font-semibold">{{ $richMenu->synced_at?->format('Y-m-d H:i') ?? '-' }}</div>
            </div>
        </div>
    </x-filament::section>

    {{-- エリア別クリック数 --}}
    <x-filament::section heading="エリア別クリック数">
        @if($areaStats->isEmpty())
            <div class="text-sm text-gray-500">エリアが設定されていません</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2 pr-4">エリア</th>
                            <th class="py-2 pr-4">アクション</th>
                            <th class="py-2 pr-4 text-right">総数</th>
                            <th class="py-2 pr-4 text-right">ユニーク</th>
                            <th class="py-2 pr-4 text-right">本日</th>
                            <th class="py-2 text-right">7日間</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($areaStats as $stat)
                            <tr class="border-b">
                                <td class="py-2 pr-4 font-semibold">{{ $stat->label }}</td>
                                <td class="py-2 pr-4">
                                    @switch($stat->actionType)
                                        @case('postback')
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">Postback</span>
                                            @break
                                        @case('uri')
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">URI</span>
                                            @break
                                        @case('message')
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Message</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="py-2 pr-4 text-right font-semibold">{{ number_format($stat->totalClicks) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($stat->uniqueUsers) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($stat->todayClicks) }}</td>
                                <td class="py-2 text-right">{{ number_format($stat->weekClicks) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- クリックトレンドチャート --}}
    <x-filament::section heading="クリックトレンド（14日間）">
        <div style="height: 300px;">
            <canvas id="clickTrendChart"></canvas>
        </div>
    </x-filament::section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('clickTrendChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartDates),
                    datasets: @json($chartDatasets),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true },
                    },
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                },
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
