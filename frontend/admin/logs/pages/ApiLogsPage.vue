<template>
  <div>
    <RowCountWarning table="api_logs" />

    <v-alert v-if="error" type="error" density="compact" class="mb-4" closable @click:close="error = ''">
      {{ error }}
    </v-alert>

    <v-card class="pa-4 mb-4" elevation="1">
      <v-row dense align="end">
        <v-col cols="12" md="4">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('search', 'Search') }}</div>
          <v-text-field
            v-model="keywords"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            @keyup.enter="applyFilters"
          />
        </v-col>
        <v-col cols="12" md="2">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('field', 'Field') }}</div>
          <v-select v-model="field" :items="fieldItems" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="6" md="2">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">Date from</div>
          <v-text-field v-model="dateFrom" type="date" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="6" md="2">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">Date to</div>
          <v-text-field v-model="dateTo" type="date" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="12" md="2" class="d-flex ga-2">
          <v-btn color="primary" class="flex-grow-1" @click="applyFilters">{{ t('search', 'Search') }}</v-btn>
          <v-btn variant="text" @click="clearFilters">{{ t('reset', 'Reset') }}</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="1">
      <v-data-table-server
        v-model:page="page"
        v-model:items-per-page="pageSize"
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="rows"
        :items-length="total"
        :loading="loading"
        :items-per-page-options="[25, 50, 100, 200]"
        item-value="id"
        density="compact"
        class="api-logs-table"
        @update:options="load"
      >
        <template #item.method="{ item }">
          <v-chip size="x-small" label :color="methodColor(item.method)" variant="flat">
            {{ String(item.method || '').toUpperCase() }}
          </v-chip>
        </template>
        <template #item.uri="{ item }">
          <span class="api-logs-table__mono" :title="item.uri">{{ item.uri }}</span>
        </template>
        <template #item.response_code="{ item }">
          <v-chip size="x-small" label :color="codeColor(item.response_code)" variant="tonal">
            {{ item.response_code }}
          </v-chip>
        </template>
        <template #item.rtime="{ item }">
          <span v-if="item.rtime_formatted">{{ item.rtime_formatted }}</span>
          <span v-else class="text-medium-emphasis">–</span>
        </template>
        <template #item.authorized="{ item }">
          <v-icon
            size="small"
            :color="item.authorized == '1' ? 'success' : 'error'"
            :icon="item.authorized == '1' ? 'mdi-check-circle' : 'mdi-close-circle'"
          />
        </template>
        <template #item.user_id="{ item }">
          <span v-if="item.user_email" :title="`User #${item.user_id}`">{{ item.user_email }}</span>
          <span v-else-if="item.user_id">#{{ item.user_id }}</span>
          <span v-else class="text-medium-emphasis">–</span>
        </template>
        <template #item.api_key="{ item }">
          <span v-if="item.api_key" class="api-logs-table__mono">{{ String(item.api_key).slice(0, 10) }}…</span>
        </template>
        <template #no-data>
          <div class="py-6 text-medium-emphasis">{{ t('no_records_found', 'No records found') }}</div>
        </template>
      </v-data-table-server>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useLogsApi } from '../composables/useLogsApi';
import { dateToUnix } from '../utils/format';
import RowCountWarning from '../components/RowCountWarning.vue';

defineOptions({ name: 'ApiLogsPage' });

const { t } = useI18n();
const { searchApiLogs } = useLogsApi();

const rows = ref([]);
const total = ref(0);
const loading = ref(false);
const error = ref('');
const keywords = ref('');
const field = ref('all');
const dateFrom = ref('');
const dateTo = ref('');
const page = ref(1);
const pageSize = ref(50);
const sortBy = ref([{ key: 'time', order: 'desc' }]);

const fieldItems = [
  { title: 'All fields', value: 'all' },
  { title: 'URI (starts with)', value: 'uri' },
  { title: 'Method', value: 'method' },
  { title: 'API key (starts with)', value: 'api_key' },
  { title: 'IP address (starts with)', value: 'ip_address' },
  { title: 'Authorized (0/1)', value: 'authorized' },
  { title: 'Response code', value: 'response_code' },
  { title: 'User ID', value: 'user_id' },
];

const headers = [
  { title: 'ID', key: 'id', sortable: true, width: '80px' },
  { title: 'Time', key: 'time', value: 'time_formatted', sortable: true, width: '170px' },
  { title: 'Method', key: 'method', sortable: true, width: '90px' },
  { title: 'URI', key: 'uri', sortable: true },
  { title: 'Code', key: 'response_code', sortable: true, width: '80px' },
  { title: 'Response time', key: 'rtime', sortable: true, width: '120px' },
  { title: 'Auth', key: 'authorized', sortable: true, width: '70px' },
  { title: 'IP address', key: 'ip_address', sortable: true, width: '140px' },
  { title: 'User', key: 'user_id', sortable: true },
  { title: 'API key', key: 'api_key', sortable: false, width: '130px' },
];

const METHOD_COLORS = { GET: 'primary', POST: 'success', PUT: 'warning', DELETE: 'error', PATCH: 'info' };

function methodColor(method) {
  return METHOD_COLORS[String(method || '').toUpperCase()] || 'grey';
}

function codeColor(code) {
  const c = Number(code);
  if (c >= 200 && c < 300) return 'success';
  if (c >= 300 && c < 400) return 'warning';
  if (c >= 400) return 'error';
  return 'grey';
}

async function load() {
  loading.value = true;
  error.value = '';
  const sb = sortBy.value[0];
  const q = (keywords.value || '').trim();
  try {
    const data = await searchApiLogs({
      limit: pageSize.value,
      offset: (page.value - 1) * pageSize.value,
      sort_by: sb?.key || 'time',
      sort_order: sb?.order === 'asc' ? 'asc' : 'desc',
      keywords: q || undefined,
      field: q ? field.value : undefined,
      time_from: dateToUnix(dateFrom.value),
      time_to: dateToUnix(dateTo.value, true),
    });
    rows.value = data.data || [];
    total.value = data.pagination?.total_rows ?? 0;
  } catch (e) {
    rows.value = [];
    total.value = 0;
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  if (page.value !== 1) {
    page.value = 1;
  } else {
    load();
  }
}

function clearFilters() {
  keywords.value = '';
  field.value = 'all';
  dateFrom.value = '';
  dateTo.value = '';
  applyFilters();
}
</script>

<style scoped>
.api-logs-table :deep(td) {
  max-width: 320px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.api-logs-table__mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
}
</style>
