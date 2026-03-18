<x-filament-panels::page>

    {{-- フィルタ --}}
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
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
                <label class="fi-fo-field-wrp-label block text-sm font-medium mb-1">性別</label>
                <select wire:model.live="gender"
                        class="fi-input block w-full rounded-lg shadow-sm border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-950 dark:text-white text-sm py-2 px-3">
                    <option value="">すべて</option>
                    <option value="male">男性</option>
                    <option value="female">女性</option>
                    <option value="other">その他</option>
                </select>
            </div>

            <div>
                <label class="fi-fo-field-wrp-label block text-sm font-medium mb-1">処理年月</label>
                <select wire:model.live="baseMonth"
                        class="fi-input block w-full rounded-lg shadow-sm border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-950 dark:text-white text-sm py-2 px-3">
                    @foreach($this->getMonthOptions() as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <button wire:click="exportCsv"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold
                               text-white shadow-sm transition
                               bg-success-600 hover:bg-success-500
                               dark:bg-success-500 dark:hover:bg-success-400">
                    <x-heroicon-s-arrow-down-tray class="w-4 h-4" />
                    CSVダウンロード
                </button>
            </div>
        </div>
    </x-filament::section>

    {{-- レポートテーブル --}}
    <x-filament::section>
        <div class="overflow-x-auto -mx-6 px-6" wire:loading.class="opacity-50">
            <table style="width:100%; border-collapse:collapse; font-size:16px;">
                {{-- ヘッダー --}}
                <thead>
                    <tr>
                        <th rowspan="2" style="background:#2d6a4f; color:#fff; border:1px solid #4a4a4a; padding:8px 12px; text-align:center; min-width:70px;"></th>
                        <th rowspan="2" style="background:#2d6a4f; color:#fff; border:1px solid #4a4a4a; padding:8px 12px; text-align:center; white-space:nowrap;">来店数</th>
                        <th colspan="2" style="background:#2d6a4f; color:#fff; border:1px solid #4a4a4a; padding:6px 8px; text-align:center; white-space:nowrap;">同月再来</th>
                        @for($m = 1; $m <= 6; $m++)
                            <th colspan="2" style="background:#2d6a4f; color:#fff; border:1px solid #4a4a4a; padding:6px 8px; text-align:center; white-space:nowrap;">
                                {{ $monthLabels[$m] ?? '' }}再来<br><span style="font-size:11px; font-weight:normal;">累計</span>
                            </th>
                        @endfor
                        <th rowspan="2" style="background:#8b0000; color:#fff; border:1px solid #4a4a4a; padding:8px 8px; text-align:center; white-space:nowrap;">失客</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $key => $row)
                        @php
                            $isTotal = $key === '合計';
                            $labelBg = $isTotal ? '#3a3a3a' : '#2a2a2a';
                            $cellBg = $isTotal ? '#333' : '#1e1e1e';
                        @endphp

                        {{-- 上段: 新規再来 --}}
                        <tr>
                            <td rowspan="2" style="background:{{ $labelBg }}; color:#fff; border:1px solid #4a4a4a; padding:8px 12px; text-align:center; font-weight:bold; white-space:nowrap;">
                                {{ $row['label'] }}
                            </td>
                            <td rowspan="2" style="background:{{ $cellBg }}; color:#fff; border:1px solid #4a4a4a; padding:8px 12px; text-align:center; font-weight:bold; font-variant-numeric:tabular-nums;">
                                {{ number_format($row['total']) }}
                            </td>

                            {{-- 同月再来 --}}
                            <td style="background:{{ $cellBg }}; color:#ddd; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-variant-numeric:tabular-nums;">
                                {{ number_format($row['same_month']['count']) }}
                            </td>
                            <td style="background:{{ $cellBg }}; color:#ddd; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-variant-numeric:tabular-nums;">
                                {{ $row['same_month']['pct'] }} %
                            </td>

                            {{-- 月別: 新規再来 --}}
                            @for($m = 1; $m <= 6; $m++)
                                @php $md = $row['months'][$m]; @endphp
                                <td style="background:{{ $cellBg }}; color:{{ $md['is_future'] ? '#555' : '#ddd' }}; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-variant-numeric:tabular-nums;">
                                    {{ number_format($md['net_new']) }}
                                </td>
                                <td style="background:{{ $cellBg }}; color:{{ $md['is_future'] ? '#555' : '#ddd' }}; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-variant-numeric:tabular-nums;">
                                    {{ $md['net_new_pct'] }} %
                                </td>
                            @endfor

                            {{-- 失客 --}}
                            <td rowspan="2" style="background:#3d1111; color:#f87171; border:1px solid #4a4a4a; padding:8px 8px; text-align:center; font-weight:bold; font-variant-numeric:tabular-nums; line-height:1.8;">
                                {{ number_format($row['lost']['count']) }}<br>{{ $row['lost']['pct'] }} %
                            </td>
                        </tr>

                        {{-- 下段: 累計再来（緑） --}}
                        <tr>
                            {{-- 同月再来 累計 --}}
                            <td style="background:#14532d; color:#4ade80; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-weight:bold; font-variant-numeric:tabular-nums;">
                                {{ number_format($row['same_month']['count']) }}
                            </td>
                            <td style="background:#14532d; color:#4ade80; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-weight:bold; font-variant-numeric:tabular-nums;">
                                {{ $row['same_month']['pct'] }} %
                            </td>

                            @for($m = 1; $m <= 6; $m++)
                                @php $md = $row['months'][$m]; @endphp
                                @if($md['is_future'])
                                    <td style="background:#0f2e1a; color:#555; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-variant-numeric:tabular-nums;">0</td>
                                    <td style="background:#0f2e1a; color:#555; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-variant-numeric:tabular-nums;">0.0 %</td>
                                @else
                                    <td style="background:#14532d; color:#4ade80; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-weight:bold; font-variant-numeric:tabular-nums;">
                                        {{ number_format($md['cumulative']) }}
                                    </td>
                                    <td style="background:#14532d; color:#4ade80; border:1px solid #4a4a4a; padding:4px 8px; text-align:right; font-weight:bold; font-variant-numeric:tabular-nums;">
                                        {{ $md['cumulative_pct'] }} %
                                    </td>
                                @endif
                            @endfor
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" style="text-align:center; padding:32px; color:#888;">
                                データがありません
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 凡例 --}}
        <div class="flex flex-wrap gap-6" style="color:#999; margin-top:24px; font-size:13px;">
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded" style="background:#14532d;"></span>
                累計再来（累計ユニーク再来客数）
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded" style="background:#3d1111;"></span>
                失客（追跡期間内に再来なし）
            </div>
            <div>
                新規=初来店 / 再来=累計2〜4回 / 準固定=累計5〜9回 / 固定=累計10回以上
            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
