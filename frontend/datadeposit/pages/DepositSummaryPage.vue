<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { extractApiError } from '../composables/apiErrors';
import { useDepositApi } from '../composables/useDepositApi';
import DepositBreadcrumb from '../components/DepositBreadcrumb.vue';
import DepositStatusChip from '../components/DepositStatusChip.vue';
import DepositExportItems from '../components/DepositExportItems.vue';
import DepositProjectSummary from '../components/DepositProjectSummary.vue';

const props = defineProps({
  id: { type: [String, Number], default: '' },
});

const { config } = useAppConfig();
const { fetchProject, fetchMetadata, fetchSubmission } = useDepositApi();

const loading = ref(true);
const loadError = ref('');
const project = ref({});
const metadata = ref({});
const submission = ref({});

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    summary: labels.summary || 'Summary',
    myProjects: labels.myProjects || 'My projects',
    edit: labels.edit || 'Edit',
    print: labels.print || 'Print',
    email: labels.email || 'Email',
    export: labels.export || 'Export',
    loadFailed: labels.loadFailed || 'Failed to load',
    title: labels.title || 'Title',
    shortname: labels.shortname || 'Short name',
    description: labels.description || 'Description',
    collaborators: labels.collaborators || 'Collaborators',
    createdBy: labels.createdBy || 'Created by',
    createdOn: labels.createdOn || 'Date created',
    lastModified: labels.lastModified || 'Last modified',
    status: labels.status || 'Status',
    projectType: labels.projectType || 'Type',
    resourceType: labels.resourceType || 'Type',
    projectId: labels.projectId || 'Project ID',
    stepInfo: labels.stepInfo || 'Project info',
    stepMetadata: labels.stepMetadata || 'Study description',
    stepFiles: labels.stepFiles || 'Files',
    stepAccess: labels.stepAccess || 'Access and notes',
    noFiles: labels.noFiles || 'No files uploaded.',
    noPreview: labels.noPreview || 'Nothing entered yet.',
    trueLabel: labels.trueLabel || 'True',
    falseLabel: labels.falseLabel || 'False',
    downloadFile: labels.downloadFile || 'Download',
    na: labels.na || 'N/A',
  };
});

const loadedFormTemplate = ref(config.value?.formTemplate || null);
const loadedSubmitTemplate = ref(config.value?.submitTemplate || null);
const summaryRef = ref(null);

const canEdit = computed(() => {
  if (typeof project.value.can_edit === 'boolean') return project.value.can_edit;
  if (config.value?.canEdit === false) return false;
  const status = String(project.value.status || config.value?.projectStatus || '').toLowerCase();
  return status === 'draft';
});

const files = computed(() => (Array.isArray(project.value.files) ? project.value.files : []));
const summaryProject = computed(() => ({
  ...project.value,
  id: project.value.id || props.id,
}));

const formTemplate = computed(
  () => loadedFormTemplate.value || config.value?.formTemplate || { items: [] }
);
const submitTemplate = computed(
  () => loadedSubmitTemplate.value || config.value?.submitTemplate || { items: [] }
);

const status = computed(() => String(project.value.status || config.value?.projectStatus || '').toLowerCase());

const breadcrumbItems = computed(() => [
  { title: lbl.value.myProjects, to: { name: 'projects' } },
  { title: project.value.title || config.value?.projectTitle || lbl.value.summary },
]);

async function expandAllForPrint() {
  summaryRef.value?.expandAll?.();
  await nextTick();
}

async function printSummary() {
  await expandAllForPrint();
  window.print();
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    const [projectData, metadataData, submissionData] = await Promise.all([
      fetchProject(),
      fetchMetadata(),
      fetchSubmission(),
    ]);
    project.value = projectData && typeof projectData === 'object' ? projectData : {};
    if (project.value.form_template && typeof project.value.form_template === 'object') {
      loadedFormTemplate.value = project.value.form_template;
    }
    if (project.value.submit_template && typeof project.value.submit_template === 'object') {
      loadedSubmitTemplate.value = project.value.submit_template;
    }
    metadata.value = metadataData && typeof metadataData === 'object' ? metadataData : {};
    submission.value = submissionData && typeof submissionData === 'object' ? submissionData : {};
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    loadError.value = extracted.message || lbl.value.loadFailed;
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  await load();
  const print = new URLSearchParams(window.location.search).get('print');
  if (print === 'yes' && !loadError.value) {
    await expandAllForPrint();
    window.setTimeout(() => window.print(), 250);
  }
});
</script>

<template>
  <v-app class="dd-summary-app">
    <v-main class="dd-summary-main">
      <div class="dd-summary">
        <DepositBreadcrumb :items="breadcrumbItems" class="dd-no-print" />
        <div class="dd-summary-bar d-flex align-center justify-end flex-wrap ga-3 dd-no-print">
            <v-btn
              v-if="canEdit && id"
              variant="tonal"
              class="text-none"
              prepend-icon="mdi-pencil-outline"
              :to="{ name: 'study', params: { id: String(id) } }"
            >
              {{ lbl.edit }}
            </v-btn>
            <v-btn
              v-if="id"
              variant="tonal"
              class="text-none"
              prepend-icon="mdi-email-outline"
              :disabled="loading || !!loadError"
              :to="{ name: 'email', params: { id: String(id) }, query: { from: 'summary' } }"
            >
              {{ lbl.email }}
            </v-btn>
            <v-menu location="bottom end">
              <template #activator="{ props: exportProps }">
                <v-btn
                  variant="tonal"
                  class="text-none"
                  prepend-icon="mdi-download-outline"
                  :disabled="loading || !!loadError || !id"
                  v-bind="exportProps"
                >
                  {{ lbl.export }}
                </v-btn>
              </template>
              <v-list class="dd-menu-list" density="compact" min-width="220">
                <DepositExportItems
                  :project-id="id"
                  :data-type="project.data_type || config.dataType"
                  :can-export-ddi="!!project.can_export_ddi"
                  :disabled="loading || !!loadError"
                />
              </v-list>
            </v-menu>
            <v-btn
              color="primary"
              class="text-none"
              prepend-icon="mdi-printer-outline"
              :disabled="loading || !!loadError"
              @click="printSummary"
            >
              {{ lbl.print }}
            </v-btn>
        </div>

        <div v-if="loading" class="d-flex justify-center py-12">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <v-alert v-else-if="loadError" type="error" variant="tonal" class="mb-4" density="compact">
          {{ loadError }}
        </v-alert>

        <template v-else>
          <div class="dd-summary-title mb-6">
            <h1 class="text-h5 font-weight-medium mb-0">
              {{ project.title || config.projectTitle || lbl.summary }}
            </h1>
            <DepositStatusChip :status="status" class="mt-2" />
          </div>

          <DepositProjectSummary
            ref="summaryRef"
            :project="summaryProject"
            :metadata="metadata"
            :submission="submission"
            :files="files"
            :form-template="formTemplate"
            :submit-template="submitTemplate"
            :labels="lbl"
          />
        </template>
      </div>
    </v-main>
  </v-app>
</template>

<style scoped>
.dd-summary-app.v-application {
  background: transparent;
  min-height: 0;
}
.dd-summary-app :deep(.v-application__wrap) {
  min-height: 0 !important;
}
.dd-summary-main {
  padding: 0;
  --v-layout-top: 0px;
}
.dd-summary {
  width: 100%;
  margin: 0 auto;
  padding: 8px 0 32px;
}
.dd-summary-title {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

@media print {
  .dd-no-print {
    display: none !important;
  }
  .dd-summary-app.v-application,
  .dd-summary-main {
    background: #fff !important;
  }
}
</style>
