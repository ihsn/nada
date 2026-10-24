<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title class="d-flex flex-wrap align-center gap-2">
      <v-icon start>mdi-calendar-today</v-icon>
      Daily aggregates
      <v-spacer />
      <StudiesFilesToggle v-model="dataView" />
      <ExportFormatMenu
        class="ml-2"
        :disabled="loading"
        :export-max-rows="exportMaxRows"
        @export="onExport"
      />
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
        :headers="headers"
        :items="rows"
        :loading="loading"
        :items-length="total"
        :items-per-page="limit"
        :page="page"
        hide-default-footer
        @update:page="onPage"
      />
      <PaginatedTableFooter v-model:page="page" :limit="limit" :total="total" @update:page="onPage" />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useAnalyticsApi } from '../composables/useAnalyticsApi';
import { useAnalyticsMessages } from '../composables/useAnalyticsMessages';
import { defaultDailyDateRange } from '../utils/dateDefaults';
import DateRangeFilter from '../components/shared/DateRangeFilter.vue';
import PaginatedTableFooter from '../components/shared/PaginatedTableFooter.vue';
import StudiesFilesToggle from '../components/shared/StudiesFilesToggle.vue';
import ExportFormatMenu from '../components/shared/ExportFormatMenu.vue';

defineOptions({ name: 'DailyDataPage' });

const { config } = useAppConfig();
const api = useAnalyticsApi();
const { notifyError } = useAnalyticsMessages();

const serverDate = computed(() => config.value?.serverDate || '');
const defaultRange = computed(() => defaultDailyDateRange(serverDate.value));
const exportMaxRows = ref(50000);

const dataView = ref('studies');
const filters = ref(defaultDailyDateRange(serverDate.value));
const loading = ref(false);
const rows = ref([]);
const total = ref(0);
const page = ref(1);
const limit = 50;

const studyHeaders = [
  { title: 'Date', key: 'date' },
  { title: 'ID', key: 'study_id' },
  { title: 'Title', key: 'title' },
  { title: 'Pageviews', key: 'pageviews' },
  { title: 'Unique visitors', key: 'unique_visitors' },
  { title: 'Downloads', key: 'downloads' },
];

const fileHeaders = [
  { title: 'Date', key: 'date' },
  { title: 'Study ID', key: 'study_id' },
  { title: 'Title', key: 'title' },
  { title: 'File name', key: 'file_name' },
  { title: 'Downloads', key: 'downloads' },
];

const headers = computed(() => (dataView.value === 'studies' ? studyHeaders : fileHeaders));

async function load() {
  loading.value = true;
  try {
    const params = {
      limit,
      offset: (page.value - 1) * limit,
    };
    if (filters.value.date_from) params.date_from = filters.value.date_from;
    if (filters.value.date_to) params.date_to = filters.value.date_to;
    if (filters.value.study_id) params.study_id = filters.value.study_id;

    const fetcher = dataView.value === 'studies' ? api.fetchDailyStudies : api.fetchDailyFiles;
    const res = await fetcher(params);
    rows.value = res.data || [];
    total.value = res.total || 0;
  } catch (e) {
    notifyError(e.message || 'Failed to load daily data');
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

function onExport(format) {
  const params = { format };
  if (filters.value.date_from) params.date_from = filters.value.date_from;
  if (filters.value.date_to) params.date_to = filters.value.date_to;
  if (filters.value.study_id) params.study_id = filters.value.study_id;
  const path = `/daily/${dataView.value}/export`;
  window.open(api.exportUrl(path, params), '_blank');
}

watch(dataView, reload);

onMounted(async () => {
  try {
    const meta = await api.fetchMeta();
    if (meta.data?.export_max_rows) {
      exportMaxRows.value = meta.data.export_max_rows;
    }
  } catch {
    /* keep default */
  }
  filters.value = defaultDailyDateRange(serverDate.value);
  load();
});
</script>
