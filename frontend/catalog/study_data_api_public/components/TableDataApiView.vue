<template>
  <div>
    <v-progress-linear v-if="infoLoading" indeterminate color="primary" height="3" class="mb-6" />

    <v-alert v-if="infoError" type="error" variant="tonal" rounded="lg" class="mb-6">
      {{ ui.errorPrefix }} {{ infoError }}
    </v-alert>

    <template v-else-if="!infoLoading">
      <!-- table header -->
      <header class="tda-section tda-header">
        <div v-if="showTitle" class="text-body-1 font-weight-medium mb-3">{{ metadata.title || `${dbId}/${tableId}` }}</div>
        <div class="d-flex flex-wrap ga-2">
          <v-chip v-if="showTitle && rowCount !== null" size="small" variant="tonal" prepend-icon="mdi-table-row">
            {{ ui.rowsLabel }}: {{ rowCount.toLocaleString() }}
          </v-chip>
          <v-chip v-if="dictionary.length" size="small" variant="tonal" prepend-icon="mdi-table-column">
            {{ ui.columnsLabel }}: {{ dictionary.length.toLocaleString() }}
          </v-chip>
          <v-chip v-if="metadata.updated_at" size="small" variant="tonal" prepend-icon="mdi-update">
            {{ ui.updatedLabel }}: {{ formatDate(metadata.updated_at) }}
          </v-chip>
        </div>
        <p v-if="metadata.description" class="text-body-2 mt-3 mb-0 tda-description">{{ metadata.description }}</p>

        <!-- full data: the CSV as uploaded -->
        <div v-if="storedCsv" class="d-flex flex-wrap align-center ga-3 mt-4">
          <v-btn
            :href="storedCsv.download_url || api.urls.download"
            color="primary"
            variant="flat"
            class="text-none"
            prepend-icon="mdi-download"
          >
            {{ ui.downloadCsv }}
          </v-btn>
          <span class="text-caption text-medium-emphasis">
            {{ csvMeta }}
          </span>
        </div>
      </header>

      <!-- study-level downloads (same for every table of the study) -->
      <slot name="downloads" />

      <TableDataExplorer
        :api="api"
        :dictionary="dictionary"
        :filterable-fields="filterableFields"
        :ui="ui"
        class="tda-section"
        @loaded="onDataLoaded"
      />

      <TableDataDictionary
        v-if="dictionary.length"
        :dictionary="dictionary"
        :json-url="api.urls.dictionary"
        :ui="ui"
        class="tda-section"
      />

      <TableApiUsage
        :urls="api.urls"
        :dictionary="dictionary"
        :bulk-downloads-url="bulkDownloadsUrl"
        :csv-download="!!storedCsv"
        :ui="ui"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import TableDataExplorer from './TableDataExplorer.vue';
import TableDataDictionary from './TableDataDictionary.vue';
import TableApiUsage from './TableApiUsage.vue';
import { useTableDataApi } from '../composables/useTableDataApi.js';
import { fmt } from '../utils/tableQuery.js';

defineOptions({ name: 'TableDataApiView' });

const props = defineProps({
  apiBaseUrl: { type: String, required: true },
  dbId: { type: String, required: true },
  tableId: { type: String, required: true },
  bulkDownloadsUrl: { type: String, default: '' },
  /** false when the table dropdown already shows the title and row count */
  showTitle: { type: Boolean, default: true },
  ui: { type: Object, required: true },
});

// one view per table: the parent re-creates it (by key) when the table changes
const api = useTableDataApi({ apiBaseUrl: props.apiBaseUrl, dbId: props.dbId, tableId: props.tableId });

const infoLoading = ref(true);
const infoError = ref('');
const metadata = ref({});
const dictionary = ref([]);
const rowCount = ref(null);
const filterableFields = ref([]);
const storedCsv = ref(null);

function formatBytes(bytes) {
  const n = Number(bytes);
  if (!Number.isFinite(n) || n <= 0) return '';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.min(Math.floor(Math.log(n) / Math.log(1024)), units.length - 1);
  return `${(n / 1024 ** i).toLocaleString(undefined, { maximumFractionDigits: i ? 1 : 0 })} ${units[i]}`;
}

/** "12.4 MB · uploaded 26/10/2025" (parts left out when unknown) */
const csvMeta = computed(() => {
  if (!storedCsv.value) return '';
  const parts = [];
  const size = formatBytes(storedCsv.value.size_bytes);
  if (size) parts.push(size);
  if (storedCsv.value.uploaded_at) parts.push(fmt(props.ui.downloadCsvUploaded, { date: formatDate(storedCsv.value.uploaded_at) }));
  return parts.join(' · ');
});

function formatDate(value) {
  const d = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? String(value) : d.toLocaleDateString();
}

function onDataLoaded({ total }) {
  if (typeof total === 'number') rowCount.value = total;
}

onMounted(async () => {
  try {
    const info = await api.fetchInfo();
    metadata.value = info.metadata;
    dictionary.value = info.dictionary;
    rowCount.value = info.count;
    filterableFields.value = info.filterableFields;
    storedCsv.value = info.storedCsv;
  } catch (e) {
    infoError.value = e?.message || String(e);
  } finally {
    infoLoading.value = false;
  }
});
</script>

<style scoped>
.tda-description {
  white-space: pre-line;
  max-width: 900px;
}
</style>
