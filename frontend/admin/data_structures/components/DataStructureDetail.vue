<template>
  <div class="dsd-detail-page d-flex flex-column">
    <div class="d-flex flex-wrap align-center gap-2 mb-3 flex-shrink-0">
      <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="$emit('back')">Back</v-btn>
      <div class="mr-2" style="min-width: 0">
        <div class="text-h6 text-truncate">{{ structure.title || structure.name || 'Data structure' }}</div>
        <div class="text-body-2 text-medium-emphasis text-truncate">
          <code v-if="structure.idno">{{ structure.idno }}</code>
          <span v-else>ID {{ structure.id }}</span>
          <span class="mx-1">·</span>
          {{ structure.agency }} {{ structure.version }}
        </div>
      </div>
      <v-select
        v-if="versionPickerItems.length > 1"
        :model-value="Number(structure.id)"
        :items="versionPickerItems"
        :loading="versionsLoading"
        item-title="title"
        item-value="value"
        label="Version"
        density="compact"
        variant="outlined"
        hide-details
        style="max-width: 320px"
        class="flex-grow-0"
        @update:model-value="onVersionPick"
      />
      <v-btn
        v-if="isNonLatest && latestVersionId != null"
        variant="tonal"
        color="primary"
        size="small"
        prepend-icon="mdi-history"
        @click="goToLatestVersion"
      >
        Latest
      </v-btn>
      <v-spacer />
      <v-btn variant="tonal" prepend-icon="mdi-check-decagram" :loading="validateLoading" @click="runValidate">
        Validate
      </v-btn>
      <v-btn color="primary" prepend-icon="mdi-download" :loading="exportLoading" @click="downloadExport">Export JSON</v-btn>
      <v-btn color="error" variant="tonal" prepend-icon="mdi-delete" :disabled="isLocked" @click="deleteConfirm.show = true">Delete</v-btn>
    </div>

    <v-alert v-if="isNonLatest" type="info" variant="tonal" density="compact" class="mb-3 flex-shrink-0">
      You are viewing an older release of this data structure. Editing may be restricted if this row is published;
      switch version above or open the latest revision.
    </v-alert>

    <v-alert
      v-if="validateBanner"
      :type="validateBanner.ok ? 'success' : 'warning'"
      class="mb-3 flex-shrink-0"
      closable
      @click:close="validateBanner = null"
    >
      <template v-if="validateBanner.ok">Validation passed.</template>
      <template v-else>
        <div class="font-weight-medium mb-2">Validation issues</div>
        <ul class="pl-4 mb-0">
          <li v-for="(err, i) in validateBanner.errors" :key="i">
            <span v-if="err.path" class="text-caption">{{ err.path }}:</span>
            {{ err.message }}
          </li>
        </ul>
      </template>
    </v-alert>

    <!-- Resizable split: left = info + components nav; right = detail editor; fills viewport -->
    <div
      ref="splitRoot"
      class="dsd-split-root d-flex rounded elevation-1 flex-grow-1 flex-shrink-1"
      style="min-height: 0; overflow: hidden; border: 1px solid rgb(var(--v-theme-outline-variant))"
    >
      <!-- Left: data structure info + Components section -->
      <div
        class="dsd-split-list d-flex flex-column bg-surface min-height-0"
        :style="{
          flex: `0 0 ${splitW}px`,
          width: `${splitW}px`,
          minWidth: `${splitW}px`,
          maxWidth: `${splitW}px`,
          borderRight: '1px solid rgb(var(--v-theme-outline-variant))',
        }"
      >
        <v-list density="compact" class="py-0 flex-grow-1 overflow-y-auto min-height-0" nav>
          <v-list-item
            value="structure-info"
            :active="selectedComponentKey === null"
            color="primary"
            @click="goDataStructureInfo"
          >
            <template #prepend>
              <v-icon icon="mdi-file-document-edit-outline" size="small" class="mr-2" />
            </template>
            <v-list-item-title class="font-weight-medium">Data structure info</v-list-item-title>
            <v-list-item-subtitle>Identity & catalogue</v-list-item-subtitle>
          </v-list-item>

          <v-divider class="my-2" />

          <div
            class="px-3 py-2 d-flex align-center flex-wrap gap-1 flex-shrink-0"
            style="border-color: rgb(var(--v-theme-outline-variant))"
          >
            <span class="text-caption font-weight-medium text-medium-emphasis text-uppercase">Components</span>
            <v-chip size="x-small" variant="tonal">{{ componentsSorted.length }}</v-chip>
            <v-spacer />
            <v-btn size="x-small" variant="text" color="primary" prepend-icon="mdi-plus" :disabled="isLocked" @click="selectNewComponent">
              Add
            </v-btn>
          </div>

          <v-list-item
            v-for="comp in componentsSorted"
            :key="'c-' + comp.id"
            :value="Number(comp.id)"
            :active="isComponentRowActive(comp)"
            color="primary"
            @click="selectComponentRow(comp)"
          >
            <template #prepend>
              <v-icon icon="mdi-table-column" size="small" class="mr-2" />
            </template>
            <v-list-item-title class="font-weight-medium">{{ comp.name }}</v-list-item-title>
            <v-list-item-subtitle>{{ comp.column_type }}{{ comp.label ? ' · ' + comp.label : '' }}</v-list-item-subtitle>
            <template #append>
              <v-btn
                icon
                size="x-small"
                variant="text"
                color="error"
                title="Delete"
                :disabled="isLocked"
                @click.stop="confirmComponentDelete(comp)"
              >
                <v-icon size="small">mdi-delete-outline</v-icon>
              </v-btn>
            </template>
          </v-list-item>
          <v-list-item v-if="!componentsSorted.length" class="text-medium-emphasis">
            <v-list-item-title class="text-body-2">No components yet.</v-list-item-title>
            <v-list-item-subtitle>Use Add above to create the first column.</v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </div>

      <!-- Splitter -->
      <div
        class="dsd-split-gutter flex-shrink-0"
        style="width: 6px; cursor: col-resize; background: linear-gradient(to right, rgba(0,0,0,0.06), rgba(0,0,0,0.02))"
        role="separator"
        aria-orientation="vertical"
        tabindex="0"
        title="Drag to resize"
        @mousedown="onSplitMouseDown"
        @keydown.left.prevent="nudgeSplit(-16)"
        @keydown.right.prevent="nudgeSplit(16)"
      />

      <!-- Right: catalogue overview or component editor -->
      <div class="flex-grow-1 d-flex flex-column bg-surface overflow-hidden min-width-0 min-height-0">
        <div
          class="pa-3 border-b d-flex align-center flex-wrap gap-2 flex-shrink-0"
          style="border-color: rgb(var(--v-theme-outline-variant))"
        >
          <span v-if="rightTitle" class="text-subtitle-1 font-weight-medium">{{ rightTitle }}</span>
          <v-spacer />
          <v-chip v-if="rightSubtitle" size="small" variant="tonal">{{ rightSubtitle }}</v-chip>
        </div>

        <div class="pa-4 flex-grow-1 overflow-y-auto min-height-0">
          <v-alert
            v-if="isLocked"
            type="error"
            variant="outlined"
            density="comfortable"
            icon="mdi-lock"
            class="mb-4"
          >
            <div class="font-weight-medium mb-2">Locked revision</div>
            <div class="text-body-2 mb-3">Fields are read-only while status is published or archived.</div>
            <div class="d-flex flex-wrap align-end gap-2">
              <div style="min-width: 220px">
                <div class="text-caption text-medium-emphasis mb-1">Change status</div>
                <v-select
                  v-model="lockedStatusDraft"
                  :items="statusOptions"
                  item-title="title"
                  item-value="value"
                  :disabled="statusSaving"
                  density="compact"
                  variant="outlined"
                  hide-details
                />
              </div>
              <v-btn
                color="primary"
                class="ml-2"
                :loading="statusSaving"
                :disabled="statusSaving || lockedStatusDraft == null || Number(lockedStatusDraft) === Number(structure.status)"
                @click="changeLockedStatus"
              >
                Update
              </v-btn>
            </div>
          </v-alert>

          <!-- Catalogue fields with read-only identity -->
          <template v-if="selectedComponentKey === null">
            <v-row dense>
              <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis mb-1">ID</div>
                <v-text-field :model-value="structure.id" readonly density="compact" variant="outlined" hide-details />
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis mb-1">Name</div>
                <v-text-field
                  v-model="structureDraft.name"
                  :readonly="isLocked"
                  :disabled="structureSaving"
                  density="compact"
                  variant="outlined"
                  hide-details
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis mb-1">Agency</div>
                <v-text-field
                  v-model="structureDraft.agency"
                  :readonly="isLocked"
                  :disabled="structureSaving"
                  density="compact"
                  variant="outlined"
                  hide-details
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis mb-1">Version</div>
                <v-text-field
                  v-model="structureDraft.version"
                  :readonly="isLocked"
                  :disabled="structureSaving"
                  density="compact"
                  variant="outlined"
                  hide-details
                />
              </v-col>
            </v-row>
            <div class="text-caption text-medium-emphasis mt-3 mb-1">IDNO</div>
            <v-text-field
              v-model="structureDraft.idno"
              hint="Leave blank and save to regenerate from agency, name, and version."
              persistent-hint
              :disabled="structureSaving"
              :readonly="isLocked"
              density="compact"
              variant="outlined"
            />
            <div class="text-caption text-medium-emphasis mt-3 mb-1">Title</div>
            <v-text-field
              v-model="structureDraft.title"
              :disabled="structureSaving"
              :readonly="isLocked"
              density="compact"
              variant="outlined"
              hide-details
            />
            <div class="text-caption text-medium-emphasis mt-3 mb-1">Status</div>
            <v-select
              v-model="structureDraft.status"
              :items="statusOptions"
              item-title="title"
              item-value="value"
              :disabled="structureSaving"
              :readonly="isLocked"
              density="compact"
              variant="outlined"
              hide-details
            />
            <div class="text-caption text-medium-emphasis mt-3 mb-1">Description</div>
            <v-textarea
              v-model="structureDraft.description"
              :disabled="structureSaving"
              :readonly="isLocked"
              rows="3"
              density="compact"
              variant="outlined"
              auto-grow
              hide-details
            />
            <div class="text-caption text-medium-emphasis mt-3 mb-1">Notes</div>
            <v-textarea
              v-model="structureDraft.notes"
              :disabled="structureSaving"
              :readonly="isLocked"
              rows="2"
              density="compact"
              variant="outlined"
              auto-grow
            />
            <div class="d-flex justify-end mt-4">
              <v-btn color="primary" :loading="structureSaving" :disabled="isLocked" @click="saveStructureCatalogue">Save catalogue</v-btn>
            </div>
          </template>

          <!-- Component editor -->
          <template v-else>
            <DataStructureComponentEditorPanel
              :structure-id="structure.id"
              :component="selectedComponent"
              :read-only="isLocked"
              @saved="onComponentFormSaved"
              @cancel="goDataStructureInfo"
            />
          </template>
        </div>
      </div>
    </div>

    <v-dialog v-model="deleteConfirm.show" max-width="420" persistent>
      <v-card>
        <v-card-title>Delete data structure?</v-card-title>
        <v-card-text>This removes all components. This cannot be undone.</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteConfirm.show = false">Cancel</v-btn>
          <v-btn color="error" :loading="deleteConfirm.loading" @click="doDeleteStructure">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="compDelete.show" max-width="400" persistent>
      <v-card>
        <v-card-title>Delete component?</v-card-title>
        <v-card-text>Remove <strong>{{ compDelete.row?.name }}</strong>?</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="compDelete.show = false">Cancel</v-btn>
          <v-btn color="error" :loading="compDelete.loading" @click="doDeleteComponent">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, inject, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import DataStructureComponentEditorPanel from './DataStructureComponentEditorPanel.vue';
import { useDataStructuresApi } from '../composables/useDataStructuresApi';

defineOptions({ name: 'DataStructureDetail' });

const props = defineProps({
  structure: { type: Object, required: true },
  loading: Boolean,
});

const emit = defineEmits(['back', 'refresh', 'deleted']);

const setMessage = inject('setMessage', () => {});
const router = useRouter();

const {
  exportDataStructure,
  validatePayload,
  deleteDataStructure,
  deleteComponent,
  updateDataStructure,
  updateDataStructureStatus,
  createComponent,
  updateComponent,
  fetchStructureVersions,
} = useDataStructuresApi();

/** Rows returned by GET …/versions/{id-or-idno}; sorted for display and picker. */
const versionsList = ref([]);
const versionsLoading = ref(false);

const SPLIT_LS = 'admin_data_structure_detail_split_w';

const splitRoot = ref(null);
const splitW = ref(300);
/** null = data structure info; 'new' = new component; number = component id */
const selectedComponentKey = ref(null);
const structureDraft = reactive({
  name: '',
  agency: '',
  version: '',
  idno: '',
  title: '',
  status: 0,
  description: '',
  notes: '',
});
const structureSaving = ref(false);
const statusSaving = ref(false);
const lockedStatusDraft = ref(null);

const deleteConfirm = reactive({ show: false, loading: false });
const compDelete = reactive({ show: false, loading: false, row: null });
const exportLoading = ref(false);
const validateLoading = ref(false);
const validateBanner = ref(null);
const statusOptions = [
  { title: 'Draft', value: 0 },
  { title: 'Review', value: 10 },
  { title: 'Published', value: 20 },
  { title: 'Deprecated', value: 30 },
  { title: 'Archived', value: 40 },
];
const isLocked = computed(() => {
  const status = Number(props.structure?.status);
  return status === 20 || status === 40;
});

const isNonLatest = computed(() => {
  const s = props.structure;
  if (!s) return false;
  if (s.pid == null || s.pid === '') return false;
  const id = Number(s.id);
  const pid = Number(s.pid);
  if (!Number.isFinite(id) || !Number.isFinite(pid)) return false;
  return id !== pid;
});

const latestVersionId = computed(() => {
  const pid = Number(props.structure?.pid);
  return Number.isFinite(pid) ? pid : null;
});

const versionPickerItems = computed(() => {
  const rows = versionsList.value;
  if (!Array.isArray(rows) || !rows.length) return [];
  const sorted = [...rows].sort((a, b) => {
    const sa = Number(a.version_seq) || 0;
    const sb = Number(b.version_seq) || 0;
    if (sa !== sb) return sb - sa;
    return Number(b.id) - Number(a.id);
  });
  return sorted.map((v) => ({
    value: Number(v.id),
    title: `${v.version ?? '—'} · ${statusShort(v.status)}`,
  }));
});

function statusShort(code) {
  const n = Number(code);
  if (n === 0) return 'Draft';
  if (n === 10) return 'Review';
  if (n === 20) return 'Published';
  if (n === 30) return 'Deprecated';
  if (n === 40) return 'Archived';
  return String(code ?? '');
}

function onVersionPick(id) {
  if (id == null || Number(id) === Number(props.structure?.id)) return;
  router.replace({ name: 'data-structure-detail', params: { id: String(id) } });
}

function goToLatestVersion() {
  const id = latestVersionId.value;
  if (id == null) return;
  onVersionPick(id);
}

async function loadVersionFamily() {
  const s = props.structure;
  if (!s?.id) {
    versionsList.value = [];
    return;
  }
  versionsLoading.value = true;
  try {
    const rows = await fetchStructureVersions(s.id);
    versionsList.value = Array.isArray(rows) ? rows : [];
  } catch {
    versionsList.value = [];
  } finally {
    versionsLoading.value = false;
  }
}

let splitMove = null;
let splitUp = null;

const componentsSorted = computed(() => {
  const list = props.structure?.components;
  if (!Array.isArray(list)) return [];
  return [...list].sort((a, b) => {
    const oa = Number(a.sort_order) || 0;
    const ob = Number(b.sort_order) || 0;
    if (oa !== ob) return oa - ob;
    return Number(a.id) - Number(b.id);
  });
});

const selectedComponent = computed(() => {
  if (selectedComponentKey.value === 'new') return null;
  if (selectedComponentKey.value == null) return null;
  const id = Number(selectedComponentKey.value);
  if (!Number.isFinite(id)) return null;
  return componentsSorted.value.find((c) => Number(c.id) === id) || null;
});

const rightTitle = computed(() => {
  if (selectedComponentKey.value === null) return 'Data structure info';
  if (selectedComponentKey.value === 'new') return 'New component';
  return selectedComponent.value?.name || 'Component';
});

const rightSubtitle = computed(() => {
  if (selectedComponentKey.value != null && selectedComponentKey.value !== 'new') {
    return selectedComponent.value?.column_type || '';
  }
  return '';
});

function syncStructureDraft() {
  const s = props.structure;
  if (!s) return;
  structureDraft.idno = s.idno ?? '';
  structureDraft.name = s.name ?? '';
  structureDraft.agency = s.agency ?? '';
  structureDraft.version = s.version ?? '';
  structureDraft.title = s.title ?? '';
  structureDraft.status = Number.isFinite(Number(s.status)) ? Number(s.status) : 0;
  lockedStatusDraft.value = structureDraft.status;
  structureDraft.description = s.description ?? '';
  structureDraft.notes = s.notes ?? '';
}

watch(
  () => props.structure,
  () => {
    syncStructureDraft();
  },
  { deep: true, immediate: true }
);

watch(
  () => props.structure?.id,
  () => {
    selectedComponentKey.value = null;
  }
);

watch(
  () => [props.structure?.name, props.structure?.agency],
  () => {
    loadVersionFamily();
  },
  { immediate: true }
);

watch(selectedComponentKey, (k) => {
  if (k === null || k === 'new') return;
  const id = Number(k);
  if (!Number.isFinite(id) || !selectedComponent.value) {
    selectedComponentKey.value = null;
  }
});

function clampSplit(w) {
  const root = splitRoot.value;
  const rootW = root?.getBoundingClientRect().width || 960;
  const maxW = Math.max(260, Math.floor(rootW * 0.55));
  return Math.min(maxW, Math.max(220, w));
}

function nudgeSplit(delta) {
  splitW.value = clampSplit(splitW.value + delta);
  try {
    localStorage.setItem(SPLIT_LS, String(splitW.value));
  } catch {
    /* ignore */
  }
}

function onSplitMouseDown(e) {
  if (e.button !== 0) return;
  e.preventDefault();
  splitDragEnd(false);
  const startX = e.clientX;
  const startW = splitW.value;
  splitMove = (ev) => {
    splitW.value = clampSplit(startW + (ev.clientX - startX));
  };
  splitUp = () => {
    splitDragEnd(true);
  };
  document.addEventListener('mousemove', splitMove);
  document.addEventListener('mouseup', splitUp);
  document.body.style.cursor = 'col-resize';
  document.body.style.userSelect = 'none';
}

function splitDragEnd(persist) {
  if (splitMove) document.removeEventListener('mousemove', splitMove);
  if (splitUp) document.removeEventListener('mouseup', splitUp);
  splitMove = null;
  splitUp = null;
  document.body.style.cursor = '';
  document.body.style.userSelect = '';
  if (persist) {
    try {
      localStorage.setItem(SPLIT_LS, String(splitW.value));
    } catch {
      /* ignore */
    }
  }
}

onMounted(() => {
  try {
    const n = parseInt(localStorage.getItem(SPLIT_LS) || '', 10);
    if (!Number.isNaN(n) && n >= 220 && n <= 800) splitW.value = n;
  } catch {
    /* ignore */
  }
});

onUnmounted(() => {
  splitDragEnd(false);
});

function selectNewComponent() {
  if (isLocked.value) {
    setMessage('This revision is locked (published/archived). Create a new version to edit components.', 'warning');
    return;
  }
  selectedComponentKey.value = 'new';
}

function selectComponentRow(comp) {
  const id = Number(comp?.id);
  selectedComponentKey.value = Number.isFinite(id) ? id : null;
}

function isComponentRowActive(comp) {
  if (selectedComponentKey.value === null || selectedComponentKey.value === 'new') return false;
  return Number(selectedComponentKey.value) === Number(comp?.id);
}

function goDataStructureInfo() {
  selectedComponentKey.value = null;
}

function confirmComponentDelete(row) {
  if (isLocked.value) {
    setMessage('This revision is locked (published/archived). Create a new version to edit components.', 'warning');
    return;
  }
  compDelete.row = row;
  compDelete.show = true;
}

async function doDeleteComponent() {
  if (isLocked.value) {
    setMessage('This revision is locked (published/archived). Create a new version to edit components.', 'warning');
    return;
  }
  if (!compDelete.row) return;
  compDelete.loading = true;
  try {
    await deleteComponent(compDelete.row.id);
    setMessage('Component deleted.', 'success');
    compDelete.show = false;
    if (Number(selectedComponentKey.value) === Number(compDelete.row.id)) {
      selectedComponentKey.value = null;
    }
    emit('refresh');
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Delete failed', 'error');
  } finally {
    compDelete.loading = false;
  }
}

async function saveStructureCatalogue() {
  if (isLocked.value) {
    setMessage('This revision is locked. Use the status control above to unlock editing.', 'warning');
    return;
  }
  structureSaving.value = true;
  try {
    await updateDataStructure(props.structure.id, {
      name: structureDraft.name,
      agency: structureDraft.agency,
      version: structureDraft.version,
      idno: structureDraft.idno,
      title: structureDraft.title,
      status: structureDraft.status,
      description: structureDraft.description,
      notes: structureDraft.notes,
    });
    setMessage('Catalogue saved.', 'success');
    emit('refresh');
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Save failed', 'error');
  } finally {
    structureSaving.value = false;
  }
}

async function changeLockedStatus() {
  if (!isLocked.value) return;
  if (lockedStatusDraft.value == null) return;
  if (Number(lockedStatusDraft.value) === Number(props.structure?.status)) return;
  statusSaving.value = true;
  try {
    await updateDataStructureStatus(props.structure.id, Number(lockedStatusDraft.value));
    setMessage('Status updated.', 'success');
    emit('refresh');
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Status update failed', 'error');
  } finally {
    statusSaving.value = false;
  }
}

async function onComponentFormSaved(evt) {
  if (isLocked.value) {
    setMessage('This revision is locked (published/archived). Create a new version to edit components.', 'warning');
    return;
  }
  try {
    if (evt.isEdit && evt.componentId != null) {
      await updateComponent(evt.componentId, evt.payload);
      setMessage('Component updated.', 'success');
      selectedComponentKey.value = Number(evt.componentId);
      emit('refresh');
    } else if (!evt.isEdit && evt.structureId != null) {
      const created = await createComponent(evt.structureId, evt.payload);
      setMessage('Component created.', 'success');
      selectedComponentKey.value = created?.id != null ? Number(created.id) : null;
      emit('refresh');
    }
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Save failed', 'error');
  }
}

async function downloadExport() {
  exportLoading.value = true;
  try {
    const exp = await exportDataStructure(props.structure.id);
    const blob = new Blob([JSON.stringify(exp, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `data_structure_${props.structure.idno || props.structure.id}.json`;
    a.click();
    URL.revokeObjectURL(url);
  } finally {
    exportLoading.value = false;
  }
}

async function runValidate() {
  validateLoading.value = true;
  validateBanner.value = null;
  try {
    const res = await validatePayload({ data_structure_id: props.structure.id });
    const errs = res.errors || [];
    validateBanner.value = { ok: res.valid && errs.length === 0, errors: errs };
  } catch (e) {
    validateBanner.value = { ok: false, errors: [{ path: '', message: e?.message || 'Validate failed' }] };
  } finally {
    validateLoading.value = false;
  }
}

async function doDeleteStructure() {
  deleteConfirm.loading = true;
  try {
    await deleteDataStructure(props.structure.id);
    setMessage('Data structure deleted.', 'success');
    deleteConfirm.show = false;
    emit('deleted');
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Delete failed', 'error');
  } finally {
    deleteConfirm.loading = false;
  }
}
</script>

<style scoped>
/* Fill available height from App → router-view → page → this root */
.dsd-detail-page {
  display: flex;
  flex-direction: column;
  min-height: 0;
  flex: 1 1 auto;
}
</style>
