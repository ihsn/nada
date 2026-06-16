<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="ds-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="7">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Data structures</h1>
      </v-col>
      <v-col cols="12" md="5" class="d-flex justify-end ga-2">
        <v-btn variant="tonal" prepend-icon="mdi-upload" @click="openImportSdmxDialog">Import SDMX XML</v-btn>
        <v-btn variant="tonal" prepend-icon="mdi-code-json" @click="openImportJsonDialog">Import JSON</v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="goCreate">Add data structure</v-btn>
      </v-col>
    </v-row>

    <v-card class="pa-4" elevation="1" style="margin-bottom: 24px;">
      <v-row dense align="center">
        <v-col cols="12" md="5">
          <v-text-field
            :model-value="search"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            placeholder="Title, name, agency…"
            @update:model-value="onSearchInput"
            @click:clear="clearSearch"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="statusFilter"
            :items="statusFilterItems"
            item-title="title"
            item-value="value"
            placeholder="Status"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            @update:model-value="resetPageOnStatus"
          />
        </v-col>
        <v-col v-if="!loading" cols="auto" class="ml-auto">
          <v-chip variant="tonal" color="primary" size="small" class="font-weight-medium">
            {{ serverTotal }} {{ serverTotal === 1 ? 'structure' : 'structures' }}
          </v-chip>
        </v-col>
      </v-row>
    </v-card>

    <DataStructureImportDialog v-model="importDialogSdmx" @imported="onImported" />
    <DataStructureImportJsonDialog v-model="importDialogJson" @imported="onImported" />

    <DataStructureList
      ref="structureListRef"
      v-model:page="page"
      v-model:items-per-page="itemsPerPage"
      :structures="structures"
      :loading="loading"
      :server-total="serverTotal"
      :has-search="hasSearch"
      @manage="goToDetail"
      @projects="goToProjects"
      @delete="confirmDelete"
      @batch-delete="confirmBatchDelete"
      @saved="onSaved"
    />

    <v-dialog v-model="deleteDialog.show" max-width="440" persistent>
      <v-card>
        <v-card-title class="text-h6 pa-4">Delete data structure?</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          Delete version <strong>{{ deleteDialog.row?.name }}</strong>
          ({{ deleteDialog.row?.agency }} / {{ deleteDialog.row?.version }})?
          Components are removed with it.
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.saving" @click="doDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="batchDeleteDialog.show" max-width="480" persistent>
      <v-card>
        <v-card-title class="text-h6 pa-4">Delete selected data structures?</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          This will delete <strong>{{ batchDeleteDialog.rows?.length ?? 0 }}</strong>
          {{ (batchDeleteDialog.rows?.length ?? 0) === 1 ? 'structure' : 'structures' }}
          (components are removed with each row). Published or archived definitions, or structures
          linked to projects, cannot be removed.
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="batchDeleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="batchDeleteDialog.saving" @click="doBatchDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, inject, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import DataStructureList from '../components/DataStructureList.vue';
import DataStructureImportDialog from '../components/DataStructureImportDialog.vue';
import DataStructureImportJsonDialog from '../components/DataStructureImportJsonDialog.vue';
import { useDataStructuresApi } from '../composables/useDataStructuresApi';

defineOptions({ name: 'DataStructureListPage' });

const router = useRouter();
const route = useRoute();
const { siteUrl } = useAppConfig();
const setMessage = inject('setMessage', () => {});

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Data structures', disabled: true },
]);

const { loading, fetchDataStructures, updateDataStructure, deleteDataStructure, deleteDataStructuresBatch } =
  useDataStructuresApi();

const structures = ref([]);
const serverTotal = ref(0);
const page = ref(1);
const itemsPerPage = ref(25);
const search = ref('');
const searchDebounced = ref('');
const statusFilter = ref(null);
const importDialogSdmx = ref(false);
const importDialogJson = ref(false);
const deleteDialog = reactive({ show: false, saving: false, row: null });
const batchDeleteDialog = reactive({ show: false, saving: false, rows: [] });
const structureListRef = ref(null);

let searchDebounceTimer;

const statusFilterItems = [
  { title: 'Draft', value: 0 },
  { title: 'Review', value: 10 },
  { title: 'Published', value: 20 },
  { title: 'Deprecated', value: 30 },
  { title: 'Archived', value: 40 },
];

const hasSearch = computed(() => String(searchDebounced.value ?? '').trim().length > 0);

function openImportSdmxDialog() { importDialogSdmx.value = true; }
function openImportJsonDialog() { importDialogJson.value = true; }
function goCreate() { router.push({ name: 'data-structure-create' }); }

function onSearchInput(val) {
  search.value = val === null || val === undefined ? '' : String(val);
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    searchDebounced.value = search.value.trim();
    page.value = 1;
  }, 400);
}

function clearSearch() {
  clearTimeout(searchDebounceTimer);
  search.value = '';
  searchDebounced.value = '';
  page.value = 1;
  loadList();
}

function resetPageOnStatus() {
  page.value = 1;
}

function importWarningsAsList(warnings) {
  return (Array.isArray(warnings) ? warnings : [])
    .filter((w) => w && typeof w.message === 'string' && w.message.trim() !== '')
    .map((w) => ({ path: w.codelist_id ? `codelist #${w.codelist_id}` : '', message: w.message }));
}

function onImported(result) {
  const warnings = importWarningsAsList(result?.warnings);
  const n = (result?.codelists_created?.length ?? 0) + (result?.codelists_reused?.length ?? 0) + (result?.codelists_updated?.length ?? 0);
  setMessage(`Import finished. Codelists touched: ${n}.`, warnings.length ? 'warning' : 'success', { errors: warnings });
  const id = result?.data_structure?.id;
  if (id != null) {
    router.push({ name: 'data-structure-detail', params: { id: String(id) } });
  } else {
    loadList();
  }
}

async function loadList() {
  try {
    const r = await fetchDataStructures({
      page: page.value,
      per_page: itemsPerPage.value,
      search: searchDebounced.value,
      status: statusFilter.value,
    });
    structures.value = r.structures ?? [];
    serverTotal.value = r.total ?? 0;
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Failed to load data structures', 'error');
  }
}

watch([page, itemsPerPage, searchDebounced, statusFilter], loadList, { immediate: true });

function goToDetail(row) {
  router.push({ name: 'data-structure-detail', params: { id: String(row.id) } });
}

function goToProjects(row) {
  router.push({ name: 'data-structure-projects', params: { id: String(row.id) } });
}

function confirmDelete(row) {
  deleteDialog.row = row;
  deleteDialog.show = true;
}

async function doDelete() {
  if (!deleteDialog.row) return;
  deleteDialog.saving = true;
  try {
    await deleteDataStructure(deleteDialog.row.id);
    setMessage('Data structure deleted.', 'success');
    deleteDialog.show = false;
    await loadList();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Delete failed', 'error');
  } finally {
    deleteDialog.saving = false;
  }
}

function confirmBatchDelete(rows) {
  const list = Array.isArray(rows) ? rows : [];
  if (!list.length) return;
  batchDeleteDialog.rows = list;
  batchDeleteDialog.show = true;
}

async function doBatchDelete() {
  const rows = batchDeleteDialog.rows;
  if (!Array.isArray(rows) || !rows.length) return;
  batchDeleteDialog.saving = true;
  try {
    const result = await deleteDataStructuresBatch(rows.map((r) => r.id));
    const deletedCount = Number(result?.deleted_count) || 0;
    const failed = Array.isArray(result?.failed) ? result.failed : [];
    const failedCount = failed.length;
    if (deletedCount > 0 && failedCount === 0) {
      setMessage(
        deletedCount === 1 ? 'Data structure deleted.' : `${deletedCount} data structures deleted.`,
        'success'
      );
    } else if (deletedCount > 0 && failedCount > 0) {
      const sample = failed.slice(0, 3).map((f) => `#${f.id}: ${f.message}`).join('; ');
      setMessage(`Deleted ${deletedCount}; ${failedCount} failed${sample ? `. ${sample}` : ''}`, 'warning');
    } else if (failedCount > 0) {
      const sample = failed.slice(0, 3).map((f) => `#${f.id}: ${f.message}`).join('; ');
      setMessage(`Could not delete selected structures${sample ? `. ${sample}` : ''}`, 'error');
    } else {
      setMessage('Nothing was deleted.', 'info');
    }
    batchDeleteDialog.show = false;
    structureListRef.value?.clearSelection?.();
    await loadList();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Batch delete failed', 'error');
  } finally {
    batchDeleteDialog.saving = false;
  }
}

async function onSaved(evt) {
  if (!evt.isEdit || evt.structureId == null) return;
  try {
    await updateDataStructure(evt.structureId, evt.payload);
    setMessage('Data structure updated.', 'success');
    await loadList();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Save failed', 'error');
  }
}

watch(
  () => route.query.openImport,
  (v) => {
    if (v == null || v === '') return;
    importDialogSdmx.value = true;
    const q = { ...route.query };
    delete q.openImport;
    router.replace({ query: q });
  },
  { immediate: true }
);

watch(
  () => route.query.openImportJson,
  (v) => {
    if (v == null || v === '') return;
    importDialogJson.value = true;
    const q = { ...route.query };
    delete q.openImportJson;
    router.replace({ query: q });
  },
  { immediate: true }
);
</script>

<style scoped>
.ds-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.ds-breadcrumbs :deep(.v-breadcrumbs-item),
.ds-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
