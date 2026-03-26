<x-filament-panels::page>
    {{-- 配信概要 --}}
    <x-filament::section heading="配信概要">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <div class="text-xs text-gray-500">配信名</div>
                <div class="text-sm font-semibold">{{ $broadcast->name }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">ステータス</div>
                <div class="text-sm font-semibold">
                    @switch($broadcast->status)
                        @case('draft') 下書き @break
                        @case('scheduled') 予約済み @break
                        @case('sent') 配信済み @break
                        @default {{ $broadcast->status }}
                    @endswitch
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500">配信数</div>
                <div class="text-sm font-semibold">{{ number_format($broadcast->sent_count ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">配信日時</div>
                <div class="text-sm font-semibold">{{ $broadcast->sent_at?->format('Y-m-d H:i') ?? '-' }}</div>
            </div>
        </div>
    </x-filament::section>

    {{-- バブル別分析 --}}
    <x-filament::section heading="バブル別分析">
        @if($bubbleStats->isEmpty())
            <div class="text-sm text-gray-500">バブルデータがありません</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2 pr-4">#</th>
                            <th class="py-2 pr-4">種別</th>
                            <th class="py-2 pr-4">内容</th>
                            <th class="py-2 pr-4 text-right">取得数</th>
                            <th class="py-2 pr-4 text-right">取得率</th>
                            <th class="py-2 pr-4 text-right">使用数</th>
                            <th class="py-2 text-right">使用率</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bubbleStats as $stat)
                            <tr class="border-b">
                                <td class="py-2 pr-4">{{ $stat->position }}</td>
                                <td class="py-2 pr-4">
                                    @if($stat->type === 'coupon')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">クーポン</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">テキスト</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4">{{ $stat->label }}</td>
                                @if($stat->type === 'coupon')
                                    <td class="py-2 pr-4 text-right font-semibold">{{ number_format($stat->claimed) }}</td>
                                    <td class="py-2 pr-4 text-right font-semibold text-blue-600">{{ $stat->claimRate }}%</td>
                                    <td class="py-2 pr-4 text-right font-semibold">{{ number_format($stat->used) }}</td>
                                    <td class="py-2 text-right font-semibold text-green-600">{{ $stat->usageRate }}%</td>
                                @else
                                    <td class="py-2 pr-4 text-right text-gray-400" colspan="4">-</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
