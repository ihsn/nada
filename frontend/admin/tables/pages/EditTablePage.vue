<template>
  <div class="edit-table-page">
    <TablesBreadcrumbs :items="breadcrumbItems" />

    <TablesPageHeader :title="pageTitle" icon="mdi-database">
      <template #meta>
        <v-chip v-if="rowCount !== null" size="small" variant="tonal" color="primary" prepend-icon="mdi-database">
          {{ rowCount.toLocaleString() }} rows
        </v-chip>
      </template>
      <template #subtitle>
        <span class="text-body-2 text-medium-emphasis">
          <code class="text-caption">{{ dbId }}</code>
          <span class="mx-1">/</span>
          <code class="text-caption">{{ tableId }}</code>
        </span>
      </template>
    </TablesPageHeader>

    <v-card v-if="loading" class="admin-tables-surface mb-4" rounded="lg" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" />
        <p class="text-medium-emphasis mt-4">Loading table information…</p>
      </v-card-text>
    </v-card>

    <template v-else>
      <v-alert v-if="error" type="error" closable variant="tonal" density="compact" class="mb-4" @click:close="error = ''">
        {{ error }}
      </v-alert>

      <v-card class="tables-edit-shell admin-tables-surface" rounded="lg" elevation="1">
        <v-tabs v-model="activeTab" color="primary">
          <v-tab value="info">Table information</v-tab>
          <v-tab value="data">Data management</v-tab>
          <v-tab value="dictionary">Data dictionary</v-tab>
          <v-tab value="indexes">Indexes</v-tab>
          <v-tab value="studies">Study links</v-tab>
        </v-tabs>
        <v-divider />
        <v-window v-model="activeTab">
          <v-window-item value="info">
            <TableInfoTab
              :db-id="dbId"
              :table-id="tableId"
              :initial-title="tableTitle"
              :initial-description="tableDescription"
              @saved="onInfoSaved"
              @error="onError"
            />
          </v-window-item>
          <v-window-item value="data">
            <TableDataExplorer
              ref="dataExplorerRef"
              :db-id="dbId"
              :table-id="tableId"
              @fields-changed="onFieldsChanged"
              @stats-updated="onStatsUpdated"
            />
          </v-window-item>
          <v-window-item value="dictionary">
            <TableDictionaryTab ref="dictionaryRef" :db-id="dbId" :table-id="tableId" />
          </v-window-item>
          <v-window-item value="indexes">
            <TableIndexesTab ref="indexesRef" :db-id="dbId" :table-id="tableId" />
          </v-window-item>
          <v-window-item value="studies">
            <TableStudyLinksTab ref="studiesRef" :db-id="dbId" :table-id="tableId" />
          </v-window-item>
        </v-window>
      </v-card>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, inject } from 'vue';
import { useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useTablesApi } from '../composables/useTablesApi';
import TablesBreadcrumbs from '../components/TablesBreadcrumbs.vue';
import TablesPageHeader from '../components/TablesPageHeader.vue';
import TableInfoTab from '../components/edit/TableInfoTab.vue';
import TableDataExplorer from '../components/TableDataExplorer.vue';
import TableDictionaryTab from '../components/edit/TableDictionaryTab.vue';
import TableIndexesTab from '../components/edit/TableIndexesTab.vue';
import TableStudyLinksTab from '../components/edit/TableStudyLinksTab.vue';

const props = defineProps({
  db_id: { type: String, required: true },
  table_id: { type: String, required: true },
});

defineOptions({ name: 'EditTablePage' });

const route = useRoute();
const { siteUrl } = useAppConfig();
const api = useTablesApi();
const setMessage = inject('setMessage', () => {});

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Tables', to: { path: '/' } },
  { title: 'Edit table', disabled: true },
]);

const dbId = ref(props.db_id);
const tableId = ref(props.table_id);
const loading = ref(true);
const error = ref('');
const activeTab = ref('info');
const tableTitle = ref('');
const tableDescription = ref('');
const rowCount = ref(null);

const pageTitle = computed(() => tableTitle.value?.trim() || 'Edit table');

const dataExplorerRef = ref(null);
const dictionaryRef = ref(null);
const indexesRef = ref(null);
const studiesRef = ref(null);

function onInfoSaved(msg) {
  setMessage(msg, 'success');
  loadTableMeta();
}

function onError(msg) {
  setMessage(msg, 'error');
}

function onFieldsChanged() {
  dictionaryRef.value?.loadSchema?.();
}

function onStatsUpdated(count) {
  if (typeof count === 'number') {
    rowCount.value = count;
  }
}

async function loadTableMeta() {
  loading.value = true;
  error.value = '';
  try {
    const result = await api.fetchTableInfo(dbId.value, tableId.value);
    tableTitle.value = result.metadata?.title || '';
    tableDescription.value = result.metadata?.description || '';
    rowCount.value = typeof result.count === 'number' ? result.count : null;
  } catch (e) {
    error.value = 'Error loading table: ' + (e.response?.data?.message || e.message);
  } finally {
    loading.value = false;
  }
}

watch(
  () => route.params,
  (params) => {
    if (route.name === 'edit' && params.db_id && params.table_id) {
      dbId.value = params.db_id;
      tableId.value = params.table_id;
      activeTab.value = 'info';
      loadTableMeta();
    }
  }
);

watch(activeTab, (tab) => {
  if (tab === 'data') {
    dataExplorerRef.value?.refreshFieldMetaMap?.().then(() => dataExplorerRef.value?.loadPreviewData?.());
  }
  if (tab === 'indexes') indexesRef.value?.loadIndexes?.();
  if (tab === 'studies') studiesRef.value?.loadStudies?.();
});

onMounted(() => loadTableMeta());
</script>
