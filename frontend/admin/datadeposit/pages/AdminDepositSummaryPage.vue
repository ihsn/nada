<template>
  <div class="admin-deposit-summary">
    <div class="d-flex align-center justify-end flex-wrap ga-3 mb-4 admin-deposit-summary__bar">
      <v-btn
        color="primary"
        class="text-none"
        prepend-icon="mdi-printer-outline"
        :disabled="pageLoading || !!loadError"
        @click="printSummary"
      >
        {{ t('print', 'Print') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact">
      {{ loadError }}
    </v-alert>
    <div v-else-if="pageLoading" class="d-flex justify-center py-12">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <template v-else-if="project">
      <div class="mb-6">
        <h1 class="text-h5 font-weight-medium mb-2">
          {{ project.title || t('summary', 'Summary') }}
        </h1>
        <v-chip size="small" variant="tonal" :color="statusColor(project.status)">
          {{ statusLabel(project.status) }}
        </v-chip>
      </div>
      <DepositProjectSummary
        ref="summaryRef"
        :project="project"
        :metadata="metadata"
        :submission="submission"
        :files="files"
        :form-template="formTemplate"
        :submit-template="submitTemplate"
        :labels="summaryLabels"
      />
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useI18n } from '@/shared/composables/useI18n';
import DepositProjectSummary from '@/datadeposit/components/DepositProjectSummary.vue';

const props = defineProps({
  id: { type: [String, Number], required: true },
});

defineOptions({ name: 'AdminDepositSummaryPage' });

const { t } = useI18n();
const { fetchProject } = useAdminDepositApi();

const project = ref(null);
const metadata = ref({});
const submission = ref({});
const pageLoading = ref(false);
const loadError = ref('');
const summaryRef = ref(null);

const files = computed(() => (Array.isArray(project.value?.files) ? project.value.files : []));
const formTemplate = computed(() => project.value?.form_template || { items: [] });
const submitTemplate = computed(() => project.value?.submit_template || { items: [] });

const summaryLabels = computed(() => ({
  stepInfo: t('project_info', 'Project info'),
  stepMetadata: t('study_desc', 'Study description'),
  stepFiles: t('files_tab', 'Files'),
  stepAccess: t('dd_access_and_notes', 'Access and notes'),
  title: t('title', 'Title'),
  shortname: t('shortname', 'Short name'),
  description: t('description', 'Description'),
  collaborators: t('collaborator', 'Collaborators'),
  createdBy: t('created_by', 'Created by'),
  createdOn: t('dd_created_on', 'Date created'),
  lastModified: t('dd_last_modified', 'Last modified'),
  status: t('dd_status', 'Status'),
  projectType: t('type', 'Type'),
  resourceType: t('type', 'Type'),
  projectId: t('projectId', 'Project ID'),
  noFiles: t('dd_no_files_uploaded', 'No files uploaded.'),
  noPreview: t('metadata_no_preview', 'Nothing entered yet.'),
  downloadFile: t('download', 'Download'),
  na: t('dd_na', 'N/A'),
  trueLabel: t('metadata_true', 'True'),
  falseLabel: t('metadata_false', 'False'),
}));

function statusLabel(status) {
  const key = String(status || '').toLowerCase();
  const labels = {
    draft: t('draft', 'Draft'),
    submitted: t('dd_submitted', 'Submitted'),
    processed: t('dd_processed', 'Processed'),
    accepted: t('dd_accepted', 'Accepted'),
    closed: t('dd_closed', 'Closed'),
  };
  return labels[key] || (status ? String(status) : '');
}

function statusColor(status) {
  switch (String(status || '').toLowerCase()) {
    case 'submitted':
      return 'info';
    case 'processed':
      return 'warning';
    case 'accepted':
      return 'success';
    case 'closed':
      return 'secondary';
    default:
      return 'default';
  }
}

async function expandAllForPrint() {
  summaryRef.value?.expandAll?.();
  await nextTick();
}

async function printSummary() {
  await expandAllForPrint();
  window.print();
}

async function load() {
  pageLoading.value = true;
  loadError.value = '';
  try {
    const result = await fetchProject(props.id);
    project.value = result.project || null;
    metadata.value = result.metadata && typeof result.metadata === 'object' ? result.metadata : {};
    submission.value = result.submission && typeof result.submission === 'object' ? result.submission : {};
    if (!project.value) {
      loadError.value = t('dd_project_not_found', 'Project was not found');
    }
  } catch (e) {
    project.value = null;
    metadata.value = {};
    submission.value = {};
    loadError.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
  } finally {
    pageLoading.value = false;
  }
}

watch(
  () => props.id,
  async () => {
    await load();
    const print = new URLSearchParams(window.location.search).get('print');
    if (print === 'yes' && !loadError.value) {
      await expandAllForPrint();
      window.setTimeout(() => window.print(), 250);
    }
  },
  { immediate: true }
);
</script>

<style scoped>
.admin-deposit-summary {
  max-width: 960px;
  margin: 0 auto;
  padding: 16px 8px 32px;
}

@media print {
  .admin-deposit-summary__bar {
    display: none !important;
  }
}
</style>
