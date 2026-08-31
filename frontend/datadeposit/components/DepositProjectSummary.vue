<script setup>
import { computed, ref } from 'vue';
import { summarySections } from '../composables/summaryPreview';

const props = defineProps({
  project: { type: Object, default: () => ({}) },
  metadata: { type: Object, default: () => ({}) },
  submission: { type: Object, default: () => ({}) },
  files: { type: Array, default: () => [] },
  formTemplate: { type: Object, default: () => ({ items: [] }) },
  submitTemplate: { type: Object, default: () => ({ items: [] }) },
  labels: { type: Object, default: () => ({}) },
  showEdit: { type: Boolean, default: false },
});

defineEmits(['edit']);

const TOP_PANELS = ['info', 'metadata', 'files', 'access'];
const openPanels = ref([...TOP_PANELS]);

const lbl = computed(() => {
  const labels = props.labels || {};
  return {
    stepInfo: labels.stepInfo || 'Project info',
    stepMetadata: labels.stepMetadata || 'Study description',
    stepFiles: labels.stepFiles || 'Files',
    stepAccess: labels.stepAccess || 'Access and notes',
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
    noFiles: labels.noFiles || 'No files uploaded.',
    noPreview: labels.noPreview || 'Nothing entered yet.',
    downloadFile: labels.downloadFile || 'Download',
    na: labels.na || 'N/A',
    editStep: labels.editStep || 'Edit',
  };
});

const previewLabels = computed(() => ({
  trueLabel: props.labels?.trueLabel || 'True',
  falseLabel: props.labels?.falseLabel || 'False',
  noPreview: lbl.value.noPreview,
}));

const collaborators = computed(() =>
  (Array.isArray(props.project.collaborators) ? props.project.collaborators : [])
    .map((row) => String(row || '').trim())
    .filter(Boolean)
);

const metadataSections = computed(() =>
  summarySections(props.formTemplate?.items, props.metadata, previewLabels.value)
);
const accessSections = computed(() =>
  summarySections(props.submitTemplate?.items, props.submission, previewLabels.value)
);

const status = computed(() => String(props.project.status || '').toLowerCase());

function expandAll() {
  openPanels.value = [...TOP_PANELS];
}

defineExpose({ expandAll });
</script>

<template>
  <v-expansion-panels
    v-model="openPanels"
    multiple
    variant="accordion"
    class="dd-summary-panels"
  >
    <v-expansion-panel value="info" rounded="lg" elevation="0">
      <v-expansion-panel-title class="text-subtitle-1 font-weight-medium">
        <span class="dd-panel-title">{{ lbl.stepInfo }}</span>
        <v-btn
          v-if="showEdit"
          variant="text"
          size="small"
          class="text-none dd-no-print"
          @click.stop="$emit('edit', 'info')"
        >
          {{ lbl.editStep }}
        </v-btn>
      </v-expansion-panel-title>
      <v-expansion-panel-text>
        <dl class="dd-summary-dl">
          <div>
            <dt>{{ lbl.projectId }}</dt>
            <dd>{{ project.id || '—' }}</dd>
          </div>
          <div>
            <dt>{{ lbl.title }}</dt>
            <dd>{{ project.title || '—' }}</dd>
          </div>
          <div>
            <dt>{{ lbl.shortname }}</dt>
            <dd>{{ project.shortname || '—' }}</dd>
          </div>
          <div>
            <dt>{{ lbl.description }}</dt>
            <dd class="dd-pre">{{ project.description || '—' }}</dd>
          </div>
          <div>
            <dt>{{ lbl.collaborators }}</dt>
            <dd>{{ collaborators.length ? collaborators.join(', ') : '—' }}</dd>
          </div>
          <div v-if="project.data_type_label || project.data_type">
            <dt>{{ lbl.projectType }}</dt>
            <dd>{{ project.data_type_label || project.data_type }}</dd>
          </div>
          <div>
            <dt>{{ lbl.createdBy }}</dt>
            <dd>{{ project.created_by || '—' }}</dd>
          </div>
          <div>
            <dt>{{ lbl.createdOn }}</dt>
            <dd>{{ project.created_on || '—' }}</dd>
          </div>
          <div>
            <dt>{{ lbl.lastModified }}</dt>
            <dd>{{ project.last_modified || '—' }}</dd>
          </div>
          <div>
            <dt>{{ lbl.status }}</dt>
            <dd>{{ status || '—' }}</dd>
          </div>
        </dl>
      </v-expansion-panel-text>
    </v-expansion-panel>

    <v-expansion-panel value="metadata" rounded="lg" elevation="0">
      <v-expansion-panel-title class="text-subtitle-1 font-weight-medium">
        <span class="dd-panel-title">{{ lbl.stepMetadata }}</span>
        <v-btn
          v-if="showEdit"
          variant="text"
          size="small"
          class="text-none dd-no-print"
          @click.stop="$emit('edit', 'metadata')"
        >
          {{ lbl.editStep }}
        </v-btn>
      </v-expansion-panel-title>
      <v-expansion-panel-text>
        <p v-if="!metadataSections.length" class="text-body-2 text-medium-emphasis mb-0">
          {{ lbl.noPreview }}
        </p>
        <div v-else>
          <div v-for="section in metadataSections" :key="section.title" class="dd-summary-block">
            <h3 class="text-body-1 font-weight-medium mb-2">{{ section.title }}</h3>
            <dl class="dd-summary-dl">
              <div v-for="row in section.rows" :key="row.key">
                <dt>{{ row.title }}</dt>
                <dd class="dd-pre">{{ row.value }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </v-expansion-panel-text>
    </v-expansion-panel>

    <v-expansion-panel value="files" rounded="lg" elevation="0">
      <v-expansion-panel-title class="text-subtitle-1 font-weight-medium">
        <span class="dd-panel-title">{{ lbl.stepFiles }}</span>
        <v-btn
          v-if="showEdit"
          variant="text"
          size="small"
          class="text-none dd-no-print"
          @click.stop="$emit('edit', 'files')"
        >
          {{ lbl.editStep }}
        </v-btn>
      </v-expansion-panel-title>
      <v-expansion-panel-text>
        <p v-if="!files.length" class="text-body-2 text-medium-emphasis mb-0">{{ lbl.noFiles }}</p>
        <table v-else class="dd-summary-table">
          <thead>
            <tr>
              <th>{{ lbl.title }}</th>
              <th>{{ lbl.resourceType }}</th>
              <th class="dd-no-print">{{ lbl.downloadFile }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="file in files" :key="file.id || file.filename">
              <td>
                <div class="font-weight-medium">{{ file.title || file.filename }}</div>
                <div v-if="file.title && file.filename" class="text-caption text-medium-emphasis">
                  {{ file.filename }}
                </div>
              </td>
              <td>{{ file.dctype_title || file.dctype || lbl.na }}</td>
              <td class="dd-no-print">
                <a v-if="file.download_url" :href="file.download_url">{{ lbl.downloadFile }}</a>
                <span v-else>—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </v-expansion-panel-text>
    </v-expansion-panel>

    <v-expansion-panel value="access" rounded="lg" elevation="0">
      <v-expansion-panel-title class="text-subtitle-1 font-weight-medium">
        <span class="dd-panel-title">{{ lbl.stepAccess }}</span>
        <v-btn
          v-if="showEdit"
          variant="text"
          size="small"
          class="text-none dd-no-print"
          @click.stop="$emit('edit', 'access')"
        >
          {{ lbl.editStep }}
        </v-btn>
      </v-expansion-panel-title>
      <v-expansion-panel-text>
        <p v-if="!accessSections.length" class="text-body-2 text-medium-emphasis mb-0">
          {{ lbl.noPreview }}
        </p>
        <div v-else>
          <div v-for="section in accessSections" :key="section.title" class="dd-summary-block">
            <h3
              v-if="section.title && section.title !== 'Details'"
              class="text-body-1 font-weight-medium mb-2"
            >
              {{ section.title }}
            </h3>
            <dl class="dd-summary-dl">
              <div v-for="row in section.rows" :key="row.key">
                <dt>{{ row.title }}</dt>
                <dd class="dd-pre">{{ row.value }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </v-expansion-panel-text>
    </v-expansion-panel>
  </v-expansion-panels>
</template>

<style scoped>
.dd-summary-panels {
  gap: 10px;
}
.dd-summary-panels :deep(.v-expansion-panel) {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  background: rgb(var(--v-theme-surface));
}
.dd-summary-panels :deep(.v-expansion-panel-title) {
  display: flex;
  align-items: center;
  gap: 8px;
}
.dd-panel-title {
  flex: 1 1 auto;
}
.dd-summary-block + .dd-summary-block {
  margin-top: 16px;
}
.dd-summary-dl > div {
  display: grid;
  grid-template-columns: minmax(140px, 220px) 1fr;
  gap: 8px 16px;
  padding: 8px 0;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}
.dd-summary-dl dt {
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-size: 0.875rem;
}
.dd-summary-dl dd {
  margin: 0;
}
.dd-pre {
  white-space: pre-wrap;
  word-break: break-word;
}
.dd-summary-table {
  width: 100%;
  border-collapse: collapse;
}
.dd-summary-table th,
.dd-summary-table td {
  text-align: left;
  padding: 8px 10px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  vertical-align: top;
}
.dd-summary-table th {
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

@media print {
  .dd-no-print {
    display: none !important;
  }
  .dd-summary-panels :deep(.v-expansion-panel) {
    break-inside: avoid;
  }
  .dd-summary-panels :deep(.v-expansion-panel-text) {
    display: block !important;
    height: auto !important;
    opacity: 1 !important;
  }
}
</style>
