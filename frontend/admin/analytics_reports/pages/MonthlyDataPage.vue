<template>
  <div class="admin-analytics-page-stack">
    <MonthlyTrafficChartCard />
    <v-card class="admin-analytics-card" rounded="lg" elevation="1">
      <v-card-title class="d-flex flex-wrap align-center gap-2">
        <v-icon start>mdi-calendar-month</v-icon>
        {{ t('analytics_monthly_aggregates', 'Monthly aggregates') }}
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
        <v-row dense>
          <v-col cols="12" sm="3">
            <FormField :label="t('analytics_year', 'Year')">
              <v-text-field
                v-model.number="filters.year"
                type="number"
                density="compact"
                variant="outlined"
                hide-details
                clearable
              />
            </FormField>
          </v-col>
          <v-col cols="12" sm="3">
            <FormField :label="t('analytics_month', 'Month')">
              <v-select
                v-model="filters.month"
                :items="monthOptions"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                hide-details
                clearable
              />
            </FormField>
          </v-col>
          <v-col cols="12" sm="3">
            <FormField :label="t('analytics_study_filter', 'Study (IDNO or ID)')">
              <v-text-field
                v-model="filters.study_id"
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
          :headers="headers"
          :items="rows"
          :loading="loading"
          :items-length="total"
          :items-per-page="limit"
          :page="page"
          hide-default-footer
          @update:page="onPage"
        >
          <template #item.finalized="{ item }">
            <v-chip size="small" :color="item.finalized ? 'success' : 'warning'" variant="tonal">
              {{ item.finalized ? t('analytics_finalized', 'Finalized') : t('analytics_open', 'Open') }}
            </v-chip>
          </template>
        </v-data-table-server>
        <PaginatedTableFooter v-model:page="page" :limit="limit" :total="total" @update:page="onPage" />
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useAnalyticsApi } from '../composables/useAnalyticsApi';
import { useAnalyticsMessages } from '../composables/useAnalyticsMessages';
import { MONTH_OPTIONS } from '../utils/constants';
import { defaultMonthlyFilters } from '../utils/dateDefaults';
import MonthlyTrafficChartCard from '../components/monthly/MonthlyTrafficChartCard.vue';
import FormField from '../components/shared/FormField.vue';
import PaginatedTableFooter from '../components/shared/PaginatedTableFooter.vue';
import StudiesFilesToggle from '../components/shared/StudiesFilesToggle.vue';
import ExportFormatMenu from '../components/shared/ExportFormatMenu.vue';

defineOptions({ name: 'MonthlyDataPage' });

const { config } = useAppConfig();
const { t } = useI18n();
const api = useAnalyticsApi();
const { notifyError } = useAnalyticsMessages();

const serverDate = computed(() => config.value?.serverDate || '');
const exportMaxRows = ref(50000);

const monthOptions = MONTH_OPTIONS;
const dataView = ref('studies');
const filters = reactive({ ...defaultMonthlyFilters(serverDate.value) });
const loading = ref(false);
const rows = ref([]);
const total = ref(0);
const page = ref(1);
const limit = 50;

const studyHeaders = [
  { title: t('analytics_id', 'ID'), key: 'study_id' },
  { title: t('analytics_title_column', 'Title'), key: 'title' },
  { title: t('analytics_year', 'Year'), key: 'year' },
  { title: t('analytics_month', 'Month'), key: 'month' },
  { title: t('analytics_pageviews', 'Pageviews'), key: 'pageviews' },
  { title: t('analytics_unique_visitors', 'Unique visitors'), key: 'unique_visitors' },
  { title: t('analytics_downloads', 'Downloads'), key: 'downloads' },
  { title: t('analytics_status', 'Status'), key: 'finalized' },
];

const fileHeaders = [
  { title: t('analytics_study_id', 'Study ID'), key: 'study_id' },
  { title: t('analytics_title_column', 'Title'), key: 'title' },
  { title: t('analytics_year', 'Year'), key: 'year' },
  { title: t('analytics_month', 'Month'), key: 'month' },
  { title: t('analytics_file_name', 'File name'), key: 'file_name' },
  { title: t('analytics_downloads', 'Downloads'), key: 'downloads' },
  { title: t('analytics_status', 'Status'), key: 'finalized' },
];

const headers = computed(() => (dataView.value === 'studies' ? studyHeaders : fileHeaders));

async function load() {
  loading.value = true;
  try {
    const params = {
      limit,
      offset: (page.value - 1) * limit,
    };
    if (filters.year) params.year = filters.year;
    if (filters.month) params.month = filters.month;
    if (filters.study_id) params.study_id = filters.study_id;

    const fetcher = dataView.value === 'studies' ? api.fetchMonthlyStudies : api.fetchMonthlyFiles;
    const res = await fetcher(params);
    rows.value = res.data || [];
    total.value = res.total || 0;
  } catch (e) {
    notifyError(e.message || t('analytics_load_monthly_error', 'Failed to load monthly data'));
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

function applyDefaultFilters() {
  const d = defaultMonthlyFilters(serverDate.value);
  filters.year = d.year;
  filters.month = d.month;
  filters.study_id = d.study_id;
}

function clearFilters() {
  applyDefaultFilters();
  reload();
}

function onExport(format) {
  const params = { format };
  if (filters.year) params.year = filters.year;
  if (filters.month) params.month = filters.month;
  if (filters.study_id) params.study_id = filters.study_id;
  window.open(api.exportUrl(`/monthly/${dataView.value}/export`, params), '_blank');
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
  applyDefaultFilters();
  load();
});
</script>
