<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>
      <v-icon start>mdi-eye</v-icon>
      Raw pageviews
    </v-card-title>
    <v-card-text class="admin-analytics-card-sections">
      <RawLogsHintAlert :max-days="rawMaxDays" />
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
            :title="item.study_title || `Study ${item.study_id}`"
          >
            {{ item.study_title || `Study ${item.study_id}` }}
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
import { useAnalyticsApi } from '../composables/useAnalyticsApi';
import { useAnalyticsMessages } from '../composables/useAnalyticsMessages';
import { adminStudyEditUrl, formatDateTime } from '../utils/formatters';
import { defaultRawDateRange } from '../utils/dateDefaults';
import DateRangeFilter from '../components/shared/DateRangeFilter.vue';
import PaginatedTableFooter from '../components/shared/PaginatedTableFooter.vue';
import RawLogsHintAlert from '../components/shared/RawLogsHintAlert.vue';

defineOptions({ name: 'RawPageviewsPage' });

const { config, siteUrl } = useAppConfig();
const timezone = computed(() => config.value?.serverTimezone || 'UTC');
const api = useAnalyticsApi();
const { notifyError } = useAnalyticsMessages();

const serverDate = computed(() => config.value?.serverDate || '');
const defaultRange = computed(() => defaultRawDateRange(serverDate.value));
const rawMaxDays = ref(31);

const filters = ref(defaultRawDateRange(serverDate.value));
const loading = ref(false);
const rows = ref([]);
const total = ref(0);
const page = ref(1);
const limit = 50;

const headers = [
  { title: 'Time', key: 'ts' },
  { title: 'Study', key: 'study_title', minWidth: '200px' },
  { title: 'ID', key: 'study_id', width: '72px' },
  { title: 'Session', key: 'session_id' },
  { title: 'Page URL', key: 'page_url', maxWidth: '360px' },
  { title: 'Section', key: 'section' },
];

async function load() {
  if (!filters.value.date_from || !filters.value.date_to) {
    notifyError('From and To dates are required');
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
    notifyError(e.message || 'Failed to load pageviews');
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

onMounted(async () => {
  try {
    const meta = await api.fetchMeta();
    if (meta.data?.raw_list_max_days) {
      rawMaxDays.value = meta.data.raw_list_max_days;
    }
  } catch {
    /* keep default */
  }
  filters.value = defaultRawDateRange(serverDate.value);
  load();
});
</script>
