<template>
  <div class="d-flex flex-column flex-grow-1 min-height-0 gap-5">
    <section class="ds-toolbar flex-shrink-0">
      <v-sheet elevation="0" rounded="0" class="pa-2 pa-sm-3 bg-transparent">
        <div class="d-flex flex-column flex-sm-row gap-3 align-sm-center justify-space-between flex-wrap">
          <div class="d-flex flex-column flex-sm-row align-sm-center flex-grow-1 flex-wrap ds-toolbar-filters">
            <v-text-field
              :model-value="search"
              density="compact"
              variant="outlined"
              rounded="lg"
              hide-details
              clearable
              prepend-inner-icon="mdi-magnify"
              label="Search"
              placeholder="Title, name, agency…"
              class="flex-grow-0 ds-toolbar-search"
              single-line
              @update:model-value="onSearchInput"
              @click:clear="clearSearch"
            />
            <v-select
              v-model="statusFilter"
              :items="statusFilterItems"
              item-title="title"
              item-value="value"
              label="Status"
              density="compact"
              variant="outlined"
              rounded="lg"
              hide-details
              clearable
              class="flex-grow-0 ds-toolbar-status"
              @update:model-value="resetPageOnStatus"
            />
          </div>
          <v-chip v-if="!loading" variant="tonal" color="primary" size="small" class="font-weight-medium">
            {{ serverTotal }} {{ serverTotal === 1 ? 'structure' : 'structures' }}
          </v-chip>
        </div>
      </v-sheet>
    </section>

    <DataStructureImportDialog v-model="importDialogSdmx" @imported="onImported" />
    <DataStructureImportJsonDialog v-model="importDialogJson" @imported="onImported" />
    <DataStructureList
      v-model:page="page"
      v-model:items-per-page="itemsPerPage"
      :structures="structures"
      :loading="loading"
      :server-total="serverTotal"
      :has-search="hasSearch"
      @manage="goToDetail"
      @projects="goToProjects"
      @delete="confirmDelete"
      @saved="onSaved"
    />

    <v-dialog v-model="deleteDialog.show" max-width="440" persistent transition="dialog-bottom-transition">
      <v-card rounded="xl">
        <v-card-title class="d-flex align-center ga-2 pt-6 px-6">
          <v-avatar color="error" variant="tonal" size="40">
            <v-icon icon="mdi-delete-alert" color="error" />
          </v-avatar>
          <span class="text-h6 font-weight-semibold">Delete data structure?</span>
        </v-card-title>
        <v-card-text class="text-body-1 px-6 pb-2">
          Delete version
          <strong>{{ deleteDialog.row?.name }}</strong>
          ({{ deleteDialog.row?.agency }} / {{ deleteDialog.row?.version }})? Components are removed with it.
        </v-card-text>
        <v-card-actions class="px-6 pb-6 pt-2">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.saving" @click="doDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, inject, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import DataStructureList from '../components/DataStructureList.vue';
import DataStructureImportDialog from '../components/DataStructureImportDialog.vue';
import DataStructureImportJsonDialog from '../components/DataStructureImportJsonDialog.vue';
import { useDataStructuresApi } from '../composables/useDataStructuresApi';

defineOptions({ name: 'DataStructureListPage' });

const router = useRouter();
const route = useRoute();
const setMessage = inject('setMessage', () => {});

const { loading, fetchDataStructures, updateDataStructure, deleteDataStructure } = useDataStructuresApi();

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

let searchDebounceTimer;

const statusFilterItems = [
  { title: 'Draft', value: 0 },
  { title: 'Review', value: 10 },
  { title: 'Published', value: 20 },
  { title: 'Deprecated', value: 30 },
  { title: 'Archived', value: 40 },
];

const hasSearch = computed(() => String(searchDebounced.value ?? '').trim().length > 0);

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

function onImported(result) {
  if (result?.dry_run) {
    setMessage('Dry run finished — validation passed; nothing was saved.', 'success');
    return;
  }
  const n = (result?.codelists_created?.length ?? 0) + (result?.codelists_reused?.length ?? 0) + (result?.codelists_updated?.length ?? 0);
  setMessage(`Import finished. Codelists touched: ${n}.`, 'success');
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
.ds-toolbar-filters {
  row-gap: 0.75rem;
  column-gap: 1.25rem;
}
.ds-toolbar-search {
  width: 100%;
  max-width: 260px;
  min-width: 160px;
}
.ds-toolbar-status {
  width: 100%;
  max-width: 200px;
  min-width: 140px;
}
</style>
