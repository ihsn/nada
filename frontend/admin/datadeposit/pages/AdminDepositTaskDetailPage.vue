<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="catalog-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="catalog-page-header mb-4">
      <div class="catalog-page-header__inner">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 catalog-page-header__title">
          {{ t('dd_task_info', 'Task info') }}
        </h1>
      </div>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact">
      {{ loadError }}
    </v-alert>
    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <v-card v-else-if="task" class="admin-catalog-surface" rounded="lg" elevation="1">
      <v-table density="comfortable" class="text-body-2">
        <tbody>
          <tr>
            <th class="text-left text-medium-emphasis font-weight-medium" style="width: 12rem">
              {{ t('dd_project', 'Project') }}
            </th>
            <td>
              <router-link
                v-if="task.project_id"
                :to="{ name: 'admin-deposit-workspace', params: { id: String(task.project_id) } }"
                class="text-decoration-none"
              >
                {{ task.project_title || t('dd_project', 'Project') }}
              </router-link>
              <span v-else>{{ task.project_title }}</span>
            </td>
          </tr>
          <tr>
            <th class="text-left text-medium-emphasis font-weight-medium">
              {{ t('dd_status', 'Status') }}
            </th>
            <td>
              <v-chip size="small" variant="tonal" :color="taskColor(task.status)">
                {{ taskLabel(task.status) }}
              </v-chip>
            </td>
          </tr>
          <tr>
            <th class="text-left text-medium-emphasis font-weight-medium">
              {{ t('dd_assigned_to', 'Assigned to') }}
            </th>
            <td>{{ task.assigned_to || '—' }}</td>
          </tr>
          <tr>
            <th class="text-left text-medium-emphasis font-weight-medium">
              {{ t('dd_assigned_by', 'Assigned by') }}
            </th>
            <td>{{ task.assigned_by || '—' }}</td>
          </tr>
          <tr>
            <th class="text-left text-medium-emphasis font-weight-medium">
              {{ t('dd_date_assigned', 'Date assigned') }}
            </th>
            <td>{{ formatDate(task.date_assigned) || '—' }}</td>
          </tr>
          <tr>
            <th class="text-left text-medium-emphasis font-weight-medium">
              {{ t('dd_date_completed', 'Date completed') }}
            </th>
            <td>{{ formatDate(task.date_completed) || '—' }}</td>
          </tr>
          <tr>
            <th class="text-left text-medium-emphasis font-weight-medium">
              {{ t('dd_update_status', 'Update status') }}
            </th>
            <td>
              <v-alert v-if="actionError" type="error" variant="tonal" density="compact" class="mb-3">
                {{ actionError }}
              </v-alert>
              <div class="d-flex flex-wrap ga-2">
                <v-btn
                  v-if="canEdit && Number(task.status) === 0"
                  color="success"
                  size="small"
                  :loading="saving"
                  :disabled="saving || deleting"
                  @click="setStatus(1)"
                >
                  {{ t('dd_resolve', 'Resolve') }}
                </v-btn>
                <v-btn
                  v-else-if="canEdit"
                  color="warning"
                  size="small"
                  :loading="saving"
                  :disabled="saving || deleting"
                  @click="setStatus(0)"
                >
                  {{ t('dd_reopen_task', 'Re-open') }}
                </v-btn>
                <v-btn
                  v-if="canDelete"
                  variant="outlined"
                  size="small"
                  :loading="deleting"
                  :disabled="saving || deleting"
                  @click="confirmUnassign"
                >
                  {{ t('delete', 'Delete') }}
                </v-btn>
              </div>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import $dialog from '@/shared/composables/dialog';

const props = defineProps({
  id: { type: [String, Number], required: true },
});

defineOptions({ name: 'AdminDepositTaskDetailPage' });

const { t } = useI18n();
const router = useRouter();
const { siteUrl, canEdit, canDelete } = useAppConfig();
const { fetchTask, updateTaskStatus, deleteTask } = useAdminDepositApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const task = ref(null);
const loading = ref(false);
const loadError = ref('');
const actionError = ref('');
const saving = ref(false);
const deleting = ref(false);

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBaseUrl.value}/admin` },
  {
    title: t('title_project_management', 'Data deposit'),
    to: { name: 'admin-deposit-list' },
  },
  {
    title: t('dd_active_tasks', 'Tasks'),
    to: { name: 'admin-deposit-tasks' },
  },
  { title: t('dd_task_info', 'Task info'), disabled: true },
]);

function taskLabel(status) {
  return Number(status) === 1 ? t('dd_completed', 'Completed') : t('dd_wip', 'Work in progress');
}

function taskColor(status) {
  return Number(status) === 1 ? 'success' : 'warning';
}

function formatDate(unix) {
  if (!unix) return '';
  const d = new Date(Number(unix) * 1000);
  if (Number.isNaN(d.getTime())) return '';
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${mm}-${dd}-${d.getFullYear()}`;
}

async function load() {
  loading.value = true;
  loadError.value = '';
  actionError.value = '';
  try {
    task.value = await fetchTask(props.id);
    if (!task.value) {
      loadError.value = t('dd_task_not_found', 'Task was not found');
    }
  } catch (e) {
    task.value = null;
    loadError.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
  } finally {
    loading.value = false;
  }
}

async function setStatus(status) {
  if (!task.value?.id) return;
  saving.value = true;
  actionError.value = '';
  try {
    const result = await updateTaskStatus(task.value.id, status);
    if (result) {
      task.value = result;
    }
  } catch (e) {
    actionError.value = e?.response?.data?.message || e?.message || t('form_update_fail', 'Update failed');
  } finally {
    saving.value = false;
  }
}

async function confirmUnassign() {
  if (!task.value?.id) return;
  const title = task.value.project_title || t('dd_project', 'Project');
  const ok = await $dialog.confirm({
    title: t('confirm_delete', 'Confirm delete'),
    message: t('dd_confirm_delete_task', 'Unassign this task from “%s”?', title),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  deleting.value = true;
  actionError.value = '';
  try {
    await deleteTask(task.value.id);
    await router.push({ name: 'admin-deposit-list' });
  } catch (e) {
    actionError.value = e?.response?.data?.message || e?.message || t('form_update_fail', 'Update failed');
  } finally {
    deleting.value = false;
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
</style>
