<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>
      <v-icon start>mdi-eye</v-icon>
      {{ t('analytics_raw_pageviews', 'Raw pageviews') }}
    </v-card-title>
    <v-card-text class="admin-analytics-card-sections">
      <DateRangeFilter
        v-model="filters"
        :loading="loading"
        :reset-on-clear="defaultRange"
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
          {{ formatDateTime(item.ts, timezone) }}
        </template>
        <template #item.study_title="{ item }">
          <a
            v-if="item.study_id"
            :href="adminStudyEditUrl(siteUrl, item)"
            class="admin-analytics-cell-truncate admin-analytics-study-link"
            :title="item.study_title || t('analytics_study_number', 'Study %s', item.study_id)"
          >
            {{ item.study_title || t('analytics_study_number', 'Study %s', item.study_id) }}
          </a>
          <span v-else>—</span>
        </template>
        <template #item.session_id="{ item }">
          <span v-if="item.session_id" class="font-mono admin-analytics-mono-cell">
            {{ String(item.session_id).slice(0, 16) }}…
          </span>
          <span v-else>—</span>
        </template>
        <template #item.page_url="{ item }">
          <v-tooltip v-if="item.page_url" :text="item.page_url" location="top" max-width="480">
            <template #activator="{ props: tipProps }">
              <span v-bind="tipProps" class="admin-analytics-cell-truncate">{{ item.page_url }}</span>
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
import { adminStudyEditUrl, formatDateTime } from '../utils/formatters';
import { defaultRawDateRange } from '../utils/dateDefaults';
import DateRangeFilter from '../components/shared/DateRangeFilter.vue';
import PaginatedTableFooter from '../components/shared/PaginatedTableFooter.vue';

defineOptions({ name: 'RawPageviewsPage' });

const { config, siteUrl } = useAppConfig();
const { t } = useI18n();
const timezone = computed(() => config.value?.serverTimezone || 'UTC');
const api = useAnalyticsApi();
const { notifyError } = useAnalyticsMessages();

const serverDate = computed(() => config.value?.serverDate || '');
const defaultRange = computed(() => defaultRawDateRange(serverDate.value));

const filters = ref(defaultRawDateRange(serverDate.value));
const loading = ref(false);
const rows = ref([]);
const total = ref(0);
const page = ref(1);
const limit = 50;

const headers = [
  { title: t('analytics_time', 'Time'), key: 'ts' },
  { title: t('analytics_study', 'Study'), key: 'study_title', minWidth: '200px' },
  { title: t('analytics_id', 'ID'), key: 'study_id', width: '72px' },
  { title: t('analytics_session', 'Session'), key: 'session_id' },
  { title: t('analytics_page_url', 'Page URL'), key: 'page_url', maxWidth: '360px' },
  { title: t('analytics_section', 'Section'), key: 'section' },
];

async function load() {
  if (!filters.value.date_from || !filters.value.date_to) {
    notifyError(t('analytics_required_dates', 'From and To dates are required'));
    return;
  }
  loading.value = true;
  try {
    const params = {
      limit,
      offset: (page.value - 1) * limit,
      sort_by: 'ts',
      sort_order: 'desc',
      date_from: filters.value.date_from,
      date_to: filters.value.date_to,
    };
    if (filters.value.study_id) params.study_id = filters.value.study_id;
    const res = await api.fetchRawPageviews(params);
    rows.value = res.data || [];
    total.value = res.total || 0;
  } catch (e) {
    notifyError(e.message || t('analytics_load_pageviews_error', 'Failed to load pageviews'));
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

onMounted(() => {
  filters.value = defaultRawDateRange(serverDate.value);
  load();
});
</script>
