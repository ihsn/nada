<template>
  <v-app class="tda-app">
    <v-main>
      <v-container fluid class="tda-container">
        <h1 class="tda-h1 mb-4">{{ ui.pageHeading }}</h1>

        <!-- several tables linked to the study: choose one (kept in ?table=db/table) -->
        <div v-if="tables.length > 1" class="tda-table-selector">
          <v-select
            :model-value="selected?.key"
            :items="tableItems"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            class="tda-table-select"
            @update:model-value="selectTable"
          />
        </div>

        <v-alert v-if="!selected" type="error" variant="tonal" rounded="lg">
          {{ ui.errorPrefix }} {{ ui.noTable }}
        </v-alert>

        <TableDataApiView
          v-else
          :key="selected.key"
          :api-base-url="apiBaseUrl"
          :db-id="selected.dbId"
          :table-id="selected.tableId"
          :bulk-downloads-url="bulkFiles.length ? bulkDownloadsApiUrl : ''"
          :ui="ui"
          :show-title="tables.length <= 1"
        >
          <template #downloads>
            <div v-if="bulkLoading || bulkFiles.length" class="tda-section">
              <StudyRegistryBulkDownloads
                :title="ui.bulkDownloadsHeading"
                :loading="bulkLoading"
                :files="bulkFiles"
                :col-file="ui.bulkFileCol"
                :col-date="ui.bulkDateCol"
                :col-actions="ui.bulkActionsCol"
                :download-label="ui.download"
                :link-label="ui.link"
                :loading-label="ui.loading"
              />
            </div>
          </template>
        </TableDataApiView>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import StudyRegistryBulkDownloads from '@/catalog/study_indicator_data_public/components/StudyRegistryBulkDownloads.vue';
import TableDataApiView from './components/TableDataApiView.vue';
import { useTableDataApi } from './composables/useTableDataApi.js';
import { DEFAULT_UI } from './uiDefaults.js';

defineOptions({ name: 'CatalogStudyDataApiApp' });

const { config } = useAppConfig();
const ui = computed(() => ({ ...DEFAULT_UI, ...(config.value?.tableDataApiUi || {}) }));
const apiBaseUrl = computed(() => String(config.value?.apiBaseUrl || ''));

/** Tables linked to the study, in the order the server sends them. */
const tables = computed(() => {
  const list = Array.isArray(config.value?.tables) ? config.value.tables : [];
  return list
    .filter((t) => t && t.db_id && t.table_id)
    .map((t) => ({ dbId: String(t.db_id), tableId: String(t.table_id), key: `${t.db_id}/${t.table_id}`.toLowerCase() }));
});

function tableFromUrl() {
  try {
    return String(new URLSearchParams(window.location.search).get('table') || '').toLowerCase();
  } catch {
    return '';
  }
}

const selectedKey = ref(tableFromUrl());
const selected = computed(
  () => tables.value.find((t) => t.key === selectedKey.value) || tables.value[0] || null
);

function selectTable(key) {
  if (!key || key === selected.value?.key) return;
  selectedKey.value = key;
  try {
    const url = new URL(window.location.href);
    url.searchParams.set('table', key);
    url.searchParams.delete('view'); // explorer state belongs to the previous table
    window.history.pushState(window.history.state, '', url.toString());
  } catch {
    // URL state is a convenience only
  }
}

// back/forward between tables
onMounted(() => {
  window.addEventListener('popstate', () => {
    selectedKey.value = tableFromUrl();
  });
});

// titles and row counts for the selector
const summaries = reactive({});
function tableTitle(t) {
  return summaries[t.key]?.title || t.key;
}
const tableItems = computed(() =>
  tables.value.map((t) => {
    const count = summaries[t.key]?.count;
    return { value: t.key, title: count != null ? `${tableTitle(t)} (${count.toLocaleString()})` : tableTitle(t) };
  })
);

// study-level bulk downloads (same for every table)
const bulkLoading = ref(false);
const bulkFiles = ref([]);
const bulkDownloadsApiUrl = computed(() => {
  const idno = config.value?.studyIdno;
  if (!idno) return '';
  return `${String(config.value?.siteUrl || '').replace(/\/$/, '')}/api/downloads/${encodeURIComponent(idno)}/files?type=data`;
});

onMounted(async () => {
  if (tables.value.length > 1) {
    await Promise.all(
      tables.value.map(async (t) => {
        try {
          const api = useTableDataApi({ apiBaseUrl: apiBaseUrl.value, dbId: t.dbId, tableId: t.tableId });
          summaries[t.key] = await api.fetchSummary();
        } catch {
          summaries[t.key] = { title: '', count: null };
        }
      })
    );
  }

  if (config.value?.studyIdno && selected.value) {
    bulkLoading.value = true;
    const api = useTableDataApi({ apiBaseUrl: apiBaseUrl.value, dbId: selected.value.dbId, tableId: selected.value.tableId });
    bulkFiles.value = await api.fetchBulkDownloads(config.value.siteUrl, config.value.studyIdno);
    bulkLoading.value = false;
  }
});
</script>

<style>
/*
 * Typography and spacing for the page. Not scoped and prefixed with .tda-app so it wins over
 * the site theme, which styles h1-h3 globally (large sizes, margins).
 */
.tda-app h1.tda-h1 {
  font-size: 1.5rem !important;
  font-weight: 600 !important;
  line-height: 1.3 !important;
  margin: 0 0 16px !important;
}
.tda-app h2.tda-h2 {
  font-size: 1.25rem !important;
  font-weight: 600 !important;
  line-height: 1.35 !important;
  margin: 0 !important;
}
.tda-app h3.tda-h3 {
  font-size: 1rem !important;
  font-weight: 600 !important;
  line-height: 1.4 !important;
  margin: 24px 0 8px !important;
}
.tda-app .tda-section {
  margin-bottom: 40px;
}
.tda-app .tda-section-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}
</style>

<style scoped>
.tda-app {
  background: transparent !important;
}
.tda-app :deep(.v-application__wrap) {
  min-height: 0;
}
.tda-container {
  padding-left: 0;
  padding-right: 0;
}
.tda-table-selector {
  margin-bottom: 24px;
}
.tda-table-select {
  max-width: 520px;
}
</style>
