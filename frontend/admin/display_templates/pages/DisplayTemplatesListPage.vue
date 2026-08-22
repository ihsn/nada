<template>
  <div class="dt-list-page">
    <div class="dt-list-layout">
      <aside class="dt-list-sidebar">
        <v-card rounded="lg" elevation="2" class="dt-type-sidebar">
          <v-card-title class="text-subtitle-1 font-weight-semibold py-3 px-4">
            Types
          </v-card-title>
          <v-divider />
          <v-list density="compact" nav class="py-1">
            <v-list-item
              :active="sidebarSelected === ''"
              color="primary"
              rounded="lg"
              class="mx-2"
              @click="sidebarSelected = ''"
            >
              <template #prepend>
                <v-icon icon="mdi-filter-outline" />
              </template>
              <v-list-item-title>All</v-list-item-title>
            </v-list-item>
            <v-list-item
              v-for="group in sidebarGroups"
              :key="group.uid"
              :active="sidebarSelected === group.uid"
              color="primary"
              rounded="lg"
              class="mx-2"
              @click="sidebarSelected = group.uid"
            >
              <template #prepend>
                <v-icon :icon="dataTypeIcon(group.uid)" />
              </template>
              <v-list-item-title>{{ group.label }}</v-list-item-title>
              <template #append>
                <v-chip size="x-small" variant="tonal" color="primary">
                  {{ countForGroup(group) }}
                </v-chip>
              </template>
            </v-list-item>
          </v-list>
        </v-card>
      </aside>

      <div class="dt-list-main">
        <div class="d-flex flex-column flex-sm-row gap-3 align-sm-center justify-space-between flex-wrap mb-4">
          <div>
            <h1 class="text-h5 font-weight-semibold mb-1">Display template manager</h1>
            <p class="text-body-2 text-medium-emphasis mb-0">
              {{ visibleGroupCount }} type{{ visibleGroupCount === 1 ? '' : 's' }} ·
              {{ templates.length }} template{{ templates.length === 1 ? '' : 's' }}
            </p>
          </div>
          <div class="d-flex flex-wrap gap-2 align-center">
            <v-btn color="primary" variant="flat" prepend-icon="mdi-file-import-outline" @click="importDlg = true">
              Import
            </v-btn>
            <v-btn icon="mdi-reload" variant="text" :loading="loading" @click="load" />
          </div>
        </div>

        <v-progress-linear v-if="loading" class="mb-4" indeterminate color="primary" rounded />

        <template v-if="!loading && visibleGroups.length === 0">
          <v-alert type="info" variant="tonal" rounded="0">
            No display templates found.
          </v-alert>
        </template>

        <div class="dt-groups-stack">
          <section
            v-for="group in visibleGroups"
            :key="group.uid"
            class="dt-type-group"
          >
            <v-card rounded="0" elevation="1" class="dt-type-group-card">
              <div class="dt-type-group-header">
                <div class="d-flex align-center ga-3 min-width-0">
                  <v-avatar size="36" color="primary" variant="tonal" rounded="lg">
                    <v-icon :icon="dataTypeIcon(group.uid)" size="20" />
                  </v-avatar>
                  <div class="min-width-0">
                    <div class="text-subtitle-1 font-weight-semibold text-truncate">{{ group.label }}</div>
                    <div class="text-caption text-medium-emphasis">
                      {{ itemsForGroup(group).length }}
                      template{{ itemsForGroup(group).length === 1 ? '' : 's' }}
                    </div>
                  </div>
                </div>
              </div>

              <v-divider />

              <v-data-table
                v-if="itemsForGroup(group).length"
                :headers="headers"
                :items="itemsForGroup(group)"
                :loading="loading"
                item-value="uid"
                class="dt-type-table"
                density="comfortable"
                :items-per-page="-1"
                hide-default-footer
              >
                <template #item.default="{ item }">
                  <v-btn
                    icon
                    variant="text"
                    size="small"
                    :disabled="!canSetAsDefault(item) && !item.default"
                    :title="defaultActionTitle(item)"
                    @click="onSetDefault(item)"
                  >
                    <v-icon :icon="item.default ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank'" />
                  </v-btn>
                </template>

                <template #item.name="{ item }">
                  <router-link
                    class="text-primary text-decoration-none font-weight-medium"
                    :to="{ name: 'display-template-detail', params: { uid: item.uid } }"
                  >
                    {{ item.name }}
                  </router-link>
                  <div class="text-caption text-medium-emphasis text-truncate">{{ item.uid }}</div>
                </template>

                <template #item.template_type="{ item }">
                  <v-chip size="x-small" variant="tonal" :color="item.template_type === 'system' ? 'secondary' : 'primary'">
                    {{ templateTypeLabel(item.template_type) }}
                  </v-chip>
                </template>

                <template #item.status="{ item }">
                  <v-chip size="small" variant="tonal" :color="statusColor(item.status)">{{ item.status }}</v-chip>
                </template>

                <template #item.lang="{ item }">
                  <div class="d-flex flex-wrap ga-1">
                    <v-chip
                      v-for="code in languagesForItem(item)"
                      :key="code"
                      size="x-small"
                      variant="tonal"
                      :color="code === (item.lang || 'en') ? 'primary' : undefined"
                    >
                      {{ code }}
                    </v-chip>
                  </div>
                </template>

                <template #item.version="{ item }">
                  <span class="text-body-2">{{ item.version || '—' }}</span>
                </template>

                <template #item.created_at="{ item }">
                  <span class="text-body-2 text-medium-emphasis" :title="formatDateTime(item.created_at)">
                    {{ formatDate(item.created_at) }}
                  </span>
                </template>

                <template #item.updated_at="{ item }">
                  <span class="text-body-2 text-medium-emphasis" :title="formatDateTime(item.updated_at)">
                    {{ formatDate(item.updated_at) }}
                  </span>
                </template>

                <template #item.actions="{ item }">
                  <v-menu location="bottom end" transition="scale-transition">
                    <template #activator="{ props: menuProps }">
                      <v-btn
                        icon="mdi-dots-vertical"
                        size="small"
                        variant="text"
                        v-bind="menuProps"
                        title="Actions"
                      />
                    </template>
                    <v-list density="compact" min-width="200">
                      <v-list-item
                        prepend-icon="mdi-pencil-outline"
                        title="Edit"
                        @click="openTemplate(item)"
                      />
                      <v-list-item
                        prepend-icon="mdi-content-copy"
                        title="Duplicate"
                        @click="onDuplicate(item)"
                      />
                      <v-list-item
                        prepend-icon="mdi-star-outline"
                        :title="defaultActionTitle(item)"
                        :disabled="!canSetAsDefault(item)"
                        @click="onSetDefault(item)"
                      />
                      <v-divider class="my-1" />
                      <v-list-item
                        prepend-icon="mdi-delete-outline"
                        title="Delete"
                        base-color="error"
                        :disabled="isSystemTemplate(item)"
                        @click="onDeletePrompt(item)"
                      />
                    </v-list>
                  </v-menu>
                </template>
              </v-data-table>

              <div v-else class="dt-type-group-empty text-body-2 text-medium-emphasis">
                No templates for this type.
              </div>
            </v-card>
          </section>
        </div>
      </div>
    </div>

    <v-dialog v-model="importDlg" max-width="640" persistent scrollable>
      <v-card rounded="xl">
        <v-card-title class="pt-6 px-6">Import template JSON</v-card-title>
        <v-card-text class="px-6 pb-2">
          <v-alert
            v-if="importError"
            type="error"
            variant="tonal"
            density="compact"
            class="mb-3"
            closable
            @click:close="importError = ''"
          >
            {{ importError }}
          </v-alert>
          <p class="text-body-2 text-medium-emphasis mb-3">
            Paste or upload a display template document
            (<code>type: display_template</code>) or a metadata-editor tree.
            Name and note are read from <code>description</code>. The layout is
            <code>template.items</code>.
          </p>
          <v-file-input
            v-model="importFile"
            label="Pick JSON file"
            accept=".json,application/json"
            variant="outlined"
            prepend-icon="mdi-paperclip"
            show-size
            @update:model-value="readImportFile"
          />
          <v-textarea
            v-model="importForm.rawJson"
            label="Or paste JSON"
            variant="outlined"
            rows="10"
            class="font-mono text-body-2 mb-3"
          />
          <v-switch
            v-model="importCopyIfUidExists"
            color="primary"
            inset
            hide-details
            label="Create as new copy if UID already exists"
          />
        </v-card-text>
        <v-card-actions class="px-6 pb-6 pt-2">
          <v-spacer />
          <v-btn variant="text" @click="importDlg = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="savingImport" @click="doImport">Import</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="defaultDlg.show" max-width="440" persistent>
      <v-card rounded="xl">
        <v-card-title class="text-h6 font-weight-semibold pt-6 px-6">Set as default?</v-card-title>
        <v-card-text class="px-6">This will replace the current default for this type.</v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="defaultDlg.show = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="defaultDlg.busy" @click="doSetDefault">
            Set as default
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDlg.show" max-width="440" persistent>
      <v-card rounded="xl">
        <v-card-title class="text-h6 font-weight-semibold pt-6 px-6">Delete template?</v-card-title>
        <v-card-text class="px-6">{{ deleteDlg.item?.name }} ({{ deleteDlg.item?.uid }})</v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="deleteDlg.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDlg.busy" @click="doDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useDisplayTemplatesApi } from '../composables/useDisplayTemplatesApi';
import { formatApiError } from '../utils/apiError';
import {
  buildDisplayTemplateTypeGroups,
  dataTypeIcon,
  dedupeTemplatesByUid,
  templatesForTypeGroup,
} from '../utils/displayTemplateTypes';

defineOptions({ name: 'DisplayTemplatesListPage' });

const router = useRouter();
const setMessage = inject('setMessage', () => {});

const { loading, fetchTemplates, deleteTemplate, duplicateTemplate, setDefaultTemplate, importTemplate } =
  useDisplayTemplatesApi();

const templates = ref([]);
const sidebarSelected = ref('');

const headers = [
  { title: 'Default', key: 'default', sortable: false, width: '72px' },
  { title: 'Name', key: 'name', sortable: false },
  { title: 'Type', key: 'template_type', sortable: false, width: '110px' },
  { title: 'Languages', key: 'lang', sortable: false, width: '140px' },
  { title: 'Version', key: 'version', sortable: false, width: '96px' },
  { title: 'Status', key: 'status', sortable: false, width: '120px' },
  { title: 'Created', key: 'created_at', sortable: false, width: '120px' },
  { title: 'Updated', key: 'updated_at', sortable: false, width: '120px' },
  { title: '', key: 'actions', sortable: false, width: '56px', align: 'end' },
];

/** Sidebar + main content share the same canonical type order. */
const typeGroups = computed(() => buildDisplayTemplateTypeGroups(templates.value));

/** Sidebar lists all known types in canonical order (ME template manager). */
const sidebarGroups = computed(() => typeGroups.value);

function itemsForGroup(group) {
  return templatesForTypeGroup(group, templates.value);
}

function countForGroup(group) {
  return templatesForTypeGroup(group, templates.value).length;
}

const visibleGroups = computed(() => {
  const groups = typeGroups.value;
  if (sidebarSelected.value) {
    return groups.filter((group) => group.uid === sidebarSelected.value);
  }
  return groups.filter((group) => countForGroup(group) > 0);
});

const visibleGroupCount = computed(() => visibleGroups.value.length);

async function load() {
  try {
    templates.value = dedupeTemplatesByUid(await fetchTemplates());
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

onMounted(() => load());

function statusColor(s) {
  if (s === 'published') return 'success';
  if (s === 'archived') return 'secondary';
  return 'default';
}

function templateTypeLabel(type) {
  if (type === 'system') return 'System';
  if (type === 'imported') return 'Imported';
  return 'Custom';
}

function parseTemplateDate(value) {
  if (!value) return null;
  const raw = String(value).trim();
  if (!raw) return null;
  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const date = new Date(normalized);
  return Number.isNaN(date.getTime()) ? null : date;
}

function formatDate(value) {
  const date = parseTemplateDate(value);
  if (!date) return '—';
  return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatDateTime(value) {
  const date = parseTemplateDate(value);
  if (!date) return '';
  return date.toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function isSystemTemplate(item) {
  return item?.template_type === 'system' || item?.is_core;
}

function languagesForItem(item) {
  if (Array.isArray(item?.languages) && item.languages.length) {
    return item.languages;
  }
  const primary = String(item?.lang || '').trim();
  return primary ? [primary] : ['en'];
}

function openTemplate(item) {
  if (!item?.uid) return;
  router.push({ name: 'display-template-detail', params: { uid: item.uid } });
}

const importDlg = ref(false);
const importForm = ref({ rawJson: '' });
const importFile = ref(null);
const importError = ref('');
const importCopyIfUidExists = ref(false);
const savingImport = ref(false);

watch(importDlg, (open) => {
  if (open) {
    importForm.value = { rawJson: '' };
    importFile.value = null;
    importError.value = '';
    importCopyIfUidExists.value = false;
  }
});

function readImportFile(files) {
  const f = Array.isArray(files) ? files[0] : files;
  if (!f) return;
  const reader = new FileReader();
  reader.onload = () => {
    importForm.value.rawJson = String(reader.result || '');
  };
  reader.readAsText(f);
}

function formatImportSummary(summary) {
  if (!summary || typeof summary !== 'object') return 'Import completed.';
  const parts = [];
  if (summary.total_sections != null) parts.push(`${summary.total_sections} sections`);
  if (summary.total_fields != null) parts.push(`${summary.total_fields} fields`);
  if (summary.total_nodes != null) parts.push(`${summary.total_nodes} nodes`);
  const skipped = Array.isArray(summary.skipped_custom_section_containers)
    ? summary.skipped_custom_section_containers
    : [];
  if (skipped.length) {
    const labels = skipped
      .map((row) => (row && (row.title || row.key)) || '')
      .filter(Boolean);
    parts.push(
      labels.length
        ? `skipped custom containers: ${labels.join(', ')}`
        : `skipped ${skipped.length} custom container${skipped.length === 1 ? '' : 's'}`
    );
  }
  return parts.length ? `Imported (${parts.join(', ')}).` : 'Import completed.';
}

async function doImport() {
  importError.value = '';
  let parsed;
  try {
    parsed = JSON.parse(importForm.value.rawJson || '{}');
  } catch {
    importError.value = 'Invalid JSON.';
    return;
  }
  if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
    importError.value = 'Import JSON must be an object.';
    return;
  }
  savingImport.value = true;
  try {
    const body = parsed.data_type && parsed.template ? parsed : { template: parsed };
    body.copy_if_uid_exists = importCopyIfUidExists.value;
    const res = await importTemplate(body);
    importDlg.value = false;
    setMessage(formatImportSummary(res?.import_summary), 'success');
    await load();
    const uid = res?.template?.uid;
    if (uid) router.push({ name: 'display-template-detail', params: { uid } });
  } catch (e) {
    importError.value = formatApiError(e, 'Import failed');
  } finally {
    savingImport.value = false;
  }
}

async function onDuplicate(item) {
  try {
    const t = await duplicateTemplate(item.uid);
    setMessage('Duplicated.', 'success');
    await load();
    if (t?.uid) router.push({ name: 'display-template-detail', params: { uid: t.uid } });
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

function isPublished(item) {
  return item?.status === 'published';
}

function canSetAsDefault(item) {
  return !!(item?.uid && !item.default && isPublished(item));
}

function defaultActionTitle(item) {
  if (item?.status === 'draft') return 'Draft templates cannot be set as default';
  if (item?.status === 'archived') return 'Archived templates cannot be set as default';
  if (item?.default) return 'Default template';
  return 'Set as default';
}

const defaultDlg = ref({ show: false, item: null, busy: false });

function onSetDefault(item) {
  if (!canSetAsDefault(item)) return;
  defaultDlg.value = { show: true, item, busy: false };
}

async function doSetDefault() {
  const item = defaultDlg.value.item;
  if (!canSetAsDefault(item)) return;
  defaultDlg.value.busy = true;
  try {
    await setDefaultTemplate(item.data_type, item.uid);
    defaultDlg.value.show = false;
    setMessage(`Default for ${item.data_type} set.`, 'success');
    await load();
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  } finally {
    defaultDlg.value.busy = false;
  }
}

const deleteDlg = ref({ show: false, item: null, busy: false });
function onDeletePrompt(item) {
  if (isSystemTemplate(item)) return;
  deleteDlg.value = { show: true, item, busy: false };
}
async function doDelete() {
  const item = deleteDlg.value.item;
  if (!item) return;
  deleteDlg.value.busy = true;
  try {
    await deleteTemplate(item.uid);
    deleteDlg.value.show = false;
    setMessage('Deleted.', 'success');
    await load();
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  } finally {
    deleteDlg.value.busy = false;
  }
}
</script>

<style scoped>
.dt-list-page {
  min-height: 0;
  background: transparent;
}

.dt-type-sidebar,
.dt-type-group-card {
  background: var(--dt-panel-bg, #fff) !important;
}

.dt-list-layout {
  display: grid;
  grid-template-columns: minmax(240px, 280px) minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}

.dt-list-sidebar {
  position: sticky;
  top: 12px;
}

.dt-type-sidebar :deep(.v-list-item--active) {
  font-weight: 600;
}

.dt-list-main {
  min-width: 0;
}

.dt-groups-stack {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.dt-type-group-card {
  overflow: hidden;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.dt-type-group-header {
  padding: 16px 20px;
  background: var(--dt-panel-bg, #fff);
}

.dt-type-group-empty {
  padding: 28px 20px;
  text-align: center;
}

.dt-type-table :deep(.v-data-table__td) {
  vertical-align: middle;
  padding-top: 10px !important;
  padding-bottom: 10px !important;
}

.dt-type-table :deep(.v-data-table__th) {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.62);
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.dt-type-table :deep(.v-table) {
  background: transparent;
}

.min-width-0 {
  min-width: 0;
}

@media (max-width: 959.98px) {
  .dt-list-layout {
    grid-template-columns: 1fr;
  }

  .dt-list-sidebar {
    position: static;
  }
}
</style>
