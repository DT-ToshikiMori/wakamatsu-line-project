<x-filament-panels::page>

    {{-- フィルタ --}}
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">店舗</label>
                <select wire:model.live="storeId"
                        class="w-full rounded-lg border-gray-600 bg-gray-700 text-white text-sm py-2 px-3">
                    <option value="">全店舗</option>
                    @foreach($this->getStoreOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">性別</label>
                <select wire:model.live="gender"
                        class="w-full rounded-lg border-gray-600 bg-gray-700 text-white text-sm py-2 px-3">
                    <option value="">すべて</option>
                    <option value="male">男性</option>
                    <option value="female">女性</option>
                    <option value="other">その他</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">処理年月</label>
                <input type="month" wire:model.live="baseMonth"
                       class="w-full rounded-lg border-gray-600 bg-gray-700 text-white text-sm py-2 px-3" />
            </div>

            <div>
                <button wire:click="exportCsv"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700
                               text-white text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    CSVダウンロード
                </button>
            </div>
        </div>
    </x-filament::section>

    {{-- レポートテーブル --}}
    <x-filament::section>
        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-800 text-gray-300">
                        <th rowspan="2" class="border border-gray-600 px-3 py-2 text-center whitespace-nowrap"></th>
                        <th rowspan="2" class="border border-gray-600 px-3 py-2 text-center whitespace-nowrap">来店数</th>
                        <th colspan="2" class="border border-gray-600 px-2 py-1 text-center whitespace-nowrap">同月再来</th>
                        @for($m = 1; $m <= 6; $m++)
                            <th colspan="2" class="border border-gray-600 px-2 py-1 text-center whitespace-nowrap">
                                {{ $monthLabels[$m] ?? '' }}<br><span class="text-xs font-normal">再来累計</span>
                            </th>
                        @endfor
                        <th colspan="2" class="border border-gray-600 px-2 py-1 text-center whitespace-nowrap">失客</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $key => $row)
                        {{-- 上段: 新規再来数 --}}
                        <tr class="{{ $key === '合計' ? 'font-semibold' : '' }} hover:bg-gray-800/50">
                            <td rowspan="2"
                                class="border border-gray-600 px-3 py-2 text-center font-bold whitespace-nowrap
                                       {{ $key === '合計' ? 'bg-amber-900/20' : '' }}">
                                {{ $row['label'] }}
                            </td>
                            <td rowspan="2"
                                class="border border-gray-600 px-3 py-2 text-center font-bold tabular-nums">
                                {{ number_format($row['total']) }}
                            </td>

                            {{-- 同月再来 --}}
                            <td class="border border-gray-600 px-2 py-1 text-right tabular-nums">
                                {{ number_format($row['same_month']['count']) }}
                            </td>
                            <td class="border border-gray-600 px-2 py-1 text-right tabular-nums">
                                {{ $row['same_month']['pct'] }}&nbsp;%
                            </td>

                            {{-- 月別: 新規再来 --}}
                            @for($m = 1; $m <= 6; $m++)
                                @php $md = $row['months'][$m]; @endphp
                                @if($md['is_future'])
                                    <td class="border border-gray-600 px-2 py-1 text-right text-gray-600 tabular-nums">0</td>
                                    <td class="border border-gray-600 px-2 py-1 text-right text-gray-600 tabular-nums">0.0&nbsp;%</td>
                                @else
                                    <td class="border border-gray-600 px-2 py-1 text-right tabular-nums">
                                        {{ number_format($md['net_new']) }}
                                    </td>
                                    <td class="border border-gray-600 px-2 py-1 text-right tabular-nums">
                                        {{ $md['net_new_pct'] }}&nbsp;%
                                    </td>
                                @endif
                            @endfor

                            {{-- 失客 --}}
                            <td rowspan="2" class="border border-gray-600 px-2 py-1 text-right font-bold tabular-nums text-red-400">
                                {{ number_format($row['lost']['count']) }}
                            </td>
                            <td rowspan="2" class="border border-gray-600 px-2 py-1 text-right font-bold tabular-nums text-red-400">
                                {{ $row['lost']['pct'] }}&nbsp;%
                            </td>
                        </tr>

                        {{-- 下段: 累計再来数（緑） --}}
                        <tr class="{{ $key === '合計' ? 'font-semibold' : '' }}">
                            {{-- 同月再来（累計＝同月と同じ） --}}
                            <td class="border border-gray-600 px-2 py-1 text-right tabular-nums bg-emerald-900/25 text-emerald-400">
                                {{ number_format($row['same_month']['count']) }}
                            </td>
                            <td class="border border-gray-600 px-2 py-1 text-right tabular-nums bg-emerald-900/25 text-emerald-400">
                                {{ $row['same_month']['pct'] }}&nbsp;%
                            </td>

                            @for($m = 1; $m <= 6; $m++)
                                @php $md = $row['months'][$m]; @endphp
                                @if($md['is_future'])
                                    <td class="border border-gray-600 px-2 py-1 text-right tabular-nums bg-emerald-900/10 text-gray-600">0</td>
                                    <td class="border border-gray-600 px-2 py-1 text-right tabular-nums bg-emerald-900/10 text-gray-600">0.0&nbsp;%</td>
                                @else
                                    <td class="border border-gray-600 px-2 py-1 text-right tabular-nums bg-emerald-900/25 text-emerald-400">
                                        {{ number_format($md['cumulative']) }}
                                    </td>
                                    <td class="border border-gray-600 px-2 py-1 text-right tabular-nums bg-emerald-900/25 text-emerald-400">
                                        {{ $md['cumulative_pct'] }}&nbsp;%
                                    </td>
                                @endif
                            @endfor
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" class="text-center py-8 text-gray-500">
                                データがありません
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 凡例 --}}
        <div class="mt-4 flex flex-wrap gap-6 text-xs text-gray-400">
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded" style="background: rgba(6,78,59,0.25)"></span>
                累計再来（累計ユニーク再来客数）
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded bg-red-900/50"></span>
                失客（追跡期間内に再来なし）
            </div>
            <div class="text-gray-500">
                カテゴリ: 新規=初来店 / 再来=累計2〜4回 / 準固定=累計5〜9回 / 固定=累計10回以上
            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
