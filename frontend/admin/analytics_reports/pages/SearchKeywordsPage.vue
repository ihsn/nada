<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title class="d-flex flex-wrap align-center gap-2">
      <v-icon start>mdi-magnify</v-icon>
      {{ t('analytics_search_keywords', 'Search keywords') }}
      <v-spacer />
      <v-btn
        variant="outlined"
        prepend-icon="mdi-download"
        :disabled="loading || !filters.date_from || !filters.date_to"
        @click="onExport"
      >
        {{ t('analytics_export_csv', 'Export CSV') }}
      </v-btn>
    </v-card-title>
    <v-card-text class="admin-analytics-card-sections">
      <DateRangeFilter
        v-model="filters"
        :loading="loading"
        :reset-on-clear="defaultRange"
        :show-study-filter="false"
        @apply="reload"
        @clear="reload"
      />
      <v-data-table-server
        class="admin-analytics-data-table"
        density="compact"
        :headers="headers"
        :items="rows"
        :loading="loading"
        :items-length="total"
        :items-per-page="limit"
        :page="page"
        hide-default-footer
        @update:page="onPage"
      >
        <template #item.ts="{ item }">
          {{ formatUnixTs(item.ts, timezone) }}
        </template>
        <template #item.keyword="{ item }">
          <span class="font-weight-medium">{{ item.keyword || '—' }}</span>
        </template>
        <template #item.username="{ item }">
          {{ item.username || '—' }}
        </template>
        <template #item.url="{ item }">
          <v-tooltip v-if="item.url" :text="item.url" location="top" max-width="480">
            <template #activator="{ props: tipProps }">
              <span v-bind="tipProps" class="admin-analytics-cell-truncate">{{ item.url }}</span>
            </template>
          </v-tooltip>
          <span v-else>—</span>
        </template>
      </v-data-table-server>
      <PaginatedTableFooter v-model:page="page" :limit="limit" :total="total" @update:page="onPage" />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useAnalyticsApi } from '../composables/useAnalyticsApi';
import { useAnalyticsMessages } from '../composables/useAnalyticsMessages';
import { analyticsLocale } from '../utils/formatters';
import { defaultLastNDaysRange } from '../utils/dateDefaults';
import DateRangeFilter from '../components/shared/DateRangeFilter.vue';
import PaginatedTableFooter from '../components/shared/PaginatedTableFooter.vue';

defineOptions({ name: 'SearchKeywordsPage' });

const { config } = useAppConfig();
const { t } = useI18n();
const timezone = computed(() => config.value?.serverTimezone || 'UTC');
const api = useAnalyticsApi();
const { notifyError } = useAnalyticsMessages();

const serverDate = computed(() => config.value?.serverDate || '');
const defaultRange = computed(() => defaultLastNDaysRange(serverDate.value, 10));

const filters = ref(defaultLastNDaysRange(serverDate.value, 10));
const loading = ref(false);
const rows = ref([]);
const total = ref(0);
const page = ref(1);
const limit = 50;

const headers = [
  { title: t('analytics_time', 'Time'), key: 'ts' },
  { title: t('analytics_keyword', 'Keyword'), key: 'keyword', minWidth: '160px' },
  { title: t('analytics_user', 'User'), key: 'username' },
  { title: t('analytics_page_url', 'Page URL'), key: 'url', maxWidth: '360px' },
];

function formatUnixTs(ts, timeZone) {
  if (!ts) return '—';
  const d = new Date(Number(ts) * 1000);
  if (Number.isNaN(d.getTime())) return '—';
  try {
    return d.toLocaleString(analyticsLocale(), timeZone ? { timeZone } : undefined);
  } catch {
    return d.toLocaleString();
  }
}

async function load() {
  if (!filters.value.date_from || !filters.value.date_to) {
    notifyError(t('analytics_required_dates', 'From and To dates are required'));
    return;
  }
  loading.value = true;
  try {
    const res = await api.fetchSearchKeywords({
      limit,
      offset: (page.value - 1) * limit,
      date_from: filters.value.date_from,
      date_to: filters.value.date_to,
    });
    rows.value = res.data || [];
    total.value = res.total || 0;
  } catch (e) {
    notifyError(e.message || t('analytics_load_keywords_error', 'Failed to load search keywords'));
  } finally {
    loading.value = false;
  }
}

function onPage(p) {
  page.value = p;
  load();
}

function reload() {
  page.value = 1;
  load();
}

function onExport() {
  if (!filters.value.date_from || !filters.value.date_to) {
    notifyError(t('analytics_required_dates', 'From and To dates are required'));
    return;
  }
  window.open(
    api.exportUrl('/search/keywords/export', {
      date_from: filters.value.date_from,
      date_to: filters.value.date_to,
    }),
    '_blank'
  );
}

onMounted(() => {
  filters.value = defaultLastNDaysRange(serverDate.value, 10);
  load();
});
</script>
