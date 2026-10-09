@php
    $rows = collect($rows);
    $colors = $colors ?? ['#3b82f6', '#ec4899', '#f59e0b', '#6b7280'];
    $chartId = 'store-pie-' . md5(json_encode($rows->values()->toArray()) . json_encode($colors) . uniqid('', true));
    $labels = $rows->pluck('label')->values()->toArray();
    $values = $rows->pluck('count')->map(fn ($count) => (int) $count)->values()->toArray();
@endphp

<div
    class="store-pie-breakdown"
    wire:loading.class="opacity-50"
    style="display:grid; grid-template-columns:240px minmax(0, 1fr); gap:28px; align-items:center;"
>
    <div style="display:flex; justify-content:flex-start;">
        <div style="width:220px; height:220px;">
            <canvas
                id="{{ $chartId }}"
                class="js-store-pie-chart"
                width="220"
                height="220"
                data-labels='@json($labels)'
                data-values='@json($values)'
                data-colors='@json($colors)'
                style="display:block; width:220px; height:220px;"
            ></canvas>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px; min-width:0;">
        @forelse($rows as $index => $row)
            <div style="display:grid; grid-template-columns:minmax(0, 1fr) auto; gap:16px; align-items:center; font-size:14px;">
                <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                    <span
                        style="width:12px; height:12px; flex:0 0 12px; border-radius:2px; background: {{ $colors[$index % count($colors)] ?? '#6b7280' }};"
                    ></span>
                    <span class="font-medium text-gray-950 dark:text-white" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $row['label'] }}</span>
                </div>
                <span class="tabular-nums text-gray-600 dark:text-gray-300" style="white-space:nowrap;">
                    {{ number_format($row['count']) }}人 / {{ $row['percent'] }}%
                </span>
            </div>
        @empty
            <div class="py-8 text-sm text-gray-500">データがありません</div>
        @endforelse
    </div>
</div>

@once
    <style>
        @media (max-width: 640px) {
            .store-pie-breakdown {
                grid-template-columns: 1fr !important;
            }

            .store-pie-breakdown > div:first-child {
                justify-content: center !important;
            }
        }
    </style>
@endonce

@once
    <script>
    (() => {
        if (window.wakamatsuStorePieRendererInitialized) {
            return;
        }

        window.wakamatsuStorePieRendererInitialized = true;
        window.wakamatsuStorePieCharts = window.wakamatsuStorePieCharts || {};

        window.wakamatsuLoadChartJs = window.wakamatsuLoadChartJs || (() => {
            let promise = null;

            return () => {
                if (window.Chart) {
                    return Promise.resolve();
                }

                if (promise) {
                    return promise;
                }

                promise = new Promise((resolve, reject) => {
                    const existing = document.querySelector('script[data-wakamatsu-chartjs]');

                    if (existing) {
                        existing.addEventListener('load', resolve, { once: true });
                        existing.addEventListener('error', reject, { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
                    script.dataset.wakamatsuChartjs = 'true';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });

                return promise;
            };
        })();

        const parseJson = (value, fallback) => {
            try {
                return JSON.parse(value || '');
            } catch (error) {
                return fallback;
            }
        };

        const renderCanvas = (canvas) => {
            if (!canvas || !window.Chart) {
                return;
            }

            const labels = parseJson(canvas.dataset.labels, []);
            const values = parseJson(canvas.dataset.values, []);
            const colors = parseJson(canvas.dataset.colors, []);
            const dataHash = JSON.stringify({ labels, values, colors });

            if (canvas.dataset.renderedHash === dataHash && canvas.dataset.chartInstanceId) {
                return;
            }

            if (canvas.dataset.chartInstanceId && window.wakamatsuStorePieCharts[canvas.dataset.chartInstanceId]) {
                window.wakamatsuStorePieCharts[canvas.dataset.chartInstanceId].destroy();
                delete window.wakamatsuStorePieCharts[canvas.dataset.chartInstanceId];
            }

            const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
            const instanceId = canvas.id + '-' + Date.now();
            canvas.dataset.chartInstanceId = instanceId;
            canvas.dataset.renderedHash = dataHash;

            window.wakamatsuStorePieCharts[instanceId] = new Chart(canvas, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: 'rgba(255,255,255,.1)',
                        borderWidth: 1,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    const value = Number(context.raw || 0);
                                    const percent = total > 0 ? Math.round((value / total) * 1000) / 10 : 0;
                                    return ` ${context.label}: ${value.toLocaleString()}人 / ${percent}%`;
                                },
                            },
                        },
                    },
                },
            });
        };

        window.wakamatsuRenderStorePieCharts = () => {
            window.wakamatsuLoadChartJs()
                .then(() => requestAnimationFrame(() => {
                    document.querySelectorAll('canvas.js-store-pie-chart').forEach(renderCanvas);
                }))
                .catch(() => {});
        };

        document.addEventListener('DOMContentLoaded', window.wakamatsuRenderStorePieCharts);
        document.addEventListener('livewire:navigated', window.wakamatsuRenderStorePieCharts);

        document.addEventListener('livewire:init', () => {
            if (!window.Livewire) {
                return;
            }

            window.Livewire.hook('morph.updated', () => {
                window.wakamatsuRenderStorePieCharts();
            });
        });

        window.wakamatsuRenderStorePieCharts();
    })();
    </script>
@endonce
