<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="cl-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Codelists</h1>
      </v-col>
      <v-col cols="12" md="4" class="d-flex justify-end">
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
          Add codelist
        </v-btn>
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
            placeholder="Name, idno, agency, version…"
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
            {{ serverTotal }} {{ serverTotal === 1 ? 'codelist' : 'codelists' }}
          </v-chip>
        </v-col>
      </v-row>
    </v-card>

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
      @batch-delete="confirmBatchDeleteCodelists"
      @saved="onCodelistSaved"
    />

    <v-dialog v-model="deleteDialog.show" max-width="440" persistent>
      <v-card>
        <v-card-title class="text-h6 pa-4">Delete codelist version?</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          Delete <strong>{{ deleteDialog.codelist?.name }}</strong>
          ({{ deleteDialog.codelist?.agency }} / {{ deleteDialog.codelist?.version }})?
          Items, groups, and translations for this version only will be removed.
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.saving" @click="doDeleteCodelist">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="batchDeleteDialog.show" max-width="480" persistent>
      <v-card>
        <v-card-title class="text-h6 pa-4">Delete selected codelists?</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          This will delete <strong>{{ batchDeleteDialog.rows?.length ?? 0 }}</strong>
          {{ (batchDeleteDialog.rows?.length ?? 0) === 1 ? 'codelist version' : 'codelist versions' }}.
          Items, groups, and translations for each removed version are deleted.
          Published versions or codelists referenced by data structure (DSD) components cannot be removed.
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="batchDeleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="batchDeleteDialog.saving" @click="doBatchDeleteCodelists">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, inject, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import CodelistList from '../components/CodelistList.vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistListPage' });

const route = useRoute();
const router = useRouter();
const { siteUrl } = useAppConfig();
const setMessage = inject('setMessage', () => {});

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Codelists', disabled: true },
]);

const {
  loading,
  fetchCodelists,
  createCodelist,
  updateCodelist,
  deleteCodelist,
  deleteCodelistsBatch,
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
const batchDeleteDialog = reactive({ show: false, saving: false, rows: [] });

let searchDebounceTimer;

const statusFilterItems = [
  { title: 'Draft', value: 0 },
  { title: 'Review', value: 10 },
  { title: 'Published', value: 20 },
  { title: 'Deprecated', value: 30 },
  { title: 'Archived', value: 40 },
];

const hasSearch = computed(() => String(searchDebounced.value ?? '').trim().length > 0);

function openCreateDialog() {
  listRef.value?.openCreate?.();
}

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

function confirmBatchDeleteCodelists(rows) {
  const list = Array.isArray(rows) ? rows : [];
  if (!list.length) return;
  batchDeleteDialog.rows = list;
  batchDeleteDialog.show = true;
}

async function doBatchDeleteCodelists() {
  const rows = batchDeleteDialog.rows;
  if (!Array.isArray(rows) || !rows.length) return;
  batchDeleteDialog.saving = true;
  try {
    const result = await deleteCodelistsBatch(rows.map((r) => r.id));
    const deletedCount = Number(result?.deleted_count) || 0;
    const failed = Array.isArray(result?.failed) ? result.failed : [];
    const failedCount = failed.length;
    if (deletedCount > 0 && failedCount === 0) {
      setMessage(
        deletedCount === 1 ? 'Codelist version deleted.' : `${deletedCount} codelist versions deleted.`,
        'success'
      );
    } else if (deletedCount > 0 && failedCount > 0) {
      const sample = failed.slice(0, 3).map((f) => `#${f.id}: ${f.message}`).join('; ');
      setMessage(`Deleted ${deletedCount}; ${failedCount} failed${sample ? `. ${sample}` : ''}`, 'warning');
    } else if (failedCount > 0) {
      const sample = failed.slice(0, 3).map((f) => `#${f.id}: ${f.message}`).join('; ');
      setMessage(`Could not delete selected codelists${sample ? `. ${sample}` : ''}`, 'error');
    } else {
      setMessage('Nothing was deleted.', 'info');
    }
    batchDeleteDialog.show = false;
    listRef.value?.clearSelection?.();
    await loadList();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Batch delete failed', 'error');
  } finally {
    batchDeleteDialog.saving = false;
  }
}
</script>

<style scoped>
.cl-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.cl-breadcrumbs :deep(.v-breadcrumbs-item),
.cl-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
