<template>
  <div class="edit-table-page">
    <v-breadcrumbs :items="breadcrumbItems" class="tables-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Edit table</h1>
      </v-col>
    </v-row>

    <v-card v-if="loading" elevation="1" class="mb-4">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" />
        <p class="text-medium-emphasis mt-4">Loading table information…</p>
      </v-card-text>
    </v-card>

    <template v-else>
      <v-alert v-if="error" type="error" closable class="mb-4" @click:close="error = ''">{{ error }}</v-alert>
      <v-alert v-if="success" type="success" closable class="mb-4" @click:close="success = ''">{{ success }}</v-alert>

      <v-card elevation="1">
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
            />
          </v-window-item>
          <v-window-item value="dictionary">
            <TableDictionaryTab
              ref="dictionaryRef"
              :db-id="dbId"
              :table-id="tableId"
              @toast="showToast"
            />
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

    <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="2500" location="top">
      {{ snackbarText }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useTablesApi } from '../composables/useTablesApi';
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
const success = ref('');
const activeTab = ref('info');
const tableTitle = ref('');
const tableDescription = ref('');
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

const dataExplorerRef = ref(null);
const dictionaryRef = ref(null);
const indexesRef = ref(null);
const studiesRef = ref(null);

function showToast(text, color = 'success') {
  snackbarText.value = text;
  snackbarColor.value = color;
  snackbar.value = true;
}

function onInfoSaved(msg) {
  success.value = msg;
  loadTableMeta();
}

function onError(msg) {
  error.value = msg;
}

function onFieldsChanged() {
  dictionaryRef.value?.loadSchema?.();
}

async function loadTableMeta() {
  loading.value = true;
  error.value = '';
  try {
    const result = await api.fetchTableInfo(dbId.value, tableId.value);
    tableTitle.value = result.metadata?.title || '';
    tableDescription.value = result.metadata?.description || '';
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
  if (tab === 'indexes') indexesRef.value?.loadIndexes?.();
  if (tab === 'studies') studiesRef.value?.loadStudies?.();
});

onMounted(() => loadTableMeta());
</script>

<style scoped>
.tables-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.tables-breadcrumbs :deep(.v-breadcrumbs-item),
.tables-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
