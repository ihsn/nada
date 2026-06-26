<template>
  <v-app>
    <v-main class="catalog-study-analytics-vue sa-analytics-layout">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
        {{ snackbar.text }}
      </v-snackbar>

      <div class="sa-page-title font-weight-bold">{{ lbl.title || 'Analytics' }}</div>

      <v-row class="sa-kpi-row gy-3">
        <v-col cols="12" sm="6" md="3">
          <v-sheet class="sa-kpi sa-kpi--views pa-4 rounded" border>
            <div class="sa-kpi-value">{{ fmtNum(totalViews) }}</div>
            <div class="sa-kpi-label">{{ lbl.kpi_all_views || 'All-time views' }}</div>
          </v-sheet>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-sheet class="sa-kpi sa-kpi--dl pa-4 rounded" border>
            <div class="sa-kpi-value">{{ fmtNum(totalDownloads) }}</div>
            <div class="sa-kpi-label">{{ lbl.kpi_all_downloads || 'All-time downloads' }}</div>
          </v-sheet>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-sheet class="sa-kpi sa-kpi--views pa-4 rounded" border>
            <div class="sa-kpi-value">
              <span v-if="loading">…</span>
              <span v-else>{{ fmtNum(curMonth.pageviews) }}</span>
            </div>
            <div class="sa-kpi-label">{{ lbl.kpi_views_month || 'Views this month' }}</div>
          </v-sheet>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-sheet class="sa-kpi sa-kpi--dl pa-4 rounded" border>
            <div class="sa-kpi-value">
              <span v-if="loading">…</span>
              <span v-else>{{ fmtNum(curMonth.downloads) }}</span>
            </div>
            <div class="sa-kpi-label">{{ lbl.kpi_downloads_month || 'Downloads this month' }}</div>
          </v-sheet>
        </v-col>
      </v-row>

      <v-card variant="flat" elevation="0" rounded="lg" class="sa-panel pa-4" border>
        <div class="sa-section-title mb-3">{{ lbl.section_trend || 'Monthly trend' }}</div>
        <div v-if="loading" class="text-body-2 text-medium-emphasis">{{ lbl.loading || 'Loading…' }}</div>
        <div v-else-if="monthlyData.length === 0" class="text-body-2 text-medium-emphasis font-italic">
          {{ lbl.empty_monthly || 'No monthly data recorded yet for this study.' }}
        </div>
        <div v-else class="sa-chart-wrap">
          <canvas ref="chartEl"></canvas>
        </div>
      </v-card>

      <v-card variant="flat" elevation="0" rounded="lg" class="sa-panel pa-4" border>
        <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
          <div class="sa-section-title mb-0">{{ lbl.section_monthly || 'Monthly breakdown' }}</div>
          <div class="d-flex flex-wrap align-center gap-1">
            <v-btn
              v-if="exportUrls.monthlyStudiesCsv"
              size="small"
              variant="tonal"
              class="text-none"
              :href="exportUrls.monthlyStudiesCsv"
            >
              CSV
            </v-btn>
            <v-btn
              v-if="exportUrls.monthlyStudiesJson"
              size="small"
              variant="tonal"
              class="text-none"
              :href="exportUrls.monthlyStudiesJson"
            >
              JSON
            </v-btn>
          </div>
        </div>
        <div v-if="loading" class="text-body-2 text-medium-emphasis">{{ lbl.loading || 'Loading…' }}</div>
        <div v-else-if="monthlyData.length === 0" class="text-body-2 text-medium-emphasis font-italic">{{ lbl.empty_short || 'No data yet.' }}</div>
        <div v-else class="sa-table-scroll">
          <table class="sa-table">
            <thead>
              <tr>
                <th>{{ lbl.col_period || 'Period' }}</th>
                <th class="text-end">{{ lbl.col_views || 'Views' }}</th>
                <th class="text-end">{{ lbl.col_unique || 'Unique visitors' }}</th>
                <th class="text-end">{{ lbl.col_downloads || 'Downloads' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in monthlyTableRows" :key="`${row.year}-${row.month}`">
                <td>{{ row.label }}</td>
                <td class="text-end"><span class="sa-badge sa-badge--views">{{ fmtNum(row.pageviews) }}</span></td>
                <td class="text-end">{{ fmtNum(row.unique_visitors) }}</td>
                <td class="text-end"><span class="sa-badge sa-badge--dl">{{ fmtNum(row.downloads) }}</span></td>
              </tr>
            </tbody>
            <tfoot v-if="monthlyTableRows.length > 0">
              <tr class="sa-table-foot">
                <td>{{ lbl.total || 'Total' }}</td>
                <td class="text-end">{{ fmtNum(monthlyTotal.pageviews) }}</td>
                <td class="text-end">—</td>
                <td class="text-end">{{ fmtNum(monthlyTotal.downloads) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </v-card>

      <v-card variant="flat" elevation="0" rounded="lg" class="sa-panel pa-4" border>
        <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
          <div class="sa-section-title mb-0">{{ lbl.section_files || 'File downloads' }}</div>
          <div class="d-flex flex-wrap align-center gap-1">
            <v-btn v-if="exportUrls.filesCsv" size="small" variant="tonal" class="text-none" :href="exportUrls.filesCsv">CSV</v-btn>
            <v-btn v-if="exportUrls.filesJson" size="small" variant="tonal" class="text-none" :href="exportUrls.filesJson">JSON</v-btn>
          </div>
        </div>
        <div v-if="filesLoading" class="text-body-2 text-medium-emphasis">{{ lbl.loading || 'Loading…' }}</div>
        <div v-else-if="fileDownloads.length === 0" class="text-body-2 text-medium-emphasis font-italic">
          {{ lbl.empty_files || 'No file download data yet.' }}
        </div>
        <div v-else class="sa-table-scroll">
          <table class="sa-table">
            <thead>
              <tr>
                <th>{{ lbl.col_file || 'File' }}</th>
                <th class="text-end">{{ lbl.col_downloads || 'Downloads' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in fileDownloads" :key="f.file_name">
                <td class="sa-file-name" :title="f.file_name">{{ f.file_name }}</td>
                <td class="text-end"><span class="sa-badge sa-badge--dl">{{ fmtNum(f.downloads) }}</span></td>
              </tr>
            </tbody>
            <tfoot v-if="fileDownloads.length > 0">
              <tr class="sa-table-foot">
                <td>{{ lbl.total || 'Total' }}</td>
                <td class="text-end">{{ fmtNum(fileDownloadsTotal) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </v-card>
    </v-main>
  </v-app>
</template>

<script setup>
import {
  CategoryScale,
  Chart,
  Filler,
  Legend,
  LinearScale,
  LineController,
  LineElement,
  PointElement,
  Tooltip,
} from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyAnalyticsApi } from './composables/useStudyAnalyticsApi';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Legend, Tooltip, Filler);

defineOptions({ name: 'CatalogStudyAnalyticsApp' });

const { config } = useAppConfig();
const { fetchMonthlyStudies, fetchMonthlyFiles } = useStudyAnalyticsApi();

const lbl = computed(() => config.value?.labels || {});

const totalViews = computed(() => Number(config.value?.totalViews ?? 0) || 0);
const totalDownloads = computed(() => Number(config.value?.totalDownloads ?? 0) || 0);

const exportUrls = computed(() => ({
  monthlyStudiesCsv: config.value?.exportMonthlyStudiesCsv || '',
  monthlyStudiesJson: config.value?.exportMonthlyStudiesJson || '',
  filesCsv: config.value?.exportFilesCsv || '',
  filesJson: config.value?.exportFilesJson || '',
}));

const loading = ref(true);
const filesLoading = ref(true);
const monthlyData = ref([]);
const filesData = ref([]);
const curMonth = ref({ pageviews: 0, downloads: 0 });
const chartEl = ref(null);
let trendChart = null;

const snackbar = ref({ show: false, text: '', color: 'surface' });

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function periodLabel(year, month) {
  return `${MONTH_NAMES[month - 1]} ${year}`;
}

const monthlyTableRows = computed(() =>
  monthlyData.value
    .slice()
    .reverse()
    .map((r) => ({
      year: r.year,
      month: r.month,
      label: periodLabel(r.year, r.month),
      pageviews: parseInt(r.pageviews, 10) || 0,
      unique_visitors: parseInt(r.unique_visitors, 10) || 0,
      downloads: parseInt(r.downloads, 10) || 0,
    })),
);

const monthlyTotal = computed(() =>
  monthlyTableRows.value.reduce(
    (acc, r) => {
      acc.pageviews += r.pageviews;
      acc.downloads += r.downloads;
      return acc;
    },
    { pageviews: 0, downloads: 0 },
  ),
);

const fileDownloads = computed(() => {
  const map = {};
  filesData.value.forEach((r) => {
    const fn = r.file_name || '(unknown)';
    if (!map[fn]) map[fn] = 0;
    map[fn] += parseInt(r.downloads, 10) || 0;
  });
  return Object.keys(map)
    .map((fn) => ({ file_name: fn, downloads: map[fn] }))
    .sort((a, b) => b.downloads - a.downloads);
});

const fileDownloadsTotal = computed(() => fileDownloads.value.reduce((sum, f) => sum + f.downloads, 0));

function fmtNum(n) {
  return Number(n || 0).toLocaleString();
}

function showSnack(text, color = 'surface') {
  snackbar.value = { show: true, text, color };
}

function setCurMonth(rows) {
  let found = null;
  for (let i = rows.length - 1; i >= 0; i -= 1) {
    const pv = parseInt(rows[i].pageviews, 10) || 0;
    const dl = parseInt(rows[i].downloads, 10) || 0;
    if (pv > 0 || dl > 0) {
      found = rows[i];
      break;
    }
  }
  if (found) {
    curMonth.value = {
      pageviews: parseInt(found.pageviews, 10) || 0,
      downloads: parseInt(found.downloads, 10) || 0,
    };
  } else {
    curMonth.value = { pageviews: 0, downloads: 0 };
  }
}

function destroyChart() {
  if (trendChart) {
    trendChart.destroy();
    trendChart = null;
  }
}

function renderChart(rows) {
  const canvas = chartEl.value;
  if (!canvas || !rows.length) return;
  destroyChart();

  const labels = rows.map((r) => periodLabel(r.year, r.month));
  const pageviews = rows.map((r) => parseInt(r.pageviews, 10) || 0);
  const downloads = rows.map((r) => parseInt(r.downloads, 10) || 0);

  trendChart = new Chart(canvas, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: lbl.value.chart_pageviews || 'Pageviews',
          data: pageviews,
          borderColor: 'rgba(51,122,183,1)',
          backgroundColor: 'rgba(51,122,183,0.10)',
          borderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3,
          fill: true,
        },
        {
          label: lbl.value.chart_downloads || 'Downloads',
          data: downloads,
          borderColor: 'rgba(217,83,79,1)',
          backgroundColor: 'rgba(217,83,79,0.08)',
          borderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: { font: { size: 11 }, boxWidth: 10 },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 10 } },
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: { font: { size: 10 } },
        },
      },
    },
  });
}

async function loadMonthly() {
  loading.value = true;
  try {
    const rows = await fetchMonthlyStudies();
    const sorted = rows
      .filter((r) => r.year !== 0 || r.month !== 0)
      .sort((a, b) => a.year * 100 + a.month - (b.year * 100 + b.month));
    monthlyData.value = sorted;
    setCurMonth(sorted);
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    monthlyData.value = [];
  } finally {
    loading.value = false;
  }
}

async function loadFiles() {
  filesLoading.value = true;
  try {
    const rows = await fetchMonthlyFiles();
    filesData.value = rows.filter((r) => r.year !== 0 || r.month !== 0);
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    filesData.value = [];
  } finally {
    filesLoading.value = false;
  }
}

watch(
  () => [loading.value, monthlyData.value],
  async ([ld, rows]) => {
    if (ld || !rows?.length) {
      destroyChart();
      return;
    }
    await nextTick();
    renderChart(rows);
  },
  { flush: 'post' },
);

onBeforeUnmount(() => {
  destroyChart();
});

onMounted(async () => {
  await Promise.all([loadMonthly(), loadFiles()]);
});
</script>

<style scoped>
.catalog-study-analytics-vue {
  padding: 1.5rem 1.25rem 2rem;
  background-color: rgb(var(--v-theme-surface));
  border-radius: 8px;
  min-height: 200px;
  font-size: 0.875rem;
}

.sa-analytics-layout {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.sa-page-title {
  font-size: 0.9375rem;
  line-height: 1.4;
  letter-spacing: 0.01em;
}

.sa-kpi-row {
  margin-inline: 0;
}

.sa-kpi-value {
  font-size: 1.45rem;
  font-weight: 600;
  line-height: 1.2;
  color: rgb(var(--v-theme-on-surface));
}

.sa-kpi-label {
  font-size: 0.72rem;
  color: rgba(var(--v-theme-on-surface), 0.65);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-top: 4px;
}

.sa-kpi {
  border-top: 3px solid rgb(var(--v-theme-primary));
}

.sa-kpi--views {
  border-top-color: #337ab7;
}

.sa-kpi--dl {
  border-top-color: #d9534f;
}

.sa-section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.85);
  padding-bottom: 6px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.sa-chart-wrap {
  position: relative;
  height: 220px;
}

.sa-panel {
  background-color: rgb(var(--v-theme-surface));
}

.sa-table-scroll {
  overflow-x: auto;
}

.sa-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.sa-table th,
.sa-table td {
  padding: 8px 10px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.sa-table th {
  background-color: rgba(var(--v-theme-on-surface), 0.06);
  font-weight: 600;
  text-align: start;
}

.sa-table-foot td {
  font-weight: 600;
  background-color: rgba(var(--v-theme-on-surface), 0.04);
}

.sa-file-name {
  word-break: break-all;
  max-width: min(70vw, 720px);
}

.sa-badge {
  display: inline-block;
  border-radius: 3px;
  padding: 2px 6px;
  font-size: 0.75rem;
  color: #fff;
}

.sa-badge--views {
  background: #337ab7;
}

.sa-badge--dl {
  background: #d9534f;
}
</style>
