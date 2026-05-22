<x-filament-panels::page>
    @php
        $sentCount    = $this->sentCount;
        $claimedCount = $this->claimedCount;
        $usedCount    = $this->usedCount;
        $claimRate    = $this->claimRate;
        $useRate      = $this->useRate;
        $hasCoupon    = $this->hasCoupon;
        $monthly      = $this->monthlyData;
        $maxSent      = max(collect($monthly)->pluck('sent')->max(), 1);
    @endphp

    {{-- KPI カード ────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 @if($hasCoupon) lg:grid-cols-5 @else lg:grid-cols-1 @endif mb-6">

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center shadow-sm">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($sentCount) }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">配信数</div>
        </div>

        @if($hasCoupon)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center shadow-sm">
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($claimedCount) }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">クーポン取得数</div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center shadow-sm">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($usedCount) }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">クーポン利用数</div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center shadow-sm">
            <div class="text-3xl font-bold text-amber-500 dark:text-amber-400">{{ $claimRate }}<span class="text-base font-normal">%</span></div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">取得率</div>
            <div class="text-xs text-gray-400 dark:text-gray-500">配信 → 取得</div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-center shadow-sm">
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $useRate }}<span class="text-base font-normal">%</span></div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">利用率</div>
            <div class="text-xs text-gray-400 dark:text-gray-500">取得 → 利用</div>
        </div>
        @endif
    </div>

    {{-- ファネル ────────────────────────────────────────────────── --}}
    @if($hasCoupon && $sentCount > 0)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 mb-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-6">ファネル</h3>
        @php
            $barClaimed = $sentCount > 0 ? round($claimedCount / $sentCount * 100) : 0;
            $barUsed    = $sentCount > 0 ? round($usedCount    / $sentCount * 100) : 0;
            $maxH = 120;
        @endphp
        <div class="flex items-end gap-3 h-36">
            <div class="flex flex-col items-center flex-1">
                <div class="w-full rounded-t transition-all" style="height: {{ $maxH }}px; background: #6b7280;"></div>
                <div class="mt-2 text-xs text-center text-gray-500 dark:text-gray-400 leading-tight">配信<br><span class="font-bold text-gray-700 dark:text-gray-200">{{ number_format($sentCount) }}</span></div>
            </div>
            <div class="flex flex-col items-center flex-1">
                <div class="w-full rounded-t transition-all" style="height: {{ max(round($barClaimed / 100 * $maxH), 4) }}px; background: #3b82f6;"></div>
                <div class="mt-2 text-xs text-center text-blue-600 dark:text-blue-400 leading-tight">取得<br><span class="font-bold">{{ number_format($claimedCount) }}</span><br><span class="text-gray-400">({{ $claimRate }}%)</span></div>
            </div>
            <div class="flex flex-col items-center flex-1">
                <div class="w-full rounded-t transition-all" style="height: {{ max(round($barUsed / 100 * $maxH), 4) }}px; background: #22c55e;"></div>
                <div class="mt-2 text-xs text-center text-green-600 dark:text-green-400 leading-tight">利用<br><span class="font-bold">{{ number_format($usedCount) }}</span><br><span class="text-gray-400">({{ $useRate }}%)</span></div>
            </div>
        </div>
    </div>
    @endif

    {{-- 月別トレンド ──────────────────────────────────────────── --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">月別トレンド（過去6ヶ月）</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700">
                        <th class="py-2 text-left text-xs text-gray-500 font-medium">月</th>
                        <th class="py-2 text-right text-xs text-gray-500 font-medium">配信数</th>
                        @if($hasCoupon)
                        <th class="py-2 text-right text-xs text-gray-500 font-medium">取得数</th>
                        <th class="py-2 text-right text-xs text-gray-500 font-medium">取得率</th>
                        @endif
                        <th class="py-2 pl-6 text-left text-xs text-gray-500 font-medium w-1/2">グラフ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach($monthly as $row)
                    <tr>
                        <td class="py-2 text-gray-700 dark:text-gray-300 font-medium">{{ $row['label'] }}</td>
                        <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['sent']) }}</td>
                        @if($hasCoupon)
                        <td class="py-2 text-right text-blue-600 dark:text-blue-400">{{ number_format($row['claimed']) }}</td>
                        <td class="py-2 text-right text-amber-500">{{ $row['rate'] }}%</td>
                        @endif
                        <td class="py-2 pl-6">
                            <div class="flex gap-1 items-center">
                                @if($row['sent'] > 0)
                                <div class="h-3 rounded" style="width: {{ round($row['sent'] / $maxSent * 140) }}px; background:#6b7280; min-width:2px;"></div>
                                @endif
                                @if($hasCoupon && $row['claimed'] > 0)
                                <div class="h-3 rounded" style="width: {{ round($row['claimed'] / $maxSent * 140) }}px; background:#3b82f6; min-width:2px;"></div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($hasCoupon)
        <div class="flex gap-4 mt-4">
            <div class="flex items-center gap-1.5 text-xs text-gray-500"><span class="inline-block w-3 h-3 rounded" style="background:#6b7280;"></span>配信</div>
            <div class="flex items-center gap-1.5 text-xs text-gray-500"><span class="inline-block w-3 h-3 rounded" style="background:#3b82f6;"></span>取得</div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
