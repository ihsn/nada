<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { extractApiError } from '../composables/apiErrors';
import { useDepositApi } from '../composables/useDepositApi';

const MAX_JSON_BYTES = 2 * 1024 * 1024;

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'imported']);

const { config } = useAppConfig();
const { fetchImportSources, importMetadata, importMetadataJson } = useDepositApi();

const mode = ref('project');
const query = ref('');
const loading = ref(false);
const importing = ref(false);
const loadError = ref('');
const importError = ref('');
const fileError = ref('');
const projects = ref([]);
const sourceProjectId = ref(null);
const jsonDoc = ref(null);
const jsonFileName = ref('');
let searchTimer = null;

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    importMetadata: labels.importMetadata || 'Import metadata',
    importMetadataHelp:
      labels.importMetadataHelp ||
      'This replaces the current study description and cannot be undone.',
    importFromProject: labels.importFromProject || 'From a project',
    importFromFile: labels.importFromFile || 'From a JSON file',
    importMetadataSearch: labels.importMetadataSearch || 'Search by title, short name, or project ID',
    importMetadataHint: labels.importMetadataHint || 'Type at least two characters, or a project ID.',
    importMetadataEmpty: labels.importMetadataEmpty || 'No matching projects of this type.',
    importMetadataFile: labels.importMetadataFile || 'JSON file',
    importMetadataFileHelp:
      labels.importMetadataFileHelp ||
      'Use a Metadata (JSON) or Project (JSON) export, or a Metadata Editor JSON of the same type.',
    importMetadataFileInvalid: labels.importMetadataFileInvalid || 'That file is not valid JSON.',
    importMetadataFileLarge: labels.importMetadataFileLarge || 'File is too large (maximum 2 MB).',
    importMetadataConfirm:
      labels.importMetadataConfirm ||
      'This will import the metadata from the selected project into the current study description. This action may not be undone.',
    importMetadataRun: labels.importMetadataRun || 'Import',
    importMetadataFailed: labels.importMetadataFailed || 'We could not import the metadata successfully!',
    cancel: labels.cancel || 'Cancel',
    loadFailed: labels.loadFailed || 'Failed to load',
  };
});

const modeItems = computed(() => [
  { value: 'project', title: lbl.value.importFromProject },
  { value: 'file', title: lbl.value.importFromFile },
]);

const open = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const queryReady = computed(() => {
  const q = query.value.trim();
  if (!q) return false;
  if (/^\d+$/.test(q)) return true;
  return q.length >= 2;
});

const canImport = computed(() => {
  if (props.disabled || importing.value) return false;
  if (mode.value === 'file') return !!jsonDoc.value;
  return !!sourceProjectId.value;
});

function projectLabel(row) {
  const title = String(row.title || '').trim() || `#${row.id}`;
  const shortname = String(row.shortname || '').trim();
  return shortname ? `${title} (${shortname})` : title;
}

function resetState() {
  mode.value = 'project';
  query.value = '';
  projects.value = [];
  sourceProjectId.value = null;
  jsonDoc.value = null;
  jsonFileName.value = '';
  loadError.value = '';
  importError.value = '';
  fileError.value = '';
  loading.value = false;
}

function close() {
  if (importing.value) return;
  open.value = false;
}

async function runSearch(q) {
  const term = String(q || '').trim();
  if (!term || (!/^\d+$/.test(term) && term.length < 2)) {
    projects.value = [];
    loading.value = false;
    return;
  }
  loading.value = true;
  loadError.value = '';
  try {
    const data = await fetchImportSources(term);
    projects.value = Array.isArray(data.projects) ? data.projects : [];
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    loadError.value = extracted.message || lbl.value.loadFailed;
    projects.value = [];
  } finally {
    loading.value = false;
  }
}

function scheduleSearch() {
  if (searchTimer) window.clearTimeout(searchTimer);
  sourceProjectId.value = null;
  searchTimer = window.setTimeout(() => {
    runSearch(query.value);
  }, 300);
}

async function onFileChange(files) {
  fileError.value = '';
  jsonDoc.value = null;
  jsonFileName.value = '';
  const file = Array.isArray(files) ? files[0] : files;
  if (!file) return;
  if (file.size > MAX_JSON_BYTES) {
    fileError.value = lbl.value.importMetadataFileLarge;
    return;
  }
  try {
    const text = await file.text();
    const parsed = JSON.parse(text);
    if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
      fileError.value = lbl.value.importMetadataFileInvalid;
      return;
    }
    jsonDoc.value = parsed;
    jsonFileName.value = file.name || 'file.json';
  } catch (e) {
    fileError.value = lbl.value.importMetadataFileInvalid;
  }
}

async function onImport() {
  if (!canImport.value) return;
  if (!window.confirm(lbl.value.importMetadataConfirm)) return;
  importing.value = true;
  importError.value = '';
  try {
    const data =
      mode.value === 'file'
        ? await importMetadataJson(jsonDoc.value)
        : await importMetadata(sourceProjectId.value);
    const metadata = data.metadata && typeof data.metadata === 'object' ? data.metadata : {};
    emit('imported', metadata);
    open.value = false;
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    importError.value = extracted.message || lbl.value.importMetadataFailed;
  } finally {
    importing.value = false;
  }
}

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) resetState();
  }
);

watch(mode, () => {
  importError.value = '';
  loadError.value = '';
  fileError.value = '';
  sourceProjectId.value = null;
  jsonDoc.value = null;
  jsonFileName.value = '';
});

onBeforeUnmount(() => {
  if (searchTimer) window.clearTimeout(searchTimer);
});
</script>

<template>
  <v-dialog v-model="open" max-width="560" :persistent="importing">
    <v-card>
      <v-card-title class="text-subtitle-1">{{ lbl.importMetadata }}</v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-4">{{ lbl.importMetadataHelp }}</p>
        <v-btn-toggle
          v-model="mode"
          :multiple="false"
          mandatory
          density="compact"
          color="primary"
          variant="outlined"
          class="mb-4"
          :disabled="importing || disabled"
        >
          <v-btn value="project" class="text-none" size="small">{{ lbl.importFromProject }}</v-btn>
          <v-btn value="file" class="text-none" size="small">{{ lbl.importFromFile }}</v-btn>
        </v-btn-toggle>

        <template v-if="mode === 'project'">
          <div class="dd-import-field">
            <label class="dd-import-label" for="dd-import-search">{{ lbl.importMetadataSearch }}</label>
            <v-text-field
              id="dd-import-search"
              v-model="query"
              :disabled="importing || disabled"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-magnify"
              clearable
              hide-details
              @update:model-value="scheduleSearch"
            />
            <div class="dd-import-hint">{{ lbl.importMetadataHint }}</div>
          </div>
          <div v-if="loading" class="d-flex justify-center py-4">
            <v-progress-circular indeterminate color="primary" size="28" />
          </div>
          <v-alert v-else-if="loadError" type="error" variant="tonal" density="compact" class="mt-3">
            {{ loadError }}
          </v-alert>
          <v-alert
            v-else-if="queryReady && !projects.length"
            type="info"
            variant="tonal"
            density="compact"
            class="mt-3"
          >
            {{ lbl.importMetadataEmpty }}
          </v-alert>
          <v-list
            v-else-if="projects.length"
            class="dd-import-results mt-2"
            density="compact"
            selectable
          >
            <v-list-item
              v-for="row in projects"
              :key="row.id"
              :active="sourceProjectId === row.id"
              :title="projectLabel(row)"
              :subtitle="`#${row.id}`"
              @click="sourceProjectId = row.id"
            />
          </v-list>
        </template>

        <template v-else>
          <div class="dd-import-field">
            <label class="dd-import-label" for="dd-import-file">{{ lbl.importMetadataFile }}</label>
            <p class="dd-import-hint dd-import-hint--before">{{ lbl.importMetadataFileHelp }}</p>
            <v-file-input
              id="dd-import-file"
              accept=".json,application/json"
              :disabled="importing || disabled"
              variant="outlined"
              density="compact"
              prepend-icon=""
              prepend-inner-icon="mdi-file-json-outline"
              show-size
              clearable
              hide-details
              @update:model-value="onFileChange"
            />
          </div>
          <v-alert v-if="fileError" type="error" variant="tonal" density="compact" class="mt-3">
            {{ fileError }}
          </v-alert>
        </template>

        <v-alert v-if="importError" type="error" variant="tonal" density="compact" class="mt-3">
          {{ importError }}
        </v-alert>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" class="text-none" :disabled="importing" @click="close">
          {{ lbl.cancel }}
        </v-btn>
        <v-btn
          color="primary"
          class="text-none"
          :loading="importing"
          :disabled="!canImport"
          @click="onImport"
        >
          {{ lbl.importMetadataRun }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.dd-import-field {
  margin-bottom: 4px;
}
.dd-import-label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.87);
}
.dd-import-hint {
  margin-top: 6px;
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
  line-height: 1.35;
}
.dd-import-hint--before {
  margin-top: 0;
  margin-bottom: 8px;
}
.dd-import-results {
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 8px;
}
</style>
