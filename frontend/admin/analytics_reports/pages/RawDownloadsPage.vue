<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>
      <v-icon start>mdi-download</v-icon>
      {{ t('analytics_raw_downloads', 'Raw downloads') }}
    </v-card-title>
    <v-card-text class="admin-analytics-card-sections">
      <RawLogsHintAlert :max-days="rawMaxDays" />
      <v-row dense>
        <v-col cols="12" sm="3">
          <FormField :label="t('analytics_from', 'From')">
            <v-text-field
              v-model="filters.date_from"
              type="date"
              density="compact"
              variant="outlined"
              hide-details
            />
          </FormField>
        </v-col>
        <v-col cols="12" sm="3">
          <FormField :label="t('analytics_to', 'To')">
            <v-text-field
              v-model="filters.date_to"
              type="date"
              density="compact"
              variant="outlined"
              hide-details
            />
          </FormField>
        </v-col>
        <v-col cols="12" sm="3">
          <FormField :label="t('analytics_file_type', 'File type')">
            <v-text-field
              v-model="filters.file_type"
              density="compact"
              variant="outlined"
              hide-details
              clearable
            />
          </FormField>
        </v-col>
        <v-col cols="12" sm="3" class="d-flex align-end ga-2 pb-1">
          <v-btn color="primary" :loading="loading" @click="reload">{{ t('analytics_filter', 'Filter') }}</v-btn>
          <v-btn variant="outlined" @click="clearFilters">{{ t('analytics_clear', 'Clear') }}</v-btn>
        </v-col>
      </v-row>
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
          {{ formatDateTime(item.ts) }}
        </template>
      </v-data-table-server>
      <PaginatedTableFooter v-model:page="page" :limit="limit" :total="total" @update:page="onPage" />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useAnalyticsApi } from '../composables/useAnalyticsApi';
import { useAnalyticsMessages } from '../composables/useAnalyticsMessages';
import { formatDateTime } from '../utils/formatters';
import { defaultRawDateRange } from '../utils/dateDefaults';
import FormField from '../components/shared/FormField.vue';
import PaginatedTableFooter from '../components/shared/PaginatedTableFooter.vue';
import RawLogsHintAlert from '../components/shared/RawLogsHintAlert.vue';

defineOptions({ name: 'RawDownloadsPage' });

const { config } = useAppConfig();
const { t } = useI18n();
const api = useAnalyticsApi();
const { notifyError } = useAnalyticsMessages();

const serverDate = computed(() => config.value?.serverDate || '');
const rawMaxDays = ref(31);

function applyDefaultDates() {
  const d = defaultRawDateRange(serverDate.value);
  filters.date_from = d.date_from;
  filters.date_to = d.date_to;
}

const filters = reactive({ date_from: '', date_to: '', file_type: '' });
const loading = ref(false);
const rows = ref([]);
const total = ref(0);
const page = ref(1);
const limit = 50;

const headers = [
  { title: t('analytics_time', 'Time'), key: 'ts' },
  { title: t('analytics_study_id', 'Study ID'), key: 'study_id' },
  { title: t('analytics_file', 'File'), key: 'file_name' },
  { title: t('analytics_type', 'Type'), key: 'file_type' },
];

async function load() {
  if (!filters.date_from || !filters.date_to) {
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
      date_from: filters.date_from,
      date_to: filters.date_to,
    };
    if (filters.file_type) params.file_type = filters.file_type;
    const res = await api.fetchRawDownloads(params);
    rows.value = res.data || [];
    total.value = res.total || 0;
  } catch (e) {
    notifyError(e.message || t('analytics_load_downloads_error', 'Failed to load downloads'));
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

function clearFilters() {
  applyDefaultDates();
  filters.file_type = '';
  reload();
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
  applyDefaultDates();
  load();
});
</script>
