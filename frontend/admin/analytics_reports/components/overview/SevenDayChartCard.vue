<template>
  <v-card class="admin-analytics-card h-100 d-flex flex-column" rounded="lg" elevation="1">
    <v-card-title class="d-flex align-center">
      <v-icon start color="primary" size="small">mdi-chart-line</v-icon>
      {{ t('analytics_last_7_days', 'Last 7 days') }}
      <v-spacer />
      <span class="text-caption text-medium-emphasis">{{ timezone }}</span>
      <v-progress-circular v-if="loading" indeterminate size="18" width="2" class="ml-2" />
    </v-card-title>
    <v-card-text class="flex-grow-1">
      <div class="admin-analytics-chart-wrap">
        <canvas ref="canvasRef" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { nextTick, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useLineChart } from '../../composables/useLineChart';
import { analyticsLocale } from '../../utils/formatters';

const props = defineProps({
  days: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  timezone: { type: String, default: '' },
});

defineOptions({ name: 'SevenDayChartCard' });

const { canvasRef, render } = useLineChart();
const { t } = useI18n();

function draw() {
  const labels = props.days.map((row) => {
    const d = new Date(`${row.date}T12:00:00`);
    return d.toLocaleDateString(analyticsLocale(), { month: 'short', day: 'numeric' });
  });
  render({
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: t('analytics_pageviews', 'Pageviews'),
          data: props.days.map((r) => r.pageviews || 0),
          borderColor: '#1976D2',
          backgroundColor: 'rgba(25,118,210,0.12)',
          fill: true,
          tension: 0.3,
        },
        {
          label: t('analytics_downloads', 'Downloads'),
          data: props.days.map((r) => r.downloads || 0),
          borderColor: '#FF7043',
          backgroundColor: 'rgba(255,112,67,0.10)',
          fill: true,
          tension: 0.3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } },
      },
    },
  });
}

watch(
  () => props.days,
  async () => {
    if (props.loading) return;
    await nextTick();
    draw();
  },
  { deep: true }
);
</script>
