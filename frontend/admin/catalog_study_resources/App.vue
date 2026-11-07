<template>
  <v-app>
    <v-main class="catalog-study-resources-vue">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
        {{ snackbar.text }}
      </v-snackbar>

      <ResourceFormPage
        v-if="view === 'form'"
        :resource-id="editingId"
        @saved="onResourceSaved"
        @cancel="goList"
      />

      <template v-else>
        <v-overlay :model-value="loading" class="align-center justify-center" persistent>
          <v-progress-circular indeterminate size="48" />
        </v-overlay>

        <div class="csr-top-row">
          <div class="csr-top-left d-flex flex-wrap align-center gap-2">
            <span class="text-caption text-medium-emphasis">{{ lbl.total }} {{ total }}</span>
            <v-btn
              color="error"
              size="small"
              variant="tonal"
              :disabled="selected.length === 0 || deletingBatch"
              :loading="deletingBatch"
              @click="batchDelete"
            >
              <v-icon start size="small">mdi-delete</v-icon>
              {{ lbl.delete_selection }}
            </v-btn>
          </div>
          <div class="csr-top-right d-flex flex-wrap align-center justify-end gap-1">
            <v-btn color="primary" size="small" variant="text" @click="openCreate">
              {{ lbl.add_resource }}
            </v-btn>
            <v-btn size="small" variant="text" @click="importOpen = true">
              {{ lbl.import_rdf }}
            </v-btn>
            <v-btn size="small" variant="text" :loading="fixingLinks" @click="runFixLinks">
              {{ lbl.fix_links }}
            </v-btn>
            <v-btn
              v-if="exportRdfUrl"
              size="small"
              variant="text"
              :href="exportRdfUrl"
            >
              {{ lbl.export_rdf }}
            </v-btn>
          </div>
        </div>

        <v-data-table
          v-model="selected"
          v-model:sort-by="tableSortBy"
          :headers="headers"
          :items="rows"
          item-value="resource_id"
          show-select
          density="compact"
          class="csr-table elevation-0 border rounded"
          :items-per-page="-1"
          hide-default-footer
          :multi-sort="false"
          must-sort
        >
          <template #item.title="{ item }">
            <div>
              <a
                :href="editHref(item)"
                class="text-decoration-none font-weight-medium"
                @click.prevent="openEdit(item)"
              >
                {{ item.title }}
              </a>
              <div v-if="item.resource_idno" class="text-caption text-medium-emphasis">
                {{ item.resource_idno }}
              </div>
            </div>
          </template>

          <template #item.file_exists="{ item }">
            <v-icon v-if="item.file_exists" color="success" size="small">mdi-check-circle</v-icon>
            <v-icon v-else color="error" size="small">mdi-close-circle</v-icon>
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex align-center flex-wrap gap-1">
              <v-btn
                size="x-small"
                variant="text"
                icon="mdi-pencil"
                :title="lbl.edit"
                :href="editHref(item)"
                @click.prevent="openEdit(item)"
              />
              <v-btn
                v-if="downloadHref(item)"
                size="x-small"
                variant="text"
                :href="downloadHref(item)"
                icon="mdi-download"
                :title="lbl.download"
              />
              <v-btn
                size="x-small"
                variant="text"
                color="error"
                icon="mdi-delete"
                :title="lbl.delete"
                :loading="deletingId === item.resource_id"
                @click.prevent="confirmDeleteOne(item)"
              />
            </div>
          </template>
        </v-data-table>

        <div class="csr-legend d-flex flex-wrap align-center gap-3">
          <span><v-icon size="14" color="success" class="mr-1">mdi-check-circle</v-icon> {{ lbl.legend_ok }}</span>
          <span><v-icon size="14" color="error" class="mr-1">mdi-close-circle</v-icon> {{ lbl.legend_missing }}</span>
        </div>

        <ImportRdfDialog v-model="importOpen" @imported="onRdfImported" />
      </template>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyResourcesApi } from './composables/useStudyResourcesApi';
import ResourceFormPage from './components/ResourceFormPage.vue';
import ImportRdfDialog from './components/ImportRdfDialog.vue';

const { config } = useAppConfig();
const { fetchResources, deleteResource, fixLinks } = useStudyResourcesApi();

const lbl = computed(() => config.value?.labels || {});
const legacy = computed(() => config.value?.legacyUrls || {});
const exportRdfUrl = computed(() => legacy.value.exportRdfUrl || '');

/** Absolute or relative → pathname only (site_url includes host + index.php). */
function toPathname(urlOrPath) {
  const raw = String(urlOrPath || '').trim();
  if (!raw) return '';
  try {
    if (/^https?:\/\//i.test(raw)) {
      return new URL(raw).pathname.replace(/\/+$/, '') || '';
    }
  } catch (_) {
    /* ignore */
  }
  const path = raw.startsWith('/') ? raw : `/${raw}`;
  return path.replace(/\/+$/, '');
}

const resourcesTabPath = computed(() => {
  const configured = config.value?.resourcesTabUrl;
  if (configured) return toPathname(configured);
  const sid = config.value?.studySid;
  const site = String(config.value?.siteUrl || '').replace(/\/+$/, '');
  if (sid != null && site) {
    return toPathname(`${site}/admin/catalog/edit/${sid}/resources`);
  }
  return toPathname(window.location.pathname);
});

/** 'list' | 'form' */
const view = ref('list');
const editingId = ref(null);

const loading = ref(true);
const rows = ref([]);
const total = ref(0);
const selected = ref([]);
const deletingId = ref(null);
const deletingBatch = ref(false);
const fixingLinks = ref(false);
const snackbar = ref({ show: false, text: '', color: 'surface' });
const importOpen = ref(false);

/** Vuetify sort state; keys must match API `sort_by` whitelist. */
const tableSortBy = ref([{ key: 'title', order: 'asc' }]);

const API_SORT_KEYS = new Set(['title', 'dctype', 'changed', 'created', 'resource_id', 'filename']);

const headers = computed(() => [
  { title: lbl.value.col_title || 'Title', key: 'title', sortable: true },
  { title: lbl.value.col_link || 'Link', key: 'file_exists', width: '72px', sortable: false },
  { title: lbl.value.col_type || 'Type', key: 'dctype', sortable: true },
  { title: lbl.value.col_modified || 'Modified', key: 'changed', width: '160px', sortable: true },
  { title: lbl.value.actions || 'Actions', key: 'actions', sortable: false, align: 'end', width: '140px' },
]);

function sortQueryFromTable() {
  const first = tableSortBy.value?.[0];
  const rawKey = first?.key != null ? String(first.key) : '';
  const sort_by = rawKey && API_SORT_KEYS.has(rawKey) ? rawKey : 'title';
  const sort_order = first?.order === 'desc' ? 'desc' : 'asc';
  return { sort_by, sort_order };
}

function showSnack(text, color = 'surface') {
  snackbar.value = { show: true, text, color };
}

function listHref() {
  return resourcesTabPath.value || toPathname(window.location.pathname);
}

function addHref() {
  return `${listHref()}/add`;
}

function editHref(item) {
  if (!item?.resource_id) return '#';
  return `${listHref()}/edit/${encodeURIComponent(String(item.resource_id))}`;
}

function parseRouteFromLocation() {
  const path = toPathname(window.location.pathname);
  const base = listHref();
  if (!base) {
    view.value = 'list';
    editingId.value = null;
    return;
  }
  if (path === `${base}/add`) {
    view.value = 'form';
    editingId.value = null;
    return;
  }
  const editPrefix = `${base}/edit/`;
  if (path.startsWith(editPrefix)) {
    const id = path.slice(editPrefix.length).split('/')[0];
    if (id) {
      view.value = 'form';
      editingId.value = id;
      return;
    }
  }
  view.value = 'list';
  editingId.value = null;
}

function navigate(url, { replace = false } = {}) {
  if (replace) {
    window.history.replaceState({}, '', url);
  } else {
    window.history.pushState({}, '', url);
  }
  parseRouteFromLocation();
}

function openCreate() {
  navigate(addHref());
}

function openEdit(item) {
  if (!item?.resource_id) return;
  navigate(editHref(item));
}

function goList({ replace = false } = {}) {
  navigate(listHref(), { replace });
}

function downloadHref(item) {
  const u = item?._links?.download;
  return u && String(u).trim() !== '' ? u : null;
}

async function load() {
  loading.value = true;
  try {
    const { sort_by, sort_order } = sortQueryFromTable();
    const data = await fetchResources({ sort_by, sort_order });
    rows.value = Array.isArray(data.resources) ? data.resources : [];
    total.value = typeof data.total === 'number' ? data.total : rows.value.length;
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    rows.value = [];
    total.value = 0;
  } finally {
    loading.value = false;
  }
}

async function onResourceSaved() {
  showSnack(lbl.value.saved || 'Saved', 'success');
  goList({ replace: true });
  await load();
}

async function onRdfImported(data) {
  const n = data?.entries_imported;
  const msg =
    n != null
      ? `${lbl.value.saved || 'Imported'}: ${n}`
      : lbl.value.saved || 'Imported';
  showSnack(msg, 'success');
  await load();
}

async function runFixLinks() {
  if (!window.confirm(lbl.value.confirm_fix_links || 'Fix broken resource links for this study?')) {
    return;
  }
  fixingLinks.value = true;
  try {
    const data = await fixLinks();
    const n = data?.links_fixed;
    showSnack(
      n != null
        ? `${lbl.value.fix_links_done || 'Links fixed'}: ${n}`
        : lbl.value.saved || 'Done',
      'success'
    );
    await load();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    fixingLinks.value = false;
  }
}

async function confirmDeleteOne(item) {
  if (!item?.resource_id) return;
  if (!window.confirm(lbl.value.confirm_delete || 'Delete this resource?')) return;
  deletingId.value = item.resource_id;
  try {
    await deleteResource(item.resource_id);
    selected.value = selected.value.filter((id) => id !== item.resource_id);
    showSnack(lbl.value.saved || 'Deleted', 'success');
    await load();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    deletingId.value = null;
  }
}

async function batchDelete() {
  if (selected.value.length === 0) {
    showSnack(lbl.value.no_selection || 'Nothing selected', 'warning');
    return;
  }
  if (!window.confirm(lbl.value.confirm_batch_delete || 'Delete selected?')) return;
  deletingBatch.value = true;
  try {
    const ids = [...selected.value];
    for (const id of ids) {
      await deleteResource(id);
    }
    selected.value = [];
    await load();
    showSnack(lbl.value.saved || 'Done', 'success');
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
    await load();
  } finally {
    deletingBatch.value = false;
  }
}

/** Map legacy ?add=1 / ?edit=id onto path routes. */
function migrateQueryToPath() {
  try {
    const q = new URLSearchParams(window.location.search || '');
    if (q.get('add') === '1') {
      navigate(addHref(), { replace: true });
      return true;
    }
    if (q.get('edit')) {
      navigate(`${listHref()}/edit/${encodeURIComponent(q.get('edit'))}`, { replace: true });
      return true;
    }
    if (q.get('import') === '1') {
      importOpen.value = true;
      q.delete('import');
      const url = new URL(window.location.href);
      url.searchParams.delete('import');
      window.history.replaceState({}, '', url.pathname + (url.searchParams.toString() ? `?${url.searchParams}` : ''));
      return false;
    }
    if (q.get('fix') === '1') {
      const url = new URL(window.location.href);
      url.searchParams.delete('fix');
      window.history.replaceState({}, '', url.pathname + (url.searchParams.toString() ? `?${url.searchParams}` : ''));
      runFixLinks();
      return false;
    }
  } catch (_) {
    /* ignore */
  }
  return false;
}

/** After first list load; avoids duplicate fetch if VDataTable syncs sort-by on mount. */
const initialResourcesLoaded = ref(false);

watch(
  tableSortBy,
  () => {
    if (!initialResourcesLoaded.value || view.value !== 'list') return;
    load();
  },
  { deep: true }
);

watch(view, (v) => {
  if (v === 'list' && initialResourcesLoaded.value && rows.value.length === 0 && !loading.value) {
    load();
  }
});

function onPopState() {
  parseRouteFromLocation();
}

onMounted(async () => {
  window.addEventListener('popstate', onPopState);
  const migrated = migrateQueryToPath();
  if (!migrated) parseRouteFromLocation();
  if (view.value === 'list') {
    await load();
  }
  initialResourcesLoaded.value = true;
});

onUnmounted(() => {
  window.removeEventListener('popstate', onPopState);
});
</script>

<style scoped>
.catalog-study-resources-vue {
  padding: 1.5rem 1.25rem 2rem;
}

.csr-top-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.75rem 1rem;
  width: 100%;
  margin-bottom: 1.5rem;
}

@media (max-width: 600px) {
  .csr-top-row {
    grid-template-columns: 1fr;
  }

  .csr-top-right {
    justify-self: end;
  }
}

.csr-table {
  border-radius: 8px;
  overflow: hidden;
}

.csr-table :deep(th),
.csr-table :deep(td) {
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  padding-left: 8px !important;
  padding-right: 8px !important;
}

.csr-table :deep(th) {
  padding-top: 6px !important;
  padding-bottom: 6px !important;
}

.csr-legend {
  margin-top: 1.25rem;
  font-size: 0.75rem;
  line-height: 1.3;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}
</style>
