<template>
  <div class="ds-list-root">
    <v-expand-transition>
      <v-sheet
        v-if="selected.length > 0"
        border
        rounded="0"
        class="pa-3 px-4 mb-3 d-flex align-center flex-wrap gap-2 ga-2 bg-surface"
      >
        <span class="text-body-2 font-weight-medium">{{ selected.length }} selected</span>
        <v-btn color="error" variant="flat" size="small" prepend-icon="mdi-delete" @click="emitBatchDelete">
          Delete selected
        </v-btn>
        <v-btn variant="text" size="small" @click="clearSelection">Clear</v-btn>
      </v-sheet>
    </v-expand-transition>

    <v-card rounded="0" border class="overflow-hidden ds-list-card">
      <v-card-text class="pa-0">
        <v-data-table-server
          v-model="selected"
          v-model:expanded="expandedIds"
          :headers="headers"
          :items="displayItems"
          :items-length="serverTotal"
          :page="page"
          :items-per-page="itemsPerPage"
          :items-per-page-options="[10, 25, 50, 100]"
          :loading="loading"
          item-value="id"
          item-selectable="selectable"
          select-strategy="page"
          show-expand
          show-select
          hover
          class="elevation-0 ds-data-table"
          @update:page="$emit('update:page', $event)"
          @update:items-per-page="$emit('update:itemsPerPage', $event)"
          @update:expanded="onExpandedChange"
        >
          <template #no-data>
            <div class="py-12 px-4 text-center">
              <v-icon size="48" color="medium-emphasis" class="mb-3 opacity-60">mdi-database-search-outline</v-icon>
              <div class="text-h6 font-weight-medium mb-1">{{ emptyTitle }}</div>
              <p class="text-body-2 text-medium-emphasis mb-0 mx-auto" style="max-width: 360px">
                {{ emptyHint }}
              </p>
            </div>
          </template>

          <template v-slot:[`item.data-table-expand`]="{ item, internalItem, isExpanded, toggleExpand }">
            <v-btn
              v-if="expandable(item)"
              :icon="isExpanded(internalItem) ? 'mdi-chevron-up' : 'mdi-chevron-down'"
              size="small"
              variant="text"
              color="primary"
              aria-label="Show versions"
              @click.stop="toggleExpand(internalItem)"
            />
          </template>

          <template v-slot:[`item.id`]="{ item }">
            <code class="text-caption font-weight-medium">{{ item.id }}</code>
          </template>
          <template v-slot:[`item.title`]="{ item }">
            <a
              v-if="item.title"
              href="#"
              class="ds-title-cell text-primary text-decoration-none font-weight-medium ds-title-link"
              @click.prevent="$emit('manage', item)"
            >
              {{ item.title }}
            </a>
            <span v-else class="ds-title-cell text-body-2 text-medium-emphasis">—</span>
          </template>
          <template v-slot:[`item.name`]="{ item }">
            <a
              href="#"
              class="text-primary text-decoration-none font-weight-medium ds-name-link"
              @click.prevent="$emit('manage', item)"
            >
              {{ item.name }}
            </a>
          </template>
          <template v-slot:[`item.agency`]="{ item }">
            <span class="text-body-2">{{ item.agency || '—' }}</span>
          </template>
          <template v-slot:[`item.version`]="{ item }">
            <code class="text-caption">{{ item.version || '—' }}</code>
          </template>
          <template v-slot:[`item.versions_count`]="{ item }">
            <v-chip size="small" variant="tonal" color="primary" class="font-weight-medium">
              {{ item.versions_count ?? 1 }}
            </v-chip>
          </template>
          <template v-slot:[`item.projects_count`]="{ item }">
            <v-btn size="small" variant="tonal" color="indigo" class="font-weight-medium" @click="$emit('projects', item)">
              {{ Number.isFinite(Number(item.projects_count)) ? Number(item.projects_count) : 0 }}
            </v-btn>
          </template>
          <template v-slot:[`item.idno`]="{ item }">
            <code v-if="item.idno" class="text-caption text-medium-emphasis">{{ item.idno }}</code>
            <span v-else class="text-medium-emphasis">—</span>
          </template>
          <template v-slot:[`item.status`]="{ item }">
            <v-chip :color="statusMeta(item.status).color" size="small" variant="tonal" class="font-weight-medium">
              {{ statusMeta(item.status).label }}
            </v-chip>
          </template>
          <template v-slot:[`item.updated`]="{ item }">
            <span class="text-body-2 text-medium-emphasis ds-updated-cell">{{ formatUpdated(item.updated) }}</span>
          </template>
          <template v-slot:[`item.actions`]="{ item }">
            <v-menu location="bottom end" transition="scale-transition">
              <template #activator="{ props: menuProps }">
                <v-btn icon size="small" variant="text" v-bind="menuProps" title="Actions">
                  <v-icon>mdi-dots-vertical</v-icon>
                </v-btn>
              </template>
              <v-list density="compact" rounded="lg" class="pa-1">
                <v-list-item
                  prepend-icon="mdi-delete"
                  title="Delete"
                  rounded="md"
                  class="text-error"
                  :disabled="isRowLockedForDelete(item)"
                  @click="$emit('delete', item)"
                />
                <v-divider class="my-1" />
                <v-list-item prepend-icon="mdi-sitemap" title="Edit" rounded="md" @click="$emit('manage', item)" />
              </v-list>
            </v-menu>
          </template>

          <template #expanded-row="{ columns, item }">
            <tr class="ds-expanded-bg">
              <td :colspan="columns.length" class="pa-0">
                <div class="pa-4 pl-md-6 ds-expand-accent">
                  <div class="text-caption font-weight-semibold text-medium-emphasis text-uppercase mb-3 ds-kicker-small">
                    All versions
                  </div>
                  <v-progress-linear v-if="versionsLoading[item.id]" indeterminate color="primary" rounded class="mb-2" />
                  <v-alert v-else-if="versionsError[item.id]" type="error" density="compact" variant="tonal" rounded="lg">
                    {{ versionsError[item.id] }}
                  </v-alert>
                  <v-table v-else density="compact" class="elevation-0 rounded-lg border bg-surface">
                    <thead>
                      <tr>
                        <th class="text-left text-caption font-weight-semibold">Seq</th>
                        <th class="text-left text-caption font-weight-semibold">Version</th>
                        <th class="text-left text-caption font-weight-semibold">Status</th>
                        <th class="text-left text-caption font-weight-semibold">IDNO</th>
                        <th class="text-left text-caption font-weight-semibold">ID</th>
                        <th class="text-right" width="108"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="v in versionsByFamily[item.id]" :key="'v-' + v.id">
                        <td>{{ v.version_seq ?? '—' }}</td>
                        <td><code class="text-caption">{{ v.version }}</code></td>
                        <td>
                          <v-chip :color="statusMeta(v.status).color" size="x-small" variant="tonal" class="font-weight-medium">
                            {{ statusMeta(v.status).label }}
                          </v-chip>
                        </td>
                        <td><code class="text-caption">{{ v.idno || '—' }}</code></td>
                        <td><code class="text-caption">{{ v.id }}</code></td>
                        <td class="text-right">
                          <v-btn size="small" variant="flat" color="primary" rounded="lg" @click="$emit('manage', v)">
                            Open
                          </v-btn>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>
                </div>
              </td>
            </tr>
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>

    <DataStructureFormDialog v-model="formDialog.show" :structure="formDialog.structure" @saved="$emit('saved', $event)" />
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import DataStructureFormDialog from './DataStructureFormDialog.vue';
import { useDataStructuresApi } from '../composables/useDataStructuresApi';

defineOptions({ name: 'DataStructureList' });

const props = defineProps({
  structures: { type: Array, default: () => [] },
  loading: Boolean,
  /** Total rows matching current server filters (pagination) */
  serverTotal: { type: Number, default: 0 },
  page: { type: Number, default: 1 },
  itemsPerPage: { type: Number, default: 25 },
  /** Search text active on server (for empty-state copy) */
  hasSearch: { type: Boolean, default: false },
});

const emit = defineEmits(['manage', 'delete', 'saved', 'projects', 'batch-delete', 'update:page', 'update:itemsPerPage']);

const { fetchStructureVersions } = useDataStructuresApi();

const selected = ref([]);
const expandedIds = ref([]);
const versionsByFamily = reactive({});
const versionsLoading = reactive({});
const versionsError = reactive({});

/** Matches server `Data_structure_model::is_locked_status` — cannot delete. */
function isRowLockedForDelete(row) {
  const s = Number(row?.status);
  return s === 20 || s === 40;
}

/** Rows with `selectable` for Vuetify `item-selectable` (published/archived cannot be deleted). */
const displayItems = computed(() =>
  props.structures.map((row) => ({
    ...row,
    selectable: !isRowLockedForDelete(row),
  }))
);

watch(
  () => [props.page, props.itemsPerPage],
  () => {
    selected.value = [];
  }
);

const headers = [
  { title: '', key: 'data-table-expand', width: '48', sortable: false },
  { title: 'ID', key: 'id', width: '72' },
  { title: 'Title', key: 'title', sortable: false, minWidth: '180' },
  { title: 'Name', key: 'name', width: '140' },
  { title: 'Agency', key: 'agency', width: '100' },
  { title: 'Latest version', key: 'version', width: '110' },
  { title: 'Versions', key: 'versions_count', width: '100' },
  { title: 'Projects', key: 'projects_count', width: '100' },
  { title: 'IDNO', key: 'idno', width: '220' },
  { title: 'Status', key: 'status', width: '120' },
  { title: 'Updated', key: 'updated', width: '168' },
  { title: '', key: 'actions', sortable: false, width: '72', align: 'end' },
];

const formDialog = reactive({
  show: false,
  structure: null,
});

const emptyTitle = computed(() => {
  if (props.loading) return '';
  if (props.hasSearch && props.serverTotal === 0) return 'No matching structures';
  if (props.serverTotal === 0) return 'No data structures yet';
  return '';
});

const emptyHint = computed(() => {
  if (props.hasSearch && props.serverTotal === 0) {
    return 'Try another keyword or clear the search to see the full catalogue.';
  }
  if (props.serverTotal === 0) {
    return 'Create a definition or import an SDMX structure message to get started.';
  }
  return '';
});

function statusMeta(code) {
  const n = Number(code);
  if (n === 0) return { label: 'Draft', color: 'grey' };
  if (n === 10) return { label: 'Review', color: 'info' };
  if (n === 20) return { label: 'Published', color: 'success' };
  if (n === 30) return { label: 'Deprecated', color: 'warning' };
  if (n === 40) return { label: 'Archived', color: 'secondary' };
  return { label: '—', color: 'default' };
}

/** Server stores `updated` as Unix seconds (or occasionally ms / ISO string). */
function formatUpdated(val) {
  if (val == null || val === '') return '—';
  const n = Number(val);
  if (Number.isFinite(n) && n > 0) {
    const ms = n > 1e12 ? n : n * 1000;
    const d = new Date(ms);
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleDateString(undefined, { dateStyle: 'short' });
    }
  }
  const d = new Date(val);
  if (!Number.isNaN(d.getTime())) {
    return d.toLocaleDateString(undefined, { dateStyle: 'short' });
  }
  return '—';
}

function expandable(item) {
  const n = Number(item?.versions_count);
  return Number.isFinite(n) ? n > 1 : true;
}

async function loadVersionsForFamilyRow(row) {
  const key = row?.id;
  if (key == null) return;
  if (versionsLoading[key]) return;
  if (versionsByFamily[key]?.length) return;
  versionsLoading[key] = true;
  versionsError[key] = '';
  try {
    const rows = await fetchStructureVersions(row.id);
    versionsByFamily[key] = [...rows].sort((a, b) => {
      const sa = Number(a.version_seq) || 0;
      const sb = Number(b.version_seq) || 0;
      if (sa !== sb) return sa - sb;
      return Number(a.id) - Number(b.id);
    });
  } catch (e) {
    versionsError[key] = e?.response?.data?.message || e?.message || 'Failed to load versions';
    versionsByFamily[key] = [];
  } finally {
    versionsLoading[key] = false;
  }
}

function onExpandedChange(expanded) {
  const list = Array.isArray(expanded) ? expanded : [];
  for (const entry of list) {
    const sid = typeof entry === 'object' && entry !== null ? entry.id : entry;
    const row = props.structures.find((r) => Number(r.id) === Number(sid));
    if (row && expandable(row)) {
      loadVersionsForFamilyRow(row);
    }
  }
}

function openEdit(item) {
  formDialog.structure = item;
  formDialog.show = true;
}

function clearSelection() {
  selected.value = [];
}

function emitBatchDelete() {
  const raw = Array.isArray(selected.value) ? selected.value : [];
  const ids = raw
    .map((x) => {
      if (x != null && typeof x === 'object' && 'id' in x) return Number(x.id);
      return Number(x);
    })
    .filter((n) => Number.isInteger(n) && n >= 1);
  const idSet = new Set(ids);
  const rows = props.structures.filter((r) => idSet.has(Number(r.id)));
  if (!rows.length) return;
  emit('batch-delete', rows);
}

defineExpose({
  clearSelection,
});
</script>

<style scoped>
.ds-list-card {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.ds-data-table :deep(th) {
  font-size: 0.75rem;
  font-weight: 600 !important;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.ds-title-cell {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.ds-name-link:hover,
.ds-title-link:hover {
  text-decoration: underline;
}
.ds-expanded-bg {
  background: rgba(var(--v-theme-primary), 0.04);
}
.ds-kicker-small {
  letter-spacing: 0.06em;
}
.ds-expand-accent {
  border-left: 3px solid rgb(var(--v-theme-primary));
}
</style>
