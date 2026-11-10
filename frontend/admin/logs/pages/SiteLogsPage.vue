<template>
  <div>
    <RowCountWarning table="sitelogs" />

    <v-alert v-if="error" type="error" density="compact" class="mb-4" closable @click:close="error = ''">
      {{ error }}
    </v-alert>

    <v-card class="pa-4 mb-4" elevation="1">
      <v-row dense align="end">
        <v-col cols="12" md="6">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('search', 'Search') }}</div>
          <v-text-field
            v-model="keywords"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            @keyup.enter="applyFilters"
            @click:clear="clearFilters"
          />
        </v-col>
        <v-col cols="12" md="4">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('field', 'Field') }}</div>
          <v-select
            v-model="field"
            :items="fieldItems"
            density="compact"
            variant="outlined"
            hide-details
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-btn color="primary" block @click="applyFilters">{{ t('search', 'Search') }}</v-btn>
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
        class="logs-table"
        @update:options="load"
      >
        <template v-for="key in filterableKeys" :key="key" #[`item.${key}`]="{ item }">
          <a v-if="item[key]" href="#" class="text-primary text-decoration-none" :title="item[key]" @click.prevent="filterBy(key, item[key])">
            {{ item[key] }}
          </a>
        </template>
        <template #item.url="{ item }">
          <span class="logs-table__url" :title="item.url">{{ item.url }}</span>
        </template>
        <template #no-data>
          <div class="py-6 text-medium-emphasis">{{ t('no_records_found', 'No records found') }}</div>
        </template>
      </v-data-table-server>
    </v-card>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useLogsApi } from '../composables/useLogsApi';
import RowCountWarning from '../components/RowCountWarning.vue';

defineOptions({ name: 'SiteLogsPage' });

const { t } = useI18n();
const { searchSiteLogs } = useLogsApi();

const rows = ref([]);
const total = ref(0);
const loading = ref(false);
const error = ref('');
const keywords = ref('');
const field = ref('all');
const page = ref(1);
const pageSize = ref(50);
const sortBy = ref([{ key: 'logtime', order: 'desc' }]);

const filterableKeys = ['logtype', 'section', 'keyword', 'username', 'ip'];

const fieldItems = computed(() => [
  { title: t('all_fields', 'All fields'), value: 'all' },
  { title: t('logtype', 'Type'), value: 'logtype' },
  { title: t('section', 'Section'), value: 'section' },
  { title: t('url', 'URL'), value: 'url' },
  { title: t('keywords', 'Keywords'), value: 'keyword' },
  { title: t('username', 'Username'), value: 'username' },
  { title: t('ip_address', 'IP address'), value: 'ip' },
]);

const headers = computed(() => [
  { title: t('logtype', 'Type'), key: 'logtype', sortable: true },
  { title: t('section', 'Section'), key: 'section', sortable: true },
  { title: t('url', 'URL'), key: 'url', sortable: true },
  { title: t('keywords', 'Keywords'), key: 'keyword', sortable: true },
  { title: t('user', 'User'), key: 'username', sortable: true },
  { title: t('logtime', 'Created'), key: 'logtime', value: 'logtime_formatted', sortable: true, width: '170px' },
  { title: t('ip_address', 'IP address'), key: 'ip', sortable: true, width: '140px' },
]);

async function load() {
  loading.value = true;
  error.value = '';
  const sb = sortBy.value[0];
  const q = (keywords.value || '').trim();
  try {
    const data = await searchSiteLogs({
      limit: pageSize.value,
      offset: (page.value - 1) * pageSize.value,
      sort_by: sb?.key || 'logtime',
      sort_order: sb?.order === 'asc' ? 'asc' : 'desc',
      keywords: q || undefined,
      field: q ? field.value : undefined,
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
  applyFilters();
}

function filterBy(key, value) {
  field.value = key;
  keywords.value = String(value);
  applyFilters();
}
</script>

<style scoped>
.logs-table :deep(td) {
  max-width: 280px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.logs-table__url {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
}
</style>
