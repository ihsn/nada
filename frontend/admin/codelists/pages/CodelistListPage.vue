<template>
  <div class="d-flex flex-column flex-grow-1 min-height-0 gap-5">
    <section class="flex-shrink-0">
      <v-sheet elevation="0" rounded="0" class="pa-2 pa-sm-3 bg-transparent">
        <div class="d-flex flex-column flex-sm-row gap-3 align-sm-center justify-space-between flex-wrap">
          <div class="cl-filters-row d-flex flex-column flex-sm-row align-sm-center flex-grow-1 flex-wrap">
            <v-text-field
              :model-value="search"
              density="compact"
              variant="outlined"
              rounded="lg"
              hide-details
              clearable
              prepend-inner-icon="mdi-magnify"
              label="Search"
              placeholder="Name, idno, agency, version…"
              class="flex-grow-1"
              style="max-width: 420px"
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
              style="max-width: 200px"
              @update:model-value="resetPageOnStatus"
            />
          </div>
          <v-chip v-if="!loading" variant="tonal" color="primary" size="small" class="font-weight-medium flex-shrink-0">
            {{ serverTotal }} {{ serverTotal === 1 ? 'codelist' : 'codelists' }}
          </v-chip>
        </div>
      </v-sheet>
    </section>

    <CodelistList
      ref="listRef"
      v-model:page="page"
      v-model:items-per-page="itemsPerPage"
      :codelists="codelists"
      :loading="loading"
      :server-total="serverTotal"
      :has-search="hasSearch"
      @manage="goToDetail"
      @delete="confirmDeleteCodelist"
      @saved="onCodelistSaved"
    />

    <v-dialog v-model="deleteDialog.show" max-width="440" persistent>
      <v-card rounded="xl">
        <v-card-title>Delete codelist version?</v-card-title>
        <v-card-text>
          Delete
          <strong>{{ deleteDialog.codelist?.name }}</strong>
          ({{ deleteDialog.codelist?.agency }} / {{ deleteDialog.codelist?.version }})? Items, groups, and
          translations for this version only will be removed.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" :loading="deleteDialog.saving" @click="doDeleteCodelist">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, inject, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import CodelistList from '../components/CodelistList.vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistListPage' });

const route = useRoute();
const router = useRouter();
const setMessage = inject('setMessage', () => {});

const {
  loading,
  fetchCodelists,
  createCodelist,
  updateCodelist,
  deleteCodelist,
} = useCodelistsApi();

const listRef = ref(null);
const codelists = ref([]);
const serverTotal = ref(0);
const page = ref(1);
const itemsPerPage = ref(25);
const search = ref('');
const searchDebounced = ref('');
const statusFilter = ref(null);
const deleteDialog = reactive({ show: false, saving: false, codelist: null });

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

function openCreateDialog() {
  listRef.value?.openCreate?.();
}

watch(
  () => route.query.openCreate,
  (ts) => {
    if (ts == null || ts === '') return;
    openCreateDialog();
    const q = { ...route.query };
    delete q.openCreate;
    router.replace({ name: 'codelists', query: q });
  }
);

async function loadList() {
  try {
    const r = await fetchCodelists({
      page: page.value,
      per_page: itemsPerPage.value,
      search: searchDebounced.value,
      status: statusFilter.value,
    });
    const total = r.total ?? 0;
    const rows = r.codelists ?? [];
    const maxPage = Math.max(1, Math.ceil(total / itemsPerPage.value));
    if (rows.length === 0 && total > 0 && page.value > maxPage) {
      page.value = maxPage;
      return;
    }
    codelists.value = rows;
    serverTotal.value = total;
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Failed to load codelists', 'error');
  }
}

watch([page, itemsPerPage, searchDebounced, statusFilter], loadList, { immediate: true });

function goToDetail(codelist) {
  router.push({ name: 'codelist-detail', params: { id: String(codelist.id) } });
}

async function onCodelistSaved(payload) {
  try {
    if (payload.isEdit && payload.codelistId != null) {
      await updateCodelist(payload.codelistId, {
        idno: payload.idno,
        description: payload.description,
      });
      setMessage('Codelist updated.', 'success');
    } else if (!payload.isEdit) {
      await createCodelist({
        name: payload.name,
        agency: payload.agency,
        version: payload.version,
        idno: payload.idno || undefined,
        description: payload.description,
      });
      setMessage('Codelist created.', 'success');
    }
    await loadList();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Save failed', 'error');
  }
}

function confirmDeleteCodelist(codelist) {
  deleteDialog.codelist = codelist;
  deleteDialog.show = true;
}

async function doDeleteCodelist() {
  if (!deleteDialog.codelist) return;
  deleteDialog.saving = true;
  try {
    await deleteCodelist(deleteDialog.codelist.id);
    setMessage('Codelist version deleted.', 'success');
    deleteDialog.show = false;
    await loadList();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Delete failed', 'error');
  } finally {
    deleteDialog.saving = false;
  }
}
</script>

<style scoped>
.cl-filters-row {
  column-gap: 0.5rem;
  row-gap: 0.75rem;
}
@media (min-width: 600px) {
  .cl-filters-row {
    column-gap: 2rem;
    row-gap: 0.5rem;
  }
}
</style>
