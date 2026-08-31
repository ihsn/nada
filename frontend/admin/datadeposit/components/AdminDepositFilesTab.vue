<template>
  <v-card class="admin-catalog-surface pa-4" rounded="lg" elevation="1">
    <h2 class="text-subtitle-1 font-weight-semibold mb-3">
      {{ t('dd_files_in_folder', 'Files in project folder') }}
    </h2>

    <div class="dd-storage-path mb-4">
      <div class="text-caption text-medium-emphasis mb-1">
        {{ t('dd_files_located_at', 'Project files located at') }}
      </div>
      <code class="dd-storage-path__value">{{ storagePath || t('dd_not_set', 'NOT-SET') }}</code>
    </div>

    <v-table v-if="files.length" density="comfortable" class="text-body-2">
      <thead>
        <tr>
          <th class="text-left">{{ t('title', 'Name') }}</th>
          <th class="text-left">{{ t('type', 'Type') }}</th>
          <th v-if="canEdit" class="text-left">{{ t('download', 'Download') }}</th>
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
          <td>{{ file.dctype_title || file.dctype || t('dd_na', 'N/A') }}</td>
          <td v-if="canEdit">
            <a
              v-if="file.id"
              :href="fileDownloadUrl(projectId, file.id)"
              class="text-primary text-decoration-none"
            >
              {{ t('download', 'Download') }}
            </a>
            <span v-else>—</span>
          </td>
        </tr>
      </tbody>
    </v-table>
    <p v-else class="text-body-2 text-medium-emphasis mb-0">
      {{ t('dd_no_files_attached', 'There are no files attached to this project') }}
    </p>
  </v-card>
</template>

<script setup>
import { computed } from 'vue';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';

const props = defineProps({
  project: { type: Object, default: () => ({}) },
});

defineOptions({ name: 'AdminDepositFilesTab' });

const { t } = useI18n();
const { canEdit } = useAppConfig();
const { fileDownloadUrl } = useAdminDepositApi();

const projectId = computed(() => props.project?.id);
const files = computed(() => (Array.isArray(props.project?.files) ? props.project.files : []));
const storagePath = computed(() => String(props.project?.storage_path || '').trim());
</script>

<style scoped>
.dd-storage-path {
  background: rgba(var(--v-theme-on-surface), 0.04);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 8px;
  padding: 12px 14px;
}
.dd-storage-path__value {
  display: block;
  font-size: 0.8125rem;
  word-break: break-all;
}
</style>
