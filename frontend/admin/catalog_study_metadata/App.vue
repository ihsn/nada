<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import MetadataFormEditor from '@/shared/metadata-form/components/MetadataFormEditor.vue';
import { pruneEmpty } from '@/shared/metadata-form/composables/createMetadataFormStore';
import { extractApiError } from './composables/apiErrors';
import { dsdReferenceForMetadataSave } from './composables/dsdReference';
import { useStudyMetadataApi } from './composables/useStudyMetadataApi';
import { useUnsavedChangesGuard } from './composables/useUnsavedChangesGuard';

const { config } = useAppConfig();
const { fetchMetadata, saveMetadata } = useStudyMetadataApi();

const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const saveError = ref('');
/** @type {import('vue').Ref<Array<{ property: string, message: string }>>} */
const saveErrors = ref([]);
const snackbar = ref({ show: false, text: '', color: 'success' });
const formRef = ref(null);
const saveAlertEl = ref(null);
const metadata = ref({});
/**
 * DSD attach is owned by the Indicator data tab (attach-dsd).
 * Keep the link out of the metadata form; re-inject on save so a partial
 * payload cannot drop surveys.metadata.data_structure_reference.
 */
const preservedDsdReference = ref(null);

const { isDirty, markClean, confirmReload } = useUnsavedChangesGuard({
  getCurrent: () => formRef.value?.getPayload?.() || {},
  getWatchSource: () => formRef.value?.store?.state?.data,
  isEnabled: () => !loading.value,
  leaveMessage: () => lbl.value.unsavedLeave,
  reloadMessage: () => lbl.value.unsavedReload,
});

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    metadataTab: labels.metadataTab || 'Metadata',
    template: labels.template || 'Template',
    reload: labels.reload || 'Reload',
    save: labels.save || 'Save',
    saveUnsaved: labels.saveUnsaved || 'Save *',
    saved: labels.saved || 'Metadata saved',
    loadFailed: labels.loadFailed || 'Failed to load metadata',
    saveFailed: labels.saveFailed || 'Save failed',
    unsavedLeave: labels.unsavedLeave || 'You have unsaved changes. Leave this page?',
    unsavedReload: labels.unsavedReload || 'You have unsaved changes. Reload and discard them?',
    validationFailed: labels.validationFailed || 'Validation failed',
    schemaValidationFailed: labels.schemaValidationFailed || 'Schema validation failed',
    requestFailed: labels.requestFailed || 'Request failed',
    selectSection: labels.selectSection || 'Select a section or field from the tree.',
    addFromList: labels.addFromList || 'Add from list',
    addRow: labels.addRow || 'Add row',
    add: labels.add || 'Add',
    deleteRow: labels.deleteRow || 'Delete row',
    deleteRowConfirm: labels.deleteRowConfirm || 'Delete this row?',
    noRows: labels.noRows || 'No rows yet. Use “Add row” below.',
    noItems: labels.noItems || 'No items yet.',
    showHelp: labels.showHelp || 'Show help',
    hideHelp: labels.hideHelp || 'Hide help',
    containerOverview:
      labels.containerOverview ||
      'Overview of this group. Select a section in the tree (or below) to edit fields.',
    sectionsInGroup: labels.sectionsInGroup || 'Sections in this group',
    nothingEntered: labels.nothingEntered || 'Nothing entered here yet. Open a section to add metadata.',
    noFields: labels.noFields || 'This section has no fields.',
    noPreview:
      labels.noPreview ||
      'No metadata entered in this group yet. Open a section in the tree to start editing.',
    editSection: labels.editSection || 'Edit section',
    treeNav: labels.treeNav || 'Metadata form navigation',
    item: labels.item || 'Item',
    trueLabel: labels.trueLabel || 'True',
    falseLabel: labels.falseLabel || 'False',
  };
});

const saveLabel = computed(() => (isDirty.value ? lbl.value.saveUnsaved : lbl.value.save));

const formLabels = computed(() => {
  const l = lbl.value;
  return {
    selectSection: l.selectSection,
    addFromList: l.addFromList,
    addRow: l.addRow,
    add: l.add,
    deleteRow: l.deleteRow,
    deleteRowConfirm: l.deleteRowConfirm,
    noRows: l.noRows,
    noItems: l.noItems,
    showHelp: l.showHelp,
    hideHelp: l.hideHelp,
    containerOverview: l.containerOverview,
    sectionsInGroup: l.sectionsInGroup,
    nothingEntered: l.nothingEntered,
    noFields: l.noFields,
    noPreview: l.noPreview,
    editSection: l.editSection,
    treeNav: l.treeNav,
    item: l.item,
    trueLabel: l.trueLabel,
    falseLabel: l.falseLabel,
  };
});

const formTemplate = computed(() => config.value?.formTemplate || { items: [] });
const templateName = computed(
  () => config.value?.templateName || formTemplate.value?.title || ''
);

const errorLabels = computed(() => ({
  validationFailed: lbl.value.validationFailed,
  schemaValidationFailed: lbl.value.schemaValidationFailed,
  saveFailed: lbl.value.saveFailed,
  requestFailed: lbl.value.requestFailed,
}));

function showSnack(text, color = 'success') {
  snackbar.value = { show: true, text, color };
}

function clearSaveErrors() {
  saveError.value = '';
  saveErrors.value = [];
}

/** Form-editable metadata only (exclude operational / separately managed fields). */
function toFormMetadata(meta) {
  const next = meta && typeof meta === 'object' ? { ...meta } : {};
  delete next.data_structure_reference;

  const studyType = config.value?.studyType || '';
  if (studyType === 'geospatial' && next.description && typeof next.description === 'object') {
    const description = { ...next.description };
    delete description.feature_catalogue;
    next.description = description;
  }

  return next;
}

/** Build merge payload: form fields + preserved DSD reference when available. */
async function buildSavePayload(formPayload) {
  const pruned = pruneEmpty(formPayload) || {};
  delete pruned.data_structure_reference;

  let ref = preservedDsdReference.value;
  try {
    const latest = await fetchMetadata();
    if (latest && Object.prototype.hasOwnProperty.call(latest, 'data_structure_reference')) {
      ref = latest.data_structure_reference;
      preservedDsdReference.value = ref ?? null;
    }
  } catch {
    /* keep preservedDsdReference from last successful load */
  }

  if (ref !== undefined && ref !== null && ref !== '') {
    const forSave = dsdReferenceForMetadataSave(ref);
    if (forSave) {
      pruned.data_structure_reference = forSave;
    }
  }
  return pruned;
}

async function revealSaveErrors() {
  await nextTick();
  saveAlertEl.value?.scrollIntoView?.({ behavior: 'smooth', block: 'nearest' });
}

async function load() {
  loading.value = true;
  loadError.value = '';
  clearSaveErrors();
  try {
    const meta = await fetchMetadata();
    const full = meta && typeof meta === 'object' ? meta : {};
    preservedDsdReference.value = Object.prototype.hasOwnProperty.call(full, 'data_structure_reference')
      ? full.data_structure_reference
      : null;
    metadata.value = toFormMetadata(full);
    formRef.value?.replaceData?.(metadata.value);
    await nextTick();
    markClean();
  } catch (e) {
    const extracted = extractApiError(e, errorLabels.value);
    loadError.value = extracted.message || lbl.value.loadFailed;
  } finally {
    loading.value = false;
  }
}

async function onReload() {
  if (!confirmReload()) return;
  await load();
}

async function onSave() {
  saving.value = true;
  clearSaveErrors();
  try {
    const raw = formRef.value?.getPayload?.() || {};
    const payload = await buildSavePayload(raw);
    await saveMetadata(payload);
    const latest = await fetchMetadata();
    const full = latest && typeof latest === 'object' ? latest : {};
    preservedDsdReference.value = Object.prototype.hasOwnProperty.call(full, 'data_structure_reference')
      ? full.data_structure_reference
      : null;
    metadata.value = toFormMetadata(full);
    formRef.value?.replaceData?.(metadata.value);
    await nextTick();
    markClean();
    showSnack(lbl.value.saved, 'success');
  } catch (e) {
    const extracted = extractApiError(e, errorLabels.value);
    saveError.value = extracted.message || lbl.value.saveFailed;
    saveErrors.value = extracted.errors || [];
    showSnack(saveError.value, 'error');
    await revealSaveErrors();
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<template>
  <v-app class="csm-app">
    <v-main class="csm-main">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
        {{ snackbar.text }}
      </v-snackbar>

      <div class="csm-shell">
        <div class="csm-toolbar d-flex align-center justify-space-between flex-wrap ga-2">
          <p class="csm-context text-body-2 mb-0">
            <span class="csm-context-tab font-weight-semibold">{{ lbl.metadataTab }}</span>
            <template v-if="templateName">
              <span class="csm-context-sep" aria-hidden="true">·</span>
              <span class="csm-context-item">
                <span class="csm-context-label">{{ lbl.template }}:</span>
                <span class="csm-context-value">{{ templateName }}</span>
              </span>
            </template>
          </p>
          <div class="d-flex ga-2">
            <v-btn
              variant="tonal"
              class="text-none"
              :disabled="loading || saving"
              @click="onReload"
            >
              {{ lbl.reload }}
            </v-btn>
            <v-btn
              color="primary"
              class="text-none"
              :loading="saving"
              :disabled="loading"
              @click="onSave"
            >
              {{ saveLabel }}
            </v-btn>
          </div>
        </div>

        <v-alert v-if="loadError" type="error" variant="tonal" class="ma-2" density="compact" closable>
          {{ loadError }}
        </v-alert>

        <div v-if="saveError" ref="saveAlertEl" class="csm-save-errors ma-2">
          <v-alert type="error" variant="tonal" density="compact" closable @click:close="clearSaveErrors">
            <div class="font-weight-medium">{{ saveError }}</div>
            <div v-if="saveErrors.length" class="csm-save-errors-list mt-2">
              <div
                v-for="(err, i) in saveErrors"
                :key="i"
                class="csm-save-error-item"
              >
                <div class="csm-save-error-msg">{{ err.message }}</div>
                <div v-if="err.property" class="csm-save-error-path text-caption">
                  {{ err.property }}
                </div>
              </div>
            </div>
          </v-alert>
        </div>

        <div v-if="loading" class="d-flex justify-center py-12">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <div v-show="!loading" class="csm-editor-wrap">
          <MetadataFormEditor
            ref="formRef"
            v-model="metadata"
            :form-template="formTemplate"
            :labels="formLabels"
            :show-tree="true"
          />
        </div>
      </div>
    </v-main>
  </v-app>
</template>

<style scoped>
.csm-app.v-application {
  background: transparent;
  /* Cap to viewport so tree + main scroll independently (not with the page). */
  height: calc(100dvh - 11.5rem);
  min-height: 420px;
  max-height: calc(100dvh - 11.5rem);
}
.csm-app :deep(.v-application__wrap) {
  min-height: 0 !important;
  height: 100%;
  max-height: 100%;
  display: flex;
  flex-direction: column;
}
.csm-main {
  flex: 1 1 auto;
  min-height: 0;
  max-height: 100%;
  padding: 0;
  --v-layout-top: 0px;
  display: flex;
  flex-direction: column;
}
.csm-shell {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
  height: 100%;
}
.csm-toolbar {
  flex-shrink: 0;
  z-index: 2;
  background: rgb(var(--v-theme-surface));
  padding: 8px 4px 12px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}
.csm-context {
  line-height: 1.45;
  word-break: break-word;
  color: rgba(var(--v-theme-on-surface), 0.87);
}
.csm-context-tab {
  color: rgba(var(--v-theme-on-surface), 0.87);
}
.csm-context-sep {
  margin: 0 0.35em;
  opacity: 0.45;
}
.csm-context-label {
  color: rgba(var(--v-theme-on-surface), 0.58);
  margin-right: 0.2em;
}
.csm-context-value {
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.92);
}
.csm-save-errors {
  flex-shrink: 0;
  max-height: min(40vh, 280px);
  overflow: auto;
}
.csm-save-errors-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.csm-save-error-item {
  padding: 6px 8px;
  border-radius: 6px;
  background: rgba(var(--v-theme-error), 0.08);
}
.csm-save-error-msg {
  font-size: 0.8125rem;
  line-height: 1.35;
}
.csm-save-error-path {
  margin-top: 2px;
  opacity: 0.75;
  word-break: break-word;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
.csm-editor-wrap {
  flex: 1 1 auto;
  min-height: 0;
  padding-top: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.csm-editor-wrap :deep(.mf-editor) {
  flex: 1 1 auto;
  min-height: 0;
  height: 100%;
}
</style>
