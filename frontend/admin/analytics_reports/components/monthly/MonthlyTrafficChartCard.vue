<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title class="d-flex flex-wrap align-center gap-2">
      <v-icon start color="primary">mdi-chart-line</v-icon>
      {{ t('analytics_monthly_traffic', 'Monthly traffic') }}
      <v-spacer />
      <div class="d-flex flex-wrap align-end ga-2">
        <FormField :label="t('analytics_range', 'Range')" class="monthly-chart-range-field">
          <v-select
            v-model="months"
            :items="rangeOptions"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
          />
        </FormField>
        <v-btn color="primary" :loading="loading" @click="load">{{ t('analytics_refresh', 'Refresh') }}</v-btn>
      </div>
    </v-card-title>
    <v-card-text>
      <div class="admin-analytics-chart-wrap">
        <canvas ref="canvasRef" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAnalyticsApi } from '../../composables/useAnalyticsApi';
import { useAnalyticsMessages } from '../../composables/useAnalyticsMessages';
import { useLineChart } from '../../composables/useLineChart';
import { MONTHLY_CHART_RANGE_OPTIONS } from '../../utils/constants';
import { formatMonthLabel } from '../../utils/formatters';
import FormField from '../shared/FormField.vue';

defineOptions({ name: 'MonthlyTrafficChartCard' });

const api = useAnalyticsApi();
const { t } = useI18n();
const { notifyError } = useAnalyticsMessages();
const { canvasRef, render } = useLineChart();

const rangeOptions = MONTHLY_CHART_RANGE_OPTIONS;
const months = ref(3);
const loading = ref(false);

function draw(rows) {
  const labels = rows.map((m) => formatMonthLabel(m));
  render({
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: t('analytics_pageviews', 'Pageviews'),
          data: rows.map((m) => m.pageviews || 0),
          borderColor: '#1976D2',
          backgroundColor: 'rgba(25,118,210,0.12)',
          fill: true,
          tension: 0.25,
          pointRadius: 2,
        },
        {
          label: t('analytics_unique_visitors_chart', 'Unique Visitors'),
          data: rows.map((m) => m.unique_visitors || 0),
          borderColor: '#43A047',
          backgroundColor: 'rgba(67,160,71,0.12)',
          fill: true,
          tension: 0.25,
          pointRadius: 2,
        },
        {
          label: t('analytics_downloads', 'Downloads'),
          data: rows.map((m) => m.downloads || 0),
          borderColor: '#FF7043',
          backgroundColor: 'rgba(255,112,67,0.12)',
          fill: true,
          tension: 0.25,
          pointRadius: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'top' } },
      scales: {
        x: { grid: { display: false } },
        y: { grid: { color: 'rgba(0,0,0,0.06)' }, beginAtZero: true },
      },
    },
  });
}

async function load() {
  loading.value = true;
  try {
    const res = await api.fetchMonthlyTotals({ months: months.value });
    const rows = res.data || [];
    await nextTick();
    draw(rows);
  } catch (e) {
    notifyError(e.message || t('analytics_load_chart_error', 'Failed to load monthly chart'));
  } finally {
    loading.value = false;
  }
}

watch(months, () => load());

onMounted(load);
</script>

<style scoped>
.monthly-chart-range-field {
  min-width: 200px;
}
</style>
