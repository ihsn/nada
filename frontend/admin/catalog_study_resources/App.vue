<template>
  <v-app>
    <v-main class="catalog-study-resources-vue">
      <v-overlay :model-value="loading" class="align-center justify-center" persistent>
        <v-progress-circular indeterminate size="48" />
      </v-overlay>

      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000">
        {{ snackbar.text }}
      </v-snackbar>

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
          <v-btn
            v-if="legacy.addUrl"
            color="primary"
            size="small"
            variant="text"
            :href="legacy.addUrl"
          >
            {{ lbl.add_resource }}
          </v-btn>
          <v-btn v-if="legacy.importUrl" size="small" variant="text" :href="legacy.importUrl">
            {{ lbl.import_rdf }}
          </v-btn>
          <v-btn v-if="legacy.fixLinksUrl" size="small" variant="text" :href="legacy.fixLinksUrl">
            {{ lbl.fix_links }}
          </v-btn>
          <v-btn v-if="legacy.exportRdfUrl" size="small" variant="text" :href="legacy.exportRdfUrl">
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
            <a :href="editResourceUrl(item)" class="text-decoration-none font-weight-medium">{{ item.title }}</a>
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
            <v-btn size="x-small" variant="text" :href="editResourceUrl(item)" icon="mdi-pencil" :title="lbl.edit" />
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

      <div class="csr-legend text-caption d-flex flex-wrap gap-3 mt-3">
        <span><v-icon size="small" color="success" class="mr-1">mdi-check-circle</v-icon> {{ lbl.legend_ok }}</span>
        <span><v-icon size="small" color="error" class="mr-1">mdi-close-circle</v-icon> {{ lbl.legend_missing }}</span>
      </div>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyResourcesApi } from './composables/useStudyResourcesApi';

const { config } = useAppConfig();
const { fetchResources, deleteResource } = useStudyResourcesApi();

const lbl = computed(() => config.value?.labels || {});
const legacy = computed(() => config.value?.legacyUrls || {});

const loading = ref(true);
const rows = ref([]);
const total = ref(0);
const selected = ref([]);
const deletingId = ref(null);
const deletingBatch = ref(false);
const snackbar = ref({ show: false, text: '', color: 'surface' });

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

const studySid = computed(() => config.value?.studySid);

function showSnack(text, color = 'surface') {
  snackbar.value = { show: true, text, color };
}

function editResourceUrl(item) {
  const sid = studySid.value;
  if (item?.resource_id == null || sid == null) return '#';
  const base = String(legacy.value.editBase || '').replace(/\/+$/, '');
  if (!base) return '#';
  return `${base}/${encodeURIComponent(String(item.resource_id))}/${encodeURIComponent(String(sid))}`;
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

/** After first list load; avoids duplicate fetch if VDataTable syncs sort-by on mount. */
const initialResourcesLoaded = ref(false);

watch(
  tableSortBy,
  () => {
    if (!initialResourcesLoaded.value) return;
    load();
  },
  { deep: true },
);

onMounted(async () => {
  await load();
  initialResourcesLoaded.value = true;
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

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}
</style>
