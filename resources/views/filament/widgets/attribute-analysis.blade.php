<x-filament-widgets::widget>
  <x-filament::section heading="属性分析">
    <div
      x-data="{
        ready: false,
        retryCount: 0,
        genderData: @js($genderData),
        rankData: @js($rankData),
        birthMonthData: @js($birthMonthData),
        visitData: @js($visitData),
        lastVisitData: @js($lastVisitData),
        initCharts() {
          if (typeof Chart === 'undefined') {
            if (this.retryCount < 20) {
              this.retryCount++;
              setTimeout(() => this.initCharts(), 300);
            }
            return;
          }
          this.ready = true;
          this.$nextTick(() => {
            this.renderDoughnut('genderChart', this.genderData);
            this.renderDoughnut('rankChart', this.rankData);
            this.renderBar('birthMonthChart', this.birthMonthData, '#f59e0b', false);
            this.renderBar('visitChart', this.visitData, '#fbbf24', false);
            this.renderBar('lastVisitChart', this.lastVisitData, null, true);
          });
        },
        renderDoughnut(id, data) {
          const ctx = document.getElementById(id);
          if (!ctx) return;
          new Chart(ctx, {
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
          });
        },
        renderBar(id, data, color, horizontal) {
          const ctx = document.getElementById(id);
          if (!ctx) return;
          const bgColors = data.colors || data.values.map(() => color);
          new Chart(ctx, {
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
          });
        }
      }"
      x-init="initCharts()"
    >
      {{-- 2列グリッド: ドーナツ2つ --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-gray-700 p-4">
          <div class="text-sm font-semibold mb-3 text-gray-300">性別分布</div>
          <div class="flex justify-center" style="max-height:220px">
            <canvas id="genderChart"></canvas>
          </div>
        </div>
        <div class="rounded-xl border border-gray-700 p-4">
          <div class="text-sm font-semibold mb-3 text-gray-300">ランク分布</div>
          <div class="flex justify-center" style="max-height:220px">
            <canvas id="rankChart"></canvas>
          </div>
        </div>
      </div>

      {{-- 2列グリッド: 棒グラフ2つ --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div class="rounded-xl border border-gray-700 p-4">
          <div class="text-sm font-semibold mb-3 text-gray-300">誕生月分布</div>
          <div style="height:200px">
            <canvas id="birthMonthChart"></canvas>
          </div>
        </div>
        <div class="rounded-xl border border-gray-700 p-4">
          <div class="text-sm font-semibold mb-3 text-gray-300">来店回数分布</div>
          <div style="height:200px">
            <canvas id="visitChart"></canvas>
          </div>
        </div>
      </div>

      {{-- 全幅: 横棒グラフ --}}
      <div class="mt-4 rounded-xl border border-gray-700 p-4">
        <div class="text-sm font-semibold mb-3 text-gray-300">最終来店経過日数</div>
        <div style="height:200px">
          <canvas id="lastVisitChart"></canvas>
        </div>
      </div>
    </div>
  </x-filament::section>
</x-filament-widgets::widget>
