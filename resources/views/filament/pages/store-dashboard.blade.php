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

    @if(empty($storeId))
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
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <x-filament::section>
            <x-slot name="heading">性別</x-slot>
            @include('filament.pages.partials.store-pie-breakdown', [
                'rows' => $genderRows,
                'colors' => ['#3b82f6', '#ec4899', '#8b5cf6', '#6b7280'],
            ])
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">年代</x-slot>
            @include('filament.pages.partials.store-pie-breakdown', [
                'rows' => $ageRows,
                'colors' => ['#38bdf8', '#22c55e', '#f59e0b', '#ef4444', '#a855f7', '#14b8a6', '#6b7280'],
            ])
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">初回登録時の来店状況</x-slot>
            @include('filament.pages.partials.store-pie-breakdown', [
                'rows' => $frequencyRows,
                'colors' => ['#22c55e', '#f59e0b', '#ef4444', '#6b7280'],
            ])
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">郵便番号エリアマップ</x-slot>
            @if(empty($googleMapsApiKey))
                <div class="py-8 text-center text-sm text-gray-500">Google Maps APIキーが未設定です</div>
            @elseif(empty($mapRows))
                <div class="py-8 text-center text-sm text-gray-500">地図に表示できる郵便番号データがありません</div>
            @else
                <div
                    id="store-postal-map"
                    data-api-key="{{ $googleMapsApiKey }}"
                    data-map-rows='@json($mapRows)'
                    style="height:420px; border-radius:8px; overflow:hidden; background:#111827;"
                ></div>

                @once
                    <script>
                    (() => {
                        if (window.wakamatsuStorePostalMapInitialized) {
                            return;
                        }

                        window.wakamatsuStorePostalMapInitialized = true;

                        window.wakamatsuLoadGoogleMaps = (apiKey) => {
                            if (window.google?.maps) {
                                return Promise.resolve();
                            }

                            window.wakamatsuGoogleMapsPromise = window.wakamatsuGoogleMapsPromise || new Promise((resolve, reject) => {
                                window.wakamatsuGoogleMapsLoaded = resolve;

                                const existing = document.querySelector('script[data-wakamatsu-google-maps]');
                                if (existing) {
                                    existing.addEventListener('error', reject, { once: true });
                                    return;
                                }

                                const script = document.createElement('script');
                                script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&callback=wakamatsuGoogleMapsLoaded`;
                                script.async = true;
                                script.defer = true;
                                script.dataset.wakamatsuGoogleMaps = 'true';
                                script.onerror = reject;
                                document.head.appendChild(script);
                            });

                            return window.wakamatsuGoogleMapsPromise;
                        };

                        const parseRows = (element) => {
                            try {
                                return JSON.parse(element.dataset.mapRows || '[]');
                            } catch (error) {
                                return [];
                            }
                        };

                        window.wakamatsuRenderStorePostalMap = () => {
                            const element = document.getElementById('store-postal-map');
                            if (!element) {
                                return;
                            }

                            const rows = parseRows(element);
                            if (rows.length === 0) {
                                return;
                            }

                            window.wakamatsuLoadGoogleMaps(element.dataset.apiKey)
                                .then(() => {
                                    const bounds = new google.maps.LatLngBounds();
                                    const map = new google.maps.Map(element, {
                                        center: { lat: rows[0].lat, lng: rows[0].lng },
                                        zoom: 12,
                                        mapTypeControl: false,
                                        streetViewControl: false,
                                        fullscreenControl: true,
                                    });
                                    const infoWindow = new google.maps.InfoWindow();

                                    rows.forEach((row) => {
                                        const position = { lat: Number(row.lat), lng: Number(row.lng) };
                                        bounds.extend(position);

                                        const circle = new google.maps.Circle({
                                            strokeColor: '#f97316',
                                            strokeOpacity: 0.85,
                                            strokeWeight: 1,
                                            fillColor: '#f97316',
                                            fillOpacity: Number(row.opacity || 0.35),
                                            map,
                                            center: position,
                                            radius: Number(row.radius || 600),
                                        });

                                        circle.addListener('click', () => {
                                            infoWindow.setPosition(position);
                                            infoWindow.setContent(`
                                                <div style="font-size:13px; line-height:1.7; color:#111827; min-width:150px;">
                                                    <strong style="color:#111827;">〒${row.postal_code}</strong><br>
                                                    <span style="color:#111827;">${Number(row.count || 0).toLocaleString()}人 / ${row.percent}%</span><br>
                                                    <span style="color:#4b5563;">${row.address || ''}</span>
                                                </div>
                                            `);
                                            infoWindow.open(map);
                                        });
                                    });

                                    if (rows.length > 1) {
                                        map.fitBounds(bounds, 48);
                                    }
                                })
                                .catch(() => {});
                        };

                        document.addEventListener('DOMContentLoaded', window.wakamatsuRenderStorePostalMap);
                        document.addEventListener('livewire:navigated', window.wakamatsuRenderStorePostalMap);
                        document.addEventListener('livewire:init', () => {
                            if (!window.Livewire) {
                                return;
                            }

                            window.Livewire.hook('morph.updated', () => {
                                window.wakamatsuRenderStorePostalMap();
                            });
                        });
                        window.wakamatsuRenderStorePostalMap();
                    })();
                    </script>
                @endonce
            @endif
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
