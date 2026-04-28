<template>
  <div class="cl-list-root">
    <v-card rounded="xl" border class="overflow-hidden">
      <v-card-text class="pa-0">
        <v-data-table-server
          v-model:expanded="expandedIds"
          :headers="headers"
          :items="codelists"
          :items-length="serverTotal"
          :page="page"
          :items-per-page="itemsPerPage"
          :items-per-page-options="[10, 25, 50, 100]"
          :loading="loading"
          item-value="id"
          show-expand
          hover
          class="elevation-0"
          @update:page="$emit('update:page', $event)"
          @update:items-per-page="$emit('update:itemsPerPage', $event)"
          @update:expanded="onExpandedChange"
        >
          <template #no-data>
            <div class="py-12 px-4 text-center">
              <v-icon size="48" color="medium-emphasis" class="mb-3 opacity-60">mdi-format-list-bulleted</v-icon>
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

          <template #item.name="{ item }">
            <a href="#" class="text-primary text-decoration-none font-weight-medium" @click.prevent="$emit('manage', item)">{{ item.name }}</a>
          </template>
          <template #item.agency="{ item }">
            {{ item.agency || '—' }}
          </template>
          <template #item.version="{ item }">
            {{ item.version || '—' }}
          </template>
          <template #item.versions_count="{ item }">
            <v-chip size="small" variant="tonal" color="primary" class="font-weight-medium">
              {{ item.versions_count ?? 1 }}
            </v-chip>
          </template>
          <template #item.status="{ item }">
            <v-chip :color="statusMeta(item.status).color" size="small" variant="tonal" class="font-weight-medium">
              {{ statusMeta(item.status).label }}
            </v-chip>
          </template>
          <template #item.changed="{ item }">
            <span class="text-body-2 text-medium-emphasis">{{ formatChanged(item.changed) }}</span>
          </template>
          <template #item.idno="{ item }">
            <code v-if="item.idno" class="text-caption">{{ item.idno }}</code>
            <span v-else>—</span>
          </template>
          <template #item.item_count="{ item }">
            {{ item.item_count ?? '—' }}
          </template>
          <template #item.dsd_component_count="{ item }">
            <v-tooltip v-if="Number(item.dsd_component_count) > 0" location="top" text="Data structure (DSD) components using this codelist">
              <template #activator="{ props: tipProps }">
                <v-chip v-bind="tipProps" size="small" variant="tonal" color="secondary" class="font-weight-medium">
                  {{ item.dsd_component_count }}
                </v-chip>
              </template>
            </v-tooltip>
            <span v-else class="text-medium-emphasis text-body-2">—</span>
          </template>
          <template #item.actions="{ item }">
            <v-menu location="bottom end" transition="scale-transition">
              <template #activator="{ props: menuProps }">
                <v-btn icon size="small" variant="text" v-bind="menuProps" title="Actions">
                  <v-icon>mdi-dots-vertical</v-icon>
                </v-btn>
              </template>
              <v-list density="compact">
                <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="openEdit(item)" />
                <v-list-item
                  prepend-icon="mdi-delete"
                  title="Delete"
                  color="error"
                  :disabled="Number(item.status) === 20"
                  @click="$emit('delete', item)"
                />
                <v-list-item prepend-icon="mdi-playlist-edit" title="Manage" @click="$emit('manage', item)" />
              </v-list>
            </v-menu>
          </template>

          <template #expanded-row="{ columns, item }">
            <tr class="cl-expanded-bg">
              <td :colspan="columns.length" class="pa-0">
                <div class="pa-4 pl-md-6 cl-expand-accent">
                  <div class="text-caption font-weight-semibold text-medium-emphasis text-uppercase mb-3">
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

    <CodelistFormDialog
      v-model="formDialog.show"
      :codelist="formDialog.codelist"
      @saved="onFormSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import CodelistFormDialog from './CodelistFormDialog.vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistList' });

const props = defineProps({
  codelists: { type: Array, default: () => [] },
  loading: Boolean,
  serverTotal: { type: Number, default: 0 },
  page: { type: Number, default: 1 },
  itemsPerPage: { type: Number, default: 25 },
  hasSearch: { type: Boolean, default: false },
});

const emit = defineEmits(['manage', 'delete', 'saved', 'update:page', 'update:itemsPerPage']);

const emptyTitle = computed(() => {
  if (props.loading) return '';
  if (props.hasSearch && props.serverTotal === 0) return 'No matching codelists';
  if (props.serverTotal === 0) return 'No codelists yet';
  return '';
});

const emptyHint = computed(() => {
  if (props.hasSearch && props.serverTotal === 0) {
    return 'Try another keyword or clear the search to see the full catalogue.';
  }
  if (props.serverTotal === 0) {
    return 'Create a codelist to get started.';
  }
  return '';
});

defineExpose({ openCreate });

const { fetchCodelistVersions } = useCodelistsApi();

const expandedIds = ref([]);
const versionsByFamily = reactive({});
const versionsLoading = reactive({});
const versionsError = reactive({});

const headers = [
  { title: '', key: 'data-table-expand', width: '48', sortable: false },
  { title: 'ID', key: 'id', width: '60' },
  { title: 'Name', key: 'name', width: '140' },
  { title: 'Agency', key: 'agency', width: '100' },
  { title: 'Latest version', key: 'version', width: '110' },
  { title: 'Versions', key: 'versions_count', width: '100' },
  { title: 'IDNO', key: 'idno', width: '200' },
  { title: 'Status', key: 'status', width: '120' },
  { title: 'Changed', key: 'changed', width: '120' },
  { title: 'Items', key: 'item_count', width: '70' },
  {
    title: 'DSD',
    key: 'dsd_component_count',
    width: '76',
    align: 'end',
    sortable: false,
  },
  { title: '', key: 'actions', sortable: false, width: '80', align: 'end' },
];

const formDialog = reactive({
  show: false,
  codelist: null,
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

/** Server stores `changed` as Unix seconds. */
function formatChanged(val) {
  if (val == null || val === '') return '—';
  const n = Number(val);
  if (Number.isFinite(n) && n > 0) {
    const ms = n > 1e12 ? n : n * 1000;
    const d = new Date(ms);
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleDateString(undefined, { dateStyle: 'short' });
    }
  }
  return '—';
}

function expandable(item) {
  const raw = item?.versions_count;
  if (raw == null || raw === '') return false;
  const n = Number(raw);
  return Number.isFinite(n) && n > 1;
}

async function loadVersionsForFamilyRow(row) {
  const key = row?.id;
  if (key == null) return;
  if (versionsLoading[key]) return;
  if (versionsByFamily[key]?.length) return;
  versionsLoading[key] = true;
  versionsError[key] = '';
  try {
    const rows = await fetchCodelistVersions(row.name, row.agency);
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
    const row = props.codelists.find((r) => Number(r.id) === Number(sid));
    if (row && expandable(row)) {
      loadVersionsForFamilyRow(row);
    }
  }
}

function openCreate() {
  formDialog.codelist = null;
  formDialog.show = true;
}

function openEdit(item) {
  formDialog.codelist = item;
  formDialog.show = true;
}

function onFormSaved(payload) {
  if (formDialog.codelist?.id) {
    emit('saved', { ...payload, codelistId: formDialog.codelist.id });
  } else {
    emit('saved', payload);
  }
}
</script>

<style scoped>
.cl-list-root :deep(.v-data-table-footer) {
  border-top: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
