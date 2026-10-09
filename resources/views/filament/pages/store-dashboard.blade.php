<x-filament-panels::page>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="fi-fo-field-wrp-label block text-sm font-medium mb-1">店舗</label>
                <select wire:model.live="storeId"
                        class="fi-input block w-full rounded-lg shadow-sm border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-950 dark:text-white text-sm py-2 px-3">
                    <option value="">全店舗</option>
                    @foreach($this->getStoreOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="fi-fo-field-wrp-label block text-sm font-medium mb-1">開始日</label>
                <input type="date" wire:model.live="startDate"
                       class="fi-input block w-full rounded-lg shadow-sm border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-950 dark:text-white text-sm py-2 px-3">
            </div>

            <div>
                <label class="fi-fo-field-wrp-label block text-sm font-medium mb-1">終了日</label>
                <input type="date" wire:model.live="endDate"
                       class="fi-input block w-full rounded-lg shadow-sm border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-950 dark:text-white text-sm py-2 px-3">
            </div>
        </div>
    </x-filament::section>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4" wire:loading.class="opacity-50">
        @php
            $cards = [
                ['label' => '来店回数', 'value' => number_format($summary['visit_count'] ?? 0), 'unit' => '回'],
                ['label' => '来店ユーザー数', 'value' => number_format($summary['visitor_count'] ?? 0), 'unit' => '人'],
                ['label' => '新規来店ユーザー', 'value' => number_format($summary['new_visitor_count'] ?? 0), 'unit' => '人'],
                ['label' => '期間内リピート', 'value' => number_format($summary['repeat_visitor_count'] ?? 0), 'unit' => '人'],
                ['label' => '平均来店回数', 'value' => number_format($summary['avg_visits_per_user'] ?? 0, 2), 'unit' => '回/人'],
                ['label' => 'リピート率', 'value' => number_format($summary['repeat_rate'] ?? 0, 1), 'unit' => '%'],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 px-4 py-4 shadow-sm">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $card['value'] }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $card['unit'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">店舗別サマリー</x-slot>

        <div class="overflow-x-auto -mx-6 px-6" wire:loading.class="opacity-50">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2 pr-4 font-medium">店舗</th>
                        <th class="py-2 px-4 font-medium text-right">来店回数</th>
                        <th class="py-2 px-4 font-medium text-right">来店ユーザー数</th>
                        <th class="py-2 px-4 font-medium text-right">新規来店ユーザー</th>
                        <th class="py-2 pl-4 font-medium text-right">期間内リピート</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse($storeRows as $row)
                        <tr>
                            <td class="py-3 pr-4 font-medium text-gray-950 dark:text-white whitespace-nowrap">{{ $row['store_name'] }}</td>
                            <td class="py-3 px-4 text-right tabular-nums">{{ number_format($row['visit_count']) }}</td>
                            <td class="py-3 px-4 text-right tabular-nums">{{ number_format($row['visitor_count']) }}</td>
                            <td class="py-3 px-4 text-right tabular-nums">{{ number_format($row['new_visitor_count']) }}</td>
                            <td class="py-3 pl-4 text-right tabular-nums">{{ number_format($row['repeat_visitor_count']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">データがありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <x-filament::section>
            <x-slot name="heading">性別</x-slot>
            @include('filament.pages.partials.store-breakdown', ['rows' => $genderRows])
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">年代</x-slot>
            @include('filament.pages.partials.store-breakdown', ['rows' => $ageRows])
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">初回登録時の来店状況</x-slot>
            @include('filament.pages.partials.store-breakdown', ['rows' => $frequencyRows])
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">郵便番号 上位20件</x-slot>
            <div class="space-y-3" wire:loading.class="opacity-50">
                @forelse($postalRows as $row)
                    <div>
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span class="font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</span>
                            <span class="tabular-nums text-gray-600 dark:text-gray-300">{{ number_format($row['count']) }}人 / {{ $row['percent'] }}%</span>
                        </div>
                        <div class="mt-1 h-2 rounded bg-gray-100 dark:bg-white/10 overflow-hidden">
                            <div class="h-full rounded bg-primary-500" style="width: {{ min(100, $row['percent']) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-gray-500">郵便番号データがありません</div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
