<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import MetadataFormEditor from '@/shared/metadata-form/components/MetadataFormEditor.vue';
import { pruneEmpty } from '@/shared/metadata-form/composables/createMetadataFormStore';
import { extractApiError } from './composables/apiErrors';
import { useDepositApi } from './composables/useDepositApi';
import { useUnsavedChangesGuard } from './composables/useUnsavedChangesGuard';
import DepositFilesStep from './components/DepositFilesStep.vue';
import DepositBreadcrumb from './components/DepositBreadcrumb.vue';
import DepositStatusChip from './components/DepositStatusChip.vue';
import DepositImportMetadataDialog from './components/DepositImportMetadataDialog.vue';
import DepositProjectSummary from './components/DepositProjectSummary.vue';

const STEP_INFO = 1;
const STEP_METADATA = 2;
const STEP_FILES = 3;
const STEP_ACCESS = 4;
const STEP_REVIEW = 5;

const STEP_KEYS = {
  info: STEP_INFO,
  metadata: STEP_METADATA,
  files: STEP_FILES,
  access: STEP_ACCESS,
  review: STEP_REVIEW,
};

function resolveStepKey(key) {
  if (key === 'submit') return STEP_REVIEW;
  return STEP_KEYS[key] || STEP_METADATA;
}

const { config } = useAppConfig();
const route = useRoute();
const router = useRouter();
const {
  fetchMetadata,
  saveMetadata,
  fetchSubmission,
  saveSubmission,
  submitProject,
  fetchProject,
  saveProject,
  validateProject,
} = useDepositApi();

const step = ref(resolveStepKey(route.query.step || config.value?.initialStep));
const loading = ref(true);
const saving = ref(false);
const submitting = ref(false);
const loadError = ref('');
const saveError = ref('');
const saveErrors = ref([]);
const issues = ref([]);
const validating = ref(false);
const snackbar = ref({ show: false, text: '', color: 'success' });
const saveAlertEl = ref(null);
let validateTimer = null;

const metadataRef = ref(null);
const submitRef = ref(null);
const metadata = ref({});
const submission = ref({});
const reviewFiles = ref([]);
const filesTick = ref(0);
const importDialogOpen = ref(false);
const submitDialogOpen = ref(false);
const projectRow = ref({});
const projectInfo = ref({
  title: '',
  shortname: '',
  description: '',
  collaborators: [''],
});
const projectTitle = ref(config.value?.projectTitle || '');
const loadedFormTemplate = ref(config.value?.formTemplate || null);
const loadedSubmitTemplate = ref(config.value?.submitTemplate || null);
const projectCanEdit = ref(config.value?.canEdit !== false);
const liveStatus = ref(String(config.value?.projectStatus || '').toLowerCase());

const canEdit = computed(() => !!projectCanEdit.value);
const projectStatus = computed(() => liveStatus.value || String(config.value?.projectStatus || '').toLowerCase());

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    reload: labels.reload || 'Reload',
    save: labels.save || 'Save',
    saveUnsaved: labels.saveUnsaved || 'Save *',
    saved: labels.saved || 'Saved',
    loadFailed: labels.loadFailed || 'Failed to load',
    saveFailed: labels.saveFailed || 'Save failed',
    unsavedLeave: labels.unsavedLeave || 'You have unsaved changes. Leave this page?',
    unsavedReload: labels.unsavedReload || 'You have unsaved changes. Reload and discard them?',
    validationFailed: labels.validationFailed || 'Validation failed',
    requestFailed: labels.requestFailed || 'Request failed',
    selectSection: labels.selectSection || 'Select a section.',
    addFromList: labels.addFromList || 'Add from list',
    addRow: labels.addRow || 'Add row',
    add: labels.add || 'Add',
    deleteRow: labels.deleteRow || 'Delete row',
    deleteRowConfirm: labels.deleteRowConfirm || 'Delete this row?',
    noRows: labels.noRows || 'No rows yet. Use “Add row” below.',
    noItems: labels.noItems || 'No items yet.',
    showHelp: labels.showHelp || 'Show help',
    hideHelp: labels.hideHelp || 'Hide help',
    showAllHelp: labels.showAllHelp || 'Show all help',
    hideAllHelp: labels.hideAllHelp || 'Hide all help',
    filterAll: labels.filterAll || 'All',
    filterRequired: labels.filterRequired || 'Required',
    filterRecommended: labels.filterRecommended || 'Recommended',
    searchFields: labels.searchFields || 'Search fields',
    noMatchingFields: labels.noMatchingFields || 'No matching fields.',
    containerOverview: labels.containerOverview || '',
    sectionsInGroup: labels.sectionsInGroup || 'Sections',
    nothingEntered: labels.nothingEntered || 'Nothing entered yet.',
    noFields: labels.noFields || 'This section has no fields.',
    noPreview: labels.noPreview || 'Nothing entered yet.',
    createdBy: labels.createdBy || 'Created by',
    createdOn: labels.createdOn || 'Date created',
    lastModified: labels.lastModified || 'Last modified',
    status: labels.status || 'Status',
    projectType: labels.projectType || 'Type',
    resourceType: labels.resourceType || 'Type',
    projectId: labels.projectId || 'Project ID',
    downloadFile: labels.downloadFile || 'Download',
    na: labels.na || 'N/A',
    editSection: labels.editSection || 'Edit section',
    treeNav: labels.treeNav || '',
    item: labels.item || 'Item',
    trueLabel: labels.trueLabel || 'True',
    falseLabel: labels.falseLabel || 'False',
    submitProject: labels.submitProject || 'Submit project',
    submitted: labels.submitted || 'Project submitted',
    submitConfirm:
      labels.submitConfirm ||
      'Submit this project? You will not be able to edit it until it is reopened.',
    locked: labels.locked || 'This project is locked.',
    stepInfo: labels.stepInfo || 'Project info',
    stepMetadata: labels.stepMetadata || 'Study description',
    stepFiles: labels.stepFiles || 'Files',
    stepAccess: labels.stepAccess || 'Access and notes',
    stepSubmit: labels.stepSubmit || 'Review and submit',
    editStep: labels.editStep || 'Edit',
    noFiles: labels.noFiles || 'No files uploaded.',
    requiredMissing: labels.requiredMissing || 'Required fields not filled',
    cannotSubmit: labels.cannotSubmit || 'Fix the items listed above before submitting.',
    validationIssues: labels.validationIssues || 'Validation issues',
    pendingTasks: labels.pendingTasks || 'Pending tasks',
    myProjects: labels.myProjects || 'My projects',
    title: labels.title || 'Title',
    shortname: labels.shortname || 'Short name',
    description: labels.description || 'Description',
    collaborators: labels.collaborators || 'Collaborators',
    collaboratorHelp: labels.collaboratorHelp || 'Add the email addresses of people who can edit this project.',
    importMetadata: labels.importMetadata || 'Import metadata',
    importMetadataSuccess: labels.importMetadataSuccess || 'Metadata imported successfully!',
    cancel: labels.cancel || 'Cancel',
  };
});

const breadcrumbItems = computed(() => {
  const items = [{ title: lbl.value.myProjects, to: { name: 'projects' } }];
  const current = projectTitle.value || lbl.value.stepInfo;
  if (current) {
    items.push({ title: current });
  }
  return items;
});

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
    showAllHelp: l.showAllHelp,
    hideAllHelp: l.hideAllHelp,
    filterAll: l.filterAll,
    filterRequired: l.filterRequired,
    filterRecommended: l.filterRecommended,
    searchFields: l.searchFields,
    noMatchingFields: l.noMatchingFields,
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

const metadataTemplate = computed(
  () => loadedFormTemplate.value || config.value?.formTemplate || { items: [] }
);
const submitTemplate = computed(
  () => loadedSubmitTemplate.value || config.value?.submitTemplate || { items: [] }
);

const metadataIssues = computed(() => issues.value.filter((row) => row.step === 'metadata'));
const accessIssues = computed(() => issues.value.filter((row) => row.step === 'access'));
const hasValidationIssues = computed(() => issues.value.length > 0);
const reviewCollaborators = computed(() =>
  (projectInfo.value.collaborators || []).map((x) => String(x || '').trim()).filter(Boolean)
);
const reviewProject = computed(() => ({
  ...projectRow.value,
  title: projectInfo.value.title || projectRow.value.title,
  shortname: projectInfo.value.shortname || projectRow.value.shortname,
  description: projectInfo.value.description || projectRow.value.description,
  collaborators: reviewCollaborators.value,
  status: projectStatus.value || projectRow.value.status,
  files: reviewFiles.value,
}));

const errorLabels = computed(() => ({
  validationFailed: lbl.value.validationFailed,
  saveFailed: lbl.value.saveFailed,
  requestFailed: lbl.value.requestFailed,
}));

const currentStep = computed(() => Number(step.value) || STEP_METADATA);
const isReadOnlyStep = computed(() => currentStep.value === STEP_FILES || currentStep.value === STEP_REVIEW);

const activeFormRef = computed(() => {
  if (currentStep.value === STEP_METADATA) return metadataRef.value;
  if (currentStep.value === STEP_ACCESS) return submitRef.value;
  return null;
});

const { isDirty, markClean, confirmReload, confirmLeave } = useUnsavedChangesGuard({
  getCurrent: () => {
    if (currentStep.value === STEP_INFO) return projectInfo.value;
    if (currentStep.value === STEP_ACCESS) {
      return activeFormRef.value?.getPayload?.() || submission.value || {};
    }
    return activeFormRef.value?.getPayload?.() || {};
  },
  getWatchSource: () => {
    if (currentStep.value === STEP_INFO) return projectInfo.value;
    if (currentStep.value === STEP_ACCESS) {
      return activeFormRef.value?.store?.state?.data || submission.value;
    }
    return activeFormRef.value?.store?.state?.data;
  },
  isEnabled: () => !loading.value && canEdit.value && !isReadOnlyStep.value,
  leaveMessage: () => lbl.value.unsavedLeave,
  reloadMessage: () => lbl.value.unsavedReload,
});

const saveLabel = computed(() => (isDirty.value ? lbl.value.saveUnsaved : lbl.value.save));

function showSnack(text, color = 'success') {
  snackbar.value = { show: true, text, color };
}

function clearSaveErrors() {
  saveError.value = '';
  saveErrors.value = [];
}

async function revealSaveErrors() {
  await nextTick();
  saveAlertEl.value?.scrollIntoView?.({ behavior: 'smooth', block: 'nearest' });
}

function stepKey(value) {
  if (value === STEP_REVIEW) return 'review';
  return (
    Object.keys(STEP_KEYS).find((k) => STEP_KEYS[k] === value) || 'metadata'
  );
}

function applyProjectRow(row) {
  projectRow.value = row && typeof row === 'object' ? row : {};
  projectInfo.value = {
    title: row.title || '',
    shortname: row.shortname || '',
    description: row.description || '',
    collaborators: normalizeCollaborators(row.collaborators),
  };
  projectTitle.value = projectInfo.value.title || projectTitle.value;
  reviewFiles.value = Array.isArray(row.files) ? row.files : [];
  if (typeof row.can_edit === 'boolean') {
    projectCanEdit.value = row.can_edit;
  }
  if (row.status) {
    liveStatus.value = String(row.status).toLowerCase();
  }
  if (row.form_template && typeof row.form_template === 'object') {
    loadedFormTemplate.value = row.form_template;
  }
  if (row.submit_template && typeof row.submit_template === 'object') {
    loadedSubmitTemplate.value = row.submit_template;
  }
}

function onFilesUpdated(list) {
  reviewFiles.value = Array.isArray(list) ? list : [];
}

async function onMetadataImported(imported) {
  metadata.value = imported && typeof imported === 'object' ? imported : {};
  await nextTick();
  metadataRef.value?.replaceData?.(metadata.value);
  markClean();
  showSnack(lbl.value.importMetadataSuccess, 'success');
  await runValidation();
}

function syncUrl(value) {
  const url = new URL(window.location.href);
  url.searchParams.set('step', stepKey(value));
  window.history.replaceState({}, '', url);
}

function normalizeCollaborators(list) {
  const next = Array.isArray(list) ? list.map((x) => String(x || '')).filter((x) => x.trim()) : [];
  return next.length ? next : [''];
}

function addCollaborator() {
  projectInfo.value.collaborators = [...projectInfo.value.collaborators, ''];
}

function removeCollaborator(index) {
  const next = projectInfo.value.collaborators.slice();
  next.splice(index, 1);
  projectInfo.value.collaborators = next.length ? next : [''];
}

async function loadStep(target = currentStep.value) {
  const next = Number(target) || STEP_METADATA;
  loading.value = true;
  loadError.value = '';
  clearSaveErrors();
  try {
    if (!loadedFormTemplate.value || next === STEP_INFO || next === STEP_REVIEW) {
      applyProjectRow(await fetchProject());
    }
    if (next === STEP_INFO) {
      await nextTick();
      markClean();
    } else if (next === STEP_METADATA) {
      const data = await fetchMetadata();
      metadata.value = data && typeof data === 'object' ? data : {};
      await nextTick();
      metadataRef.value?.replaceData?.(metadata.value);
      markClean();
    } else if (next === STEP_ACCESS) {
      const data = await fetchSubmission();
      submission.value = data && typeof data === 'object' ? data : {};
      await nextTick();
      submitRef.value?.replaceData?.(submission.value);
      markClean();
    } else if (next === STEP_REVIEW) {
      const [meta, sub] = await Promise.all([fetchMetadata(), fetchSubmission()]);
      metadata.value = meta && typeof meta === 'object' ? meta : {};
      submission.value = sub && typeof sub === 'object' ? sub : {};
      await nextTick();
      markClean();
    } else {
      markClean();
    }
  } catch (e) {
    const extracted = extractApiError(e, errorLabels.value);
    loadError.value = extracted.message || lbl.value.loadFailed;
  } finally {
    loading.value = false;
  }
  await runValidation();
}

function currentMetadataPayload() {
  return pruneEmpty(metadataRef.value?.getPayload?.() || metadata.value || {}) || {};
}

function currentSubmissionPayload() {
  return pruneEmpty(submitRef.value?.getPayload?.() || submission.value || {}) || {};
}

async function runValidation() {
  const stepNow = currentStep.value;
  if (stepNow !== STEP_METADATA && stepNow !== STEP_ACCESS && stepNow !== STEP_REVIEW) {
    return;
  }
  validating.value = true;
  try {
    const data = await validateProject({
      metadata: currentMetadataPayload(),
      submission: currentSubmissionPayload(),
    });
    issues.value = Array.isArray(data.issues) ? data.issues : [];
  } catch (e) {
    /* keep last issues */
  } finally {
    validating.value = false;
  }
}

function scheduleValidation() {
  if (validateTimer) window.clearTimeout(validateTimer);
  validateTimer = window.setTimeout(() => {
    runValidation();
  }, 450);
}

function goToIssue(issue) {
  if (!issue) return;
  if (issue.step === 'access') {
    changeStep(STEP_ACCESS);
    return;
  }
  changeStep(STEP_METADATA);
}

function onSummaryEdit(section) {
  const map = {
    info: STEP_INFO,
    metadata: STEP_METADATA,
    files: STEP_FILES,
    access: STEP_ACCESS,
  };
  changeStep(map[section] || STEP_METADATA);
}

function issueLabel(issue) {
  const title = String(issue?.title || issue?.key || '').trim();
  const msg = String(issue?.message || '').trim();
  if (title && msg && !msg.toLowerCase().includes(title.toLowerCase())) {
    return `${title}: ${msg}`;
  }
  return msg || title;
}

function reviewIssueLabel(issue) {
  const stepTitle = issue?.step === 'access' ? lbl.value.stepAccess : lbl.value.stepMetadata;
  return `${stepTitle} — ${issueLabel(issue)}`;
}

async function persistCurrent() {
  const target = currentStep.value;
  if (target === STEP_INFO) {
    const payload = {
      title: String(projectInfo.value.title || '').trim(),
      shortname: String(projectInfo.value.shortname || '').trim(),
      description: String(projectInfo.value.description || ''),
      collaborators: (projectInfo.value.collaborators || []).map((x) => String(x || '').trim()).filter(Boolean),
    };
    if (!payload.title || !payload.shortname) {
      const err = new Error(lbl.value.validationFailed);
      err.errors = [
        ...(!payload.title ? [{ property: 'title', message: 'Title is required' }] : []),
        ...(!payload.shortname ? [{ property: 'shortname', message: 'Short name is required' }] : []),
      ];
      throw err;
    }
    const saved = await saveProject(payload);
    if (saved?.project?.title) {
      projectTitle.value = saved.project.title;
    }
    return;
  }
  if (target === STEP_METADATA) {
    const payload = pruneEmpty(metadataRef.value?.getPayload?.() || metadata.value || {}) || {};
    await saveMetadata(payload);
    const latest = await fetchMetadata();
    metadata.value = latest && typeof latest === 'object' ? latest : {};
    await nextTick();
    metadataRef.value?.replaceData?.(metadata.value);
    return;
  }
  if (target === STEP_ACCESS) {
    await nextTick();
    const payload =
      pruneEmpty(submitRef.value?.getPayload?.() || submission.value || {}) || {};
    await saveSubmission(payload);
    const latest = await fetchSubmission();
    submission.value = latest && typeof latest === 'object' ? latest : {};
    await nextTick();
    submitRef.value?.replaceData?.(submission.value);
    return;
  }
}

async function onSave() {
  if (!canEdit.value || isReadOnlyStep.value) return;
  saving.value = true;
  clearSaveErrors();
  try {
    await persistCurrent();
    await nextTick();
    markClean();
    showSnack(lbl.value.saved, 'success');
    await runValidation();
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

async function onReload() {
  if (!isReadOnlyStep.value && !confirmReload()) return;
  if (currentStep.value === STEP_FILES) {
    filesTick.value += 1;
  }
  await loadStep(step.value);
}

async function changeStep(next) {
  const target = Number(next) || STEP_METADATA;
  if (target === currentStep.value) return false;
  if (!isReadOnlyStep.value && isDirty.value && canEdit.value) {
    if (currentStep.value === STEP_METADATA || currentStep.value === STEP_ACCESS) {
      if (!confirmLeave()) return false;
    } else {
      saving.value = true;
      clearSaveErrors();
      try {
        await persistCurrent();
        markClean();
      } catch (e) {
        const extracted = extractApiError(e, errorLabels.value);
        saveError.value = extracted.message || lbl.value.saveFailed;
        saveErrors.value = extracted.errors || [];
        showSnack(saveError.value, 'error');
        await revealSaveErrors();
        return false;
      } finally {
        saving.value = false;
      }
    }
  }
  step.value = target;
  syncUrl(target);
  await loadStep(target);
  return true;
}

const stepModel = computed({
  get: () => currentStep.value,
  set: (value) => {
    changeStep(value);
  },
});

async function onSubmit() {
  if (!canEdit.value || submitting.value) return;
  await runValidation();
  if (hasValidationIssues.value) {
    saveError.value = lbl.value.cannotSubmit;
    saveErrors.value = issues.value.map((issue) => ({
      property: issue.key,
      message: issueLabel(issue),
    }));
    showSnack(lbl.value.cannotSubmit, 'error');
    await revealSaveErrors();
    return;
  }
  submitDialogOpen.value = true;
}

function closeSubmitDialog() {
  if (submitting.value) return;
  submitDialogOpen.value = false;
}

async function confirmSubmit() {
  if (!canEdit.value || submitting.value) return;
  submitting.value = true;
  clearSaveErrors();
  try {
    await submitProject(currentSubmissionPayload());
    markClean();
    submitDialogOpen.value = false;
    showSnack(lbl.value.submitted, 'success');
    await router.push({ name: 'projects' });
  } catch (e) {
    submitDialogOpen.value = false;
    const extracted = extractApiError(e, errorLabels.value);
    saveError.value = extracted.message || lbl.value.saveFailed;
    saveErrors.value = extracted.errors || [];
    showSnack(saveError.value, 'error');
    await revealSaveErrors();
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  syncUrl(step.value);
  await loadStep(step.value);
});

watch(
  [
    currentStep,
    () => metadataRef.value?.store?.state?.data,
    () => submitRef.value?.store?.state?.data,
    metadata,
    submission,
  ],
  () => {
    if (loading.value) return;
    scheduleValidation();
  },
  { deep: true }
);

onBeforeUnmount(() => {
  if (validateTimer) window.clearTimeout(validateTimer);
});

watch(
  () => String(route.params.id || ''),
  async (id, prev) => {
    if (!id || id === prev) return;
    await loadStep(step.value);
  }
);
</script>

<template>
  <v-app class="dd-wizard-app">
    <v-main class="dd-wizard-main">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
        {{ snackbar.text }}
      </v-snackbar>

      <div class="dd-wizard">
        <DepositBreadcrumb :items="breadcrumbItems" />
        <div class="dd-wizard-bar d-flex align-center justify-space-between flex-wrap ga-3">
          <div class="dd-wizard-title">
            <h1 class="text-h5 font-weight-medium mb-0">{{ projectTitle || lbl.stepInfo }}</h1>
            <DepositStatusChip :status="projectStatus" class="mt-2" />
          </div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-btn
              v-if="currentStep === STEP_METADATA && canEdit"
              variant="tonal"
              class="text-none"
              prepend-icon="mdi-database-import-outline"
              :disabled="loading || saving || submitting"
              @click="importDialogOpen = true"
            >
              {{ lbl.importMetadata }}
            </v-btn>
            <v-btn
              variant="tonal"
              class="text-none"
              :disabled="loading || saving || submitting"
              @click="onReload"
            >
              {{ lbl.reload }}
            </v-btn>
            <v-btn
              v-if="!isReadOnlyStep"
              color="primary"
              class="text-none dd-primary-btn"
              :loading="saving"
              :disabled="loading || saving || submitting || !canEdit"
              @click="onSave"
            >
              {{ saveLabel }}
            </v-btn>
            <v-btn
              v-if="currentStep === STEP_REVIEW"
              color="primary"
              class="text-none dd-primary-btn"
              :loading="submitting"
              :disabled="loading || saving || submitting || !canEdit || hasValidationIssues"
              @click="onSubmit"
            >
              {{ lbl.submitProject }}
            </v-btn>
          </div>
        </div>

        <v-alert v-if="!canEdit" type="info" variant="tonal" class="mb-3" density="compact">
          {{ lbl.locked }}
        </v-alert>
        <v-alert v-if="loadError" type="error" variant="tonal" class="mb-3" density="compact" closable>
          {{ loadError }}
        </v-alert>
        <div v-if="saveError" ref="saveAlertEl" class="mb-3">
          <v-alert type="error" variant="tonal" density="compact" closable @click:close="clearSaveErrors">
            <div class="font-weight-medium">{{ saveError }}</div>
            <div v-if="saveErrors.length" class="mt-2">
              <div v-for="(err, i) in saveErrors" :key="i" class="text-body-2">
                {{ err.message }}
                <span v-if="err.property" class="text-caption text-medium-emphasis"> ({{ err.property }})</span>
              </div>
            </div>
          </v-alert>
        </div>

        <v-stepper
          v-model="stepModel"
          :editable="true"
          :hide-actions="true"
          alt-labels
          color="primary"
          class="dd-stepper"
        >
          <v-stepper-header>
            <v-stepper-item
              :value="STEP_INFO"
              :title="lbl.stepInfo"
              color="primary"
              :ripple="false"
              :complete="step > STEP_INFO"
            />
            <div class="dd-step-line" aria-hidden="true" />
            <v-stepper-item
              :value="STEP_METADATA"
              :title="lbl.stepMetadata"
              color="primary"
              :ripple="false"
              :complete="step > STEP_METADATA"
            />
            <div class="dd-step-line" aria-hidden="true" />
            <v-stepper-item
              :value="STEP_FILES"
              :title="lbl.stepFiles"
              color="primary"
              :ripple="false"
              :complete="step > STEP_FILES"
            />
            <div class="dd-step-line" aria-hidden="true" />
            <v-stepper-item
              :value="STEP_ACCESS"
              :title="lbl.stepAccess"
              color="primary"
              :ripple="false"
              :complete="step > STEP_ACCESS"
            />
            <div class="dd-step-line" aria-hidden="true" />
            <v-stepper-item
              :value="STEP_REVIEW"
              :title="lbl.stepSubmit"
              color="primary"
              :ripple="false"
            />
          </v-stepper-header>

          <v-stepper-window>
            <v-stepper-window-item :value="STEP_INFO">
              <div class="dd-info-pane">
              <div v-if="loading && step === STEP_INFO" class="d-flex justify-center py-12">
                <v-progress-circular indeterminate color="primary" />
              </div>
              <v-form v-else class="dd-info-form" @submit.prevent="onSave">
                <div class="dd-info-field">
                  <label class="dd-info-label" for="dd-project-title">
                    {{ lbl.title }} <span class="text-error">*</span>
                  </label>
                  <v-text-field
                    id="dd-project-title"
                    v-model="projectInfo.title"
                    :disabled="!canEdit"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                  />
                </div>
                <div class="dd-info-field">
                  <label class="dd-info-label" for="dd-project-shortname">
                    {{ lbl.shortname }} <span class="text-error">*</span>
                  </label>
                  <v-text-field
                    id="dd-project-shortname"
                    v-model="projectInfo.shortname"
                    :disabled="!canEdit"
                    variant="outlined"
                    density="comfortable"
                    hide-details="auto"
                  />
                </div>
                <div class="dd-info-field">
                  <label class="dd-info-label" for="dd-project-description">{{ lbl.description }}</label>
                  <v-textarea
                    id="dd-project-description"
                    v-model="projectInfo.description"
                    :disabled="!canEdit"
                    variant="outlined"
                    auto-grow
                    rows="3"
                    hide-details="auto"
                  />
                </div>
                <div class="dd-info-field">
                  <div class="d-flex align-start justify-space-between ga-2 mb-1">
                    <div class="flex-grow-1">
                      <div class="dd-info-label">{{ lbl.collaborators }}</div>
                      <p class="text-caption text-medium-emphasis mb-0">{{ lbl.collaboratorHelp }}</p>
                    </div>
                    <v-btn
                      v-if="canEdit"
                      size="small"
                      variant="tonal"
                      class="text-none"
                      prepend-icon="mdi-plus"
                      @click="addCollaborator"
                    >
                      {{ lbl.add }}
                    </v-btn>
                  </div>
                  <div
                    v-for="(_, i) in projectInfo.collaborators"
                    :key="i"
                    class="d-flex ga-2 mb-2 align-center"
                  >
                    <v-text-field
                      :id="`dd-project-collab-${i}`"
                      v-model="projectInfo.collaborators[i]"
                      :disabled="!canEdit"
                      type="email"
                      variant="outlined"
                      density="comfortable"
                      hide-details
                      class="flex-grow-1"
                    />
                    <v-btn
                      v-if="canEdit"
                      icon="mdi-close"
                      size="small"
                      variant="text"
                      color="error"
                      :aria-label="lbl.deleteRow"
                      @click="removeCollaborator(i)"
                    />
                  </div>
                </div>
              </v-form>
              </div>
            </v-stepper-window-item>

            <v-stepper-window-item :value="STEP_METADATA">
              <div v-if="loading && step === STEP_METADATA" class="d-flex justify-center py-12">
                <v-progress-circular indeterminate color="primary" />
              </div>
              <div v-show="!(loading && step === STEP_METADATA)" class="dd-form-wrap">
                <v-alert
                  v-if="metadataIssues.length"
                  class="dd-pending-alert mb-3"
                  density="compact"
                >
                  <div class="font-weight-medium">{{ lbl.validationIssues }}</div>
                  <ul class="dd-review-missing">
                    <li v-for="issue in metadataIssues" :key="issue.key">{{ issueLabel(issue) }}</li>
                  </ul>
                </v-alert>
                <MetadataFormEditor
                  ref="metadataRef"
                  v-model="metadata"
                  layout="tree"
                  :show-tree="true"
                  :form-template="metadataTemplate"
                  :labels="formLabels"
                />
              </div>
            </v-stepper-window-item>

            <v-stepper-window-item :value="STEP_FILES">
              <div class="dd-info-pane">
                <DepositFilesStep :key="filesTick" :can-edit="canEdit" @updated="onFilesUpdated" />
              </div>
            </v-stepper-window-item>

            <v-stepper-window-item :value="STEP_ACCESS" eager>
              <div v-if="loading && currentStep === STEP_ACCESS" class="d-flex justify-center py-12">
                <v-progress-circular indeterminate color="primary" />
              </div>
              <div v-show="!(loading && currentStep === STEP_ACCESS)" class="dd-info-pane">
                <v-alert
                  v-if="accessIssues.length"
                  class="dd-pending-alert mb-3"
                  density="compact"
                >
                  <div class="font-weight-medium">{{ lbl.validationIssues }}</div>
                  <ul class="dd-review-missing">
                    <li v-for="issue in accessIssues" :key="issue.key">{{ issueLabel(issue) }}</li>
                  </ul>
                </v-alert>
                <MetadataFormEditor
                  ref="submitRef"
                  v-model="submission"
                  layout="stacked"
                  :show-tree="false"
                  :form-template="submitTemplate"
                  :labels="formLabels"
                />
              </div>
            </v-stepper-window-item>

            <v-stepper-window-item :value="STEP_REVIEW">
              <div v-if="loading && step === STEP_REVIEW" class="d-flex justify-center py-12">
                <v-progress-circular indeterminate color="primary" />
              </div>
              <div v-else class="dd-review">
                <DepositProjectSummary
                  :project="reviewProject"
                  :metadata="metadata"
                  :submission="submission"
                  :files="reviewFiles"
                  :form-template="metadataTemplate"
                  :submit-template="submitTemplate"
                  :labels="lbl"
                  :show-edit="canEdit"
                  @edit="onSummaryEdit"
                />
                <div class="dd-review-submit">
                  <v-alert
                    v-if="hasValidationIssues"
                    class="dd-pending-alert mb-3"
                    density="compact"
                  >
                    <div class="font-weight-medium">{{ lbl.pendingTasks }}</div>
                    <ul class="dd-review-missing">
                      <li v-for="issue in issues" :key="`${issue.step}:${issue.key}`">
                        <button type="button" class="dd-issue-link" @click="goToIssue(issue)">
                          {{ reviewIssueLabel(issue) }}
                        </button>
                      </li>
                    </ul>
                  </v-alert>
                  <p class="text-body-2 text-medium-emphasis mb-3">
                    {{ hasValidationIssues ? lbl.cannotSubmit : lbl.submitConfirm }}
                  </p>
                  <v-btn
                    color="primary"
                    class="text-none dd-primary-btn"
                    :loading="submitting"
                    :disabled="loading || saving || submitting || !canEdit || hasValidationIssues"
                    @click="onSubmit"
                  >
                    {{ lbl.submitProject }}
                  </v-btn>
                </div>
              </div>
            </v-stepper-window-item>
          </v-stepper-window>
        </v-stepper>
      </div>
      <DepositImportMetadataDialog
        v-model="importDialogOpen"
        :disabled="loading || saving || submitting || !canEdit"
        @imported="onMetadataImported"
      />
      <v-dialog v-model="submitDialogOpen" max-width="480" :persistent="submitting">
        <v-card>
          <v-card-title class="text-h6">{{ lbl.submitProject }}</v-card-title>
          <v-divider />
          <v-card-text>
            <p class="mb-0">{{ lbl.submitConfirm }}</p>
          </v-card-text>
          <v-divider />
          <v-card-actions class="pa-3">
            <v-spacer />
            <v-btn variant="text" class="text-none" :disabled="submitting" @click="closeSubmitDialog">
              {{ lbl.cancel }}
            </v-btn>
            <v-btn
              color="primary"
              class="text-none dd-primary-btn"
              :loading="submitting"
              @click="confirmSubmit"
            >
              {{ lbl.submitProject }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-main>
  </v-app>
</template>

<style scoped>
.dd-wizard-app.v-application {
  background: transparent;
  min-height: 0;
}
.dd-wizard-app :deep(.v-application__wrap) {
  min-height: 0 !important;
}
.dd-wizard-main {
  padding: 0;
  --v-layout-top: 0px;
}
.dd-wizard {
  width: 100%;
  margin: 0 auto;
  padding: 8px 0 24px;
}
.dd-form-wrap {
  display: flex;
  flex-direction: column;
}
.dd-form-wrap :deep(.mf-editor) {
  height: auto;
  min-height: 0;
}
.dd-form-wrap :deep(.mf-editor-layout) {
  height: auto;
  min-height: 0;
  align-items: start;
  overflow: visible;
  background: rgba(var(--v-theme-on-surface), 0.02);
}
.dd-form-wrap :deep(.mf-editor-tree) {
  position: sticky;
  top: 12px;
  align-self: start;
  max-height: calc(100dvh - 24px);
  overflow: hidden;
  background: transparent;
}
.dd-form-wrap :deep(.mf-editor-main) {
  position: static;
  align-self: start;
  max-height: none;
  overflow: visible;
  overscroll-behavior: auto;
  background: rgb(var(--v-theme-surface));
}
@media (max-width: 960px) {
  .dd-form-wrap :deep(.mf-editor-tree) {
    position: static;
    max-height: 280px;
    overflow: hidden;
  }
}
.dd-wizard-bar {
  padding: 8px 0 16px;
}
.dd-wizard-title {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.dd-stepper.v-stepper {
  box-shadow: none;
  background: transparent;
  overflow: visible;
  width: 100%;
}
.dd-stepper :deep(.v-stepper-window),
.dd-stepper :deep(.v-window),
.dd-stepper :deep(.v-window__container) {
  overflow: visible;
  height: auto;
  margin: 16px 0 0;
}
.dd-stepper :deep(.v-stepper-header) {
  --dd-step-line-top: 15px;
  --dd-step-line-color: rgba(var(--v-theme-on-surface), 0.16);
  display: flex !important;
  align-items: flex-start;
  justify-content: flex-start !important;
  width: 100%;
  box-sizing: border-box;
  box-shadow: none;
  overflow: visible;
  padding: 10px 16px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 8px;
  background: rgb(var(--v-theme-surface));
  position: relative;
}
.dd-stepper :deep(.v-stepper-item) {
  display: flex !important;
  flex: 0 0 auto !important;
  flex-basis: auto !important;
  width: auto !important;
  max-width: none !important;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  align-self: flex-start;
  min-width: 0;
  padding: 4px 12px 8px;
  position: relative;
  z-index: 1;
  background: transparent;
  outline: none !important;
  box-shadow: none !important;
}
.dd-stepper :deep(.v-stepper-item:focus),
.dd-stepper :deep(.v-stepper-item:focus-visible),
.dd-stepper :deep(.v-stepper-item:active) {
  outline: none !important;
  box-shadow: none !important;
}
.dd-stepper :deep(.v-stepper-item__overlay),
.dd-stepper :deep(.v-stepper-item__underlay) {
  display: none;
}
.dd-stepper :deep(.v-stepper-item::after) {
  display: none;
}
.dd-stepper :deep(.v-stepper-item:has(+ .dd-step-line)::after) {
  display: block;
  content: '';
  position: absolute;
  top: var(--dd-step-line-top);
  left: calc(50% + 14px);
  right: 0;
  height: 2px;
  background: var(--dd-step-line-color);
  pointer-events: none;
}
.dd-stepper :deep(.dd-step-line + .v-stepper-item::before) {
  content: '';
  position: absolute;
  top: var(--dd-step-line-top);
  left: 0;
  right: calc(50% + 14px);
  height: 2px;
  background: var(--dd-step-line-color);
  pointer-events: none;
}
.dd-step-line {
  flex: 1 1 16px;
  min-width: 12px;
  height: 2px;
  margin-top: var(--dd-step-line-top);
  background: var(--dd-step-line-color);
  align-self: flex-start;
  pointer-events: none;
}
.dd-stepper :deep(.v-stepper-item__avatar.v-avatar) {
  position: relative;
  z-index: 1;
  margin: 0 0 8px !important;
  box-shadow: 0 0 0 4px rgb(var(--v-theme-surface));
}
.dd-stepper :deep(.v-stepper-item__title) {
  font-size: 0.8125rem;
  line-height: 1.25;
  text-align: center;
}
.dd-info-pane {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 8px;
  background: rgb(var(--v-theme-surface));
  padding: 20px 24px;
}
.dd-info-form {
  max-width: 720px;
}
.dd-info-field {
  margin-bottom: 16px;
}
.dd-info-label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.87);
}
.dd-info-pane :deep(.mf-editor--stacked) {
  height: auto;
}
.dd-review {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.dd-review-missing {
  margin: 8px 0 0;
  padding-left: 1.2em;
}
.dd-issue-link {
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  text-decoration: underline;
  cursor: pointer;
}
.dd-review-submit {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  width: 100%;
}
.dd-review-submit > .v-btn {
  align-self: flex-start;
}
.dd-wizard-app :deep(.dd-primary-btn.v-btn),
.dd-wizard-app :deep(.dd-primary-btn.v-btn .v-btn__content) {
  color: #fff !important;
}
.dd-pending-alert {
  width: 100%;
  background: #fff8e6 !important;
  color: rgba(0, 0, 0, 0.87) !important;
  border: 1px solid #e0b85c;
}
.dd-pending-alert :deep(.v-alert__content) {
  color: inherit;
  width: 100%;
}
.dd-pending-alert .dd-issue-link {
  color: rgb(var(--v-theme-primary));
}
</style>
