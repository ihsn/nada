<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="catalog-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact">
      {{ loadError }}
    </v-alert>
    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-4" />

    <template v-else-if="project">
      <div class="catalog-page-header mb-4">
        <div class="catalog-page-header__inner">
          <div class="catalog-page-header__title">
            <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
              {{ project.title || t('title_project_management', 'Data deposit') }}
            </h1>
            <div class="text-body-2 text-medium-emphasis">
              {{ t('projectId', 'Project ID') }} {{ project.id }}
              <span v-if="project.shortname"> · {{ project.shortname }}</span>
            </div>
          </div>
          <v-chip size="small" variant="tonal" :color="statusColor(project.status)">
            {{ statusLabel(project.status) }}
          </v-chip>
        </div>
      </div>

      <div class="admin-deposit-toolbar mb-4">
        <a :href="summaryUrl" target="_blank" rel="noopener" class="text-primary text-decoration-none">
          {{ t('summary', 'Summary') }}
        </a>
        <template v-if="canExportDdi">
          <span class="text-medium-emphasis"> | </span>
          <a :href="exportHref('ddi')" class="text-primary text-decoration-none">
            {{ t('DDI', 'DDI') }}
          </a>
        </template>
        <span class="text-medium-emphasis"> | </span>
        <a :href="exportHref('rdf')" class="text-primary text-decoration-none">
          {{ t('RDF', 'RDF') }}
        </a>
        <span class="text-medium-emphasis"> | </span>
        <a :href="exportHref('json')" class="text-primary text-decoration-none">
          {{ t('JSON', 'JSON') }}
        </a>
      </div>

      <v-tabs v-model="tab" color="primary" class="mb-4">
        <v-tab value="info">{{ t('project_information_tab', 'Project information') }}</v-tab>
        <v-tab v-if="canEdit" value="process">{{ t('process_tab', 'Process') }}</v-tab>
        <v-tab value="files">{{ t('files_tab', 'Files') }}</v-tab>
        <v-tab v-if="canEdit" value="communicate">{{ t('communicate', 'Communicate') }}</v-tab>
        <v-tab value="history">{{ t('dd_history', 'History') }}</v-tab>
      </v-tabs>

      <v-window v-model="tab">
        <v-window-item value="info">
          <DepositProjectSummary
            :project="project"
            :metadata="metadata"
            :submission="submission"
            :files="files"
            :form-template="formTemplate"
            :submit-template="submitTemplate"
            :labels="summaryLabels"
          />
        </v-window-item>
        <v-window-item v-if="canEdit" value="process">
          <AdminDepositProcessForm :project="project" @saved="onProcessSaved" />
        </v-window-item>
        <v-window-item value="files">
          <AdminDepositFilesTab :project="project" />
        </v-window-item>
        <v-window-item v-if="canEdit" value="communicate">
          <AdminDepositCommunicateForm :project="project" />
        </v-window-item>
        <v-window-item value="history">
          <AdminDepositHistoryTab :project-id="project.id" :active="tab === 'history'" />
        </v-window-item>
      </v-window>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import DepositProjectSummary from '@/datadeposit/components/DepositProjectSummary.vue';
import AdminDepositProcessForm from '../components/AdminDepositProcessForm.vue';
import AdminDepositFilesTab from '../components/AdminDepositFilesTab.vue';
import AdminDepositCommunicateForm from '../components/AdminDepositCommunicateForm.vue';
import AdminDepositHistoryTab from '../components/AdminDepositHistoryTab.vue';

const VALID_TABS = new Set(['info', 'process', 'files', 'communicate', 'history']);

const props = defineProps({
  id: { type: [String, Number], required: true },
  tab: { type: String, default: '' },
});

defineOptions({ name: 'AdminDepositWorkspacePage' });

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { siteUrl, canEdit } = useAppConfig();
const { fetchProject, exportUrl } = useAdminDepositApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const project = ref(null);
const metadata = ref({});
const submission = ref({});
const pageLoading = ref(false);
const loadError = ref('');

const files = computed(() => (Array.isArray(project.value?.files) ? project.value.files : []));
const formTemplate = computed(() => project.value?.form_template || { items: [] });
const submitTemplate = computed(() => project.value?.submit_template || { items: [] });
const canExportDdi = computed(() => {
  if (typeof project.value?.can_export_ddi === 'boolean') return project.value.can_export_ddi;
  return String(project.value?.data_type || '').toLowerCase() === 'survey';
});
const summaryUrl = computed(() => {
  const id = project.value?.id || props.id;
  return id ? `${siteBaseUrl.value}/admin/datadeposit/summary/${id}` : '';
});

function exportHref(format) {
  const id = project.value?.id || props.id;
  return exportUrl(id, format);
}

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

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBaseUrl.value}/admin` },
  {
    title: t('title_project_management', 'Data deposit'),
    to: { name: 'admin-deposit-list' },
  },
  {
    title: project.value?.title || t('dd_project', 'Project'),
    disabled: true,
  },
]);

const tab = computed({
  get() {
    const raw = route.params.tab;
    const next = normalizeTab(raw !== undefined && raw !== '' ? raw : 'info');
    if (!canEdit.value && (next === 'process' || next === 'communicate')) {
      return 'info';
    }
    return next;
  },
  set(next) {
    const v = normalizeTab(next);
    const id = String(props.id ?? route.params.id ?? '');
    if (!id) return;
    const dest =
      v === 'info'
        ? { name: 'admin-deposit-workspace', params: { id } }
        : { name: 'admin-deposit-workspace-tab', params: { id, tab: v } };
    router.replace(dest).catch(() => {});
  },
});

function normalizeTab(value) {
  const s = String(value ?? '').toLowerCase();
  return VALID_TABS.has(s) ? s : 'info';
}

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

function onProcessSaved(result) {
  if (!result || typeof result !== 'object') {
    return;
  }
  if (result.project) {
    project.value = result.project;
  }
  if (result.metadata && typeof result.metadata === 'object') {
    metadata.value = result.metadata;
  }
  if (result.submission && typeof result.submission === 'object') {
    submission.value = result.submission;
  }
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
  () => {
    load();
  },
  { immediate: true }
);
</script>

<style scoped>
.catalog-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.catalog-breadcrumbs :deep(.v-breadcrumbs-item),
.catalog-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}

.catalog-page-header__inner {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.catalog-page-header__title {
  min-width: 0;
}

.admin-deposit-toolbar {
  font-size: 0.875rem;
}
</style>
