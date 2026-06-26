<template>
  <v-expansion-panels v-if="loading || files.length" variant="accordion" class="registry-bulk-expansion" :model-value="[]">
    <v-expansion-panel rounded="lg" elevation="0">
      <v-expansion-panel-title class="text-h6 py-2 px-3">
        {{ title }}
      </v-expansion-panel-title>
      <v-expansion-panel-text class="pt-1 px-3 pb-2">
        <div v-if="loading" class="d-flex justify-center py-6">
          <v-progress-circular indeterminate color="primary" size="36" :aria-label="loadingLabel" />
        </div>
        <div v-else-if="files.length" class="registry-bulk-table-wrap">
          <v-table density="comfortable" class="bg-transparent registry-bulk-table">
            <thead>
              <tr>
                <th class="text-start">{{ colFile }}</th>
                <th class="text-start text-no-wrap">{{ colDate }}</th>
                <th class="text-end text-no-wrap">{{ colActions }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(file, idx) in files" :key="idx">
                <td class="align-top">
                  <div class="font-weight-medium text-body-2 text-truncate" :title="file.title">{{ file.title }}</div>
                  <div v-if="file.filename" class="text-caption text-medium-emphasis text-truncate" :title="file.filename">
                    {{ file.filename }}
                  </div>
                </td>
                <td class="text-caption text-medium-emphasis text-no-wrap align-top">{{ formatChanged(file.changed) }}</td>
                <td class="text-end align-top">
                  <v-btn
                    v-if="!file.external_link && file.links?.download"
                    size="small"
                    variant="tonal"
                    rounded="lg"
                    class="text-none"
                    :href="file.links.download"
                    target="_blank"
                    rel="noopener noreferrer"
                    prepend-icon="mdi-download"
                  >
                    {{ downloadLabel }}
                  </v-btn>
                  <v-btn
                    v-else-if="file.external_link && file.filename"
                    size="small"
                    variant="tonal"
                    rounded="lg"
                    class="text-none"
                    :href="file.filename"
                    target="_blank"
                    rel="noopener noreferrer"
                    prepend-icon="mdi-open-in-new"
                  >
                    {{ linkLabel }}
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </v-expansion-panel-text>
    </v-expansion-panel>
  </v-expansion-panels>
</template>

<script setup>
defineOptions({ name: 'StudyRegistryBulkDownloads' });

defineProps({
  title: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  files: { type: Array, default: () => [] },
  colFile: { type: String, default: 'File' },
  colDate: { type: String, default: 'Date' },
  colActions: { type: String, default: 'Actions' },
  downloadLabel: { type: String, default: 'Download' },
  linkLabel: { type: String, default: 'Link' },
  loadingLabel: { type: String, default: 'Loading...' },
});

function formatChanged(changed) {
  if (!changed) return '—';
  const d = new Date(changed);
  if (Number.isNaN(d.getTime())) return String(changed);
  return d.toLocaleDateString();
}
</script>

<style scoped>
.registry-bulk-expansion :deep(.v-expansion-panel) {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.registry-bulk-expansion :deep(.v-expansion-panel-title) {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  min-height: 2.5rem !important;
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
}

.registry-bulk-table :deep(th) {
  font-weight: 600;
  font-size: 0.75rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.registry-bulk-table :deep(tbody td) {
  padding-top: 0.5rem;
}

.registry-bulk-table-wrap {
  max-height: min(55vh, 28rem);
  overflow-y: auto;
  scrollbar-gutter: stable;
}
</style>
