<x-filament-widgets::widget>
  <x-filament::section heading="属性分析">
    <div
      x-data="{
        charts: [],
        genderData: @js($genderData),
        rankData: @js($rankData),
        birthMonthData: @js($birthMonthData),
        visitData: @js($visitData),
        lastVisitData: @js($lastVisitData),

        loadChartJs() {
          return new Promise((resolve) => {
            if (typeof window.Chart !== 'undefined') {
              resolve();
              return;
            }
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
            script.onload = () => resolve();
            script.onerror = () => resolve();
            document.head.appendChild(script);
          });
        },

        async initCharts() {
          await this.loadChartJs();

          if (typeof window.Chart === 'undefined') return;

          this.$nextTick(() => {
            this.renderDoughnut(this.$refs.genderChart, this.genderData);
            this.renderDoughnut(this.$refs.rankChart, this.rankData);
            this.renderBar(this.$refs.birthMonthChart, this.birthMonthData, '#f59e0b', false);
            this.renderBar(this.$refs.visitChart, this.visitData, '#fbbf24', false);
            this.renderBar(this.$refs.lastVisitChart, this.lastVisitData, null, true);
          });
        },

        renderDoughnut(canvas, data) {
          if (!canvas) return;
          this.charts.push(new Chart(canvas, {
            type: 'doughnut',
            data: {
              labels: data.labels,
              datasets: [{
                data: data.values,
                backgroundColor: data.colors,
                borderWidth: 0,
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,
              plugins: {
                legend: { position: 'bottom', labels: { color: '#9ca3af', font: { size: 11 } } }
              }
            }
          }));
        },

        renderBar(canvas, data, color, horizontal) {
          if (!canvas) return;
          const bgColors = data.colors || data.values.map(() => color);
          this.charts.push(new Chart(canvas, {
            type: 'bar',
            data: {
              labels: data.labels,
              datasets: [{
                data: data.values,
                backgroundColor: bgColors,
                borderWidth: 0,
                borderRadius: 4,
              }]
            },
            options: {
              indexAxis: horizontal ? 'y' : 'x',
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false }
              },
              scales: {
                x: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,.06)' } },
                y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,.06)' } }
              }
            }
          }));
        }
      }"
      x-init="initCharts()"
    >
      {{-- 2列グリッド: ドーナツ2つ --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-gray-700 p-4">
          <div class="text-sm font-semibold mb-3 text-gray-300">性別分布</div>
          <div class="flex justify-center" style="max-height:220px">
            <canvas x-ref="genderChart"></canvas>
          </div>
        </div>
        <div class="rounded-xl border border-gray-700 p-4">
          <div class="text-sm font-semibold mb-3 text-gray-300">ランク分布</div>
          <div class="flex justify-center" style="max-height:220px">
            <canvas x-ref="rankChart"></canvas>
          </div>
        </div>
      </div>

      {{-- 2列グリッド: 棒グラフ2つ --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        <div class="rounded-xl border border-gray-700 p-4">
          <div class="text-sm font-semibold mb-3 text-gray-300">誕生月分布</div>
          <div style="height:200px">
            <canvas x-ref="birthMonthChart"></canvas>
          </div>
        </div>
        <div class="rounded-xl border border-gray-700 p-4">
          <div class="text-sm font-semibold mb-3 text-gray-300">来店回数分布</div>
          <div style="height:200px">
            <canvas x-ref="visitChart"></canvas>
          </div>
        </div>
      </div>

      {{-- 全幅: 横棒グラフ --}}
      <div class="mt-6 rounded-xl border border-gray-700 p-4">
        <div class="text-sm font-semibold mb-3 text-gray-300">最終来店経過日数</div>
        <div style="height:200px">
          <canvas x-ref="lastVisitChart"></canvas>
        </div>
      </div>
    </div>
  </x-filament::section>
</x-filament-widgets::widget>
