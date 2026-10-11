<template>
  <div class="admin-analytics-page-stack">
    <AggregationHealthAlert :last-completed-at="lastCompletedAt" />
    <OverviewKpiRow
      :today="today"
      :month-stats="monthStats"
      :prev-month="prevMonth"
      :month-label="monthLabel"
      :daily-days="dailyDays"
      :loading="loading"
      :timezone="timezone"
    />
    <div class="admin-analytics-two-col">
      <TopStudiesCard :items="topStudies" :loading="loading" :period-label="monthLabel" />
      <TopFilesCard :items="topFiles" :loading="loading" :period-label="monthLabel" />
    </div>
    <RecentActivityCard
      :pageviews="recentPageviews"
      :downloads="recentDownloads"
      :timezone="timezone"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useAnalyticsApi } from '../composables/useAnalyticsApi';
import { useAnalyticsMessages } from '../composables/useAnalyticsMessages';
import AggregationHealthAlert from '../components/AggregationHealthAlert.vue';
import OverviewKpiRow from '../components/overview/OverviewKpiRow.vue';
import TopStudiesCard from '../components/overview/TopStudiesCard.vue';
import TopFilesCard from '../components/overview/TopFilesCard.vue';
import RecentActivityCard from '../components/overview/RecentActivityCard.vue';

defineOptions({ name: 'OverviewPage' });

const { config } = useAppConfig();
const api = useAnalyticsApi();
const { notifyError } = useAnalyticsMessages();

const loading = ref(true);
const timezone = ref(config.value?.serverTimezone || 'UTC');
const lastCompletedAt = ref(null);
const today = ref({ pageviews: 0, downloads: 0 });
const monthStats = ref({ pageviews: 0, downloads: 0 });
const prevMonth = ref({ pageviews: 0, downloads: 0 });
const monthLabel = ref('This month');
const dailyDays = ref([]);
const topStudies = ref([]);
const topFiles = ref([]);
const recentPageviews = ref([]);
const recentDownloads = ref([]);

function resolveMonthLabel(kpiMonth, serverDate) {
  if (!kpiMonth) return 'This month';
  const parts = String(serverDate || '').split('-');
  if (parts.length === 3 && Number(kpiMonth.year) === Number(parts[0]) && Number(kpiMonth.month) === Number(parts[1])) {
    return 'This month';
  }
  return kpiMonth.label || 'This month';
}

async function load() {
  loading.value = true;
  try {
    const [overviewRes, metaRes] = await Promise.all([api.fetchOverview(), api.fetchMeta()]);
    const d = overviewRes.data || {};
    const meta = metaRes.data || {};
    timezone.value = meta.timezone || config.value?.serverTimezone || 'UTC';

    today.value = d.today || { pageviews: 0, downloads: 0 };
    recentPageviews.value = d.recent_pageviews || [];
    recentDownloads.value = d.recent_downloads || [];
    dailyDays.value = d.daily_last_7_days || [];
    topStudies.value = d.top_studies || [];
    topFiles.value = d.top_files || [];
    monthStats.value = d.this_month || { pageviews: 0, downloads: 0 };
    prevMonth.value = d.previous_month || { pageviews: 0, downloads: 0 };
    monthLabel.value = resolveMonthLabel(d.kpi_month, d.server_date || meta.server_date);
    lastCompletedAt.value = d.aggregation?.last_completed_at || meta.last_completed_at || null;
  } catch (e) {
    notifyError(e.message || 'Failed to load overview');
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
