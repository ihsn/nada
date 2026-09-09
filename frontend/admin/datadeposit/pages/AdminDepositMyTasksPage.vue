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
          {{ t('dd_my_tasks', 'My tasks') }}
        </h1>
      </div>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact">
      {{ loadError }}
    </v-alert>
    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <v-card class="admin-catalog-surface mb-5" rounded="lg" elevation="1">
      <v-table density="comfortable" class="text-body-2">
        <thead>
          <tr>
            <th class="text-left">{{ t('title', 'Title') }}</th>
            <th class="text-left">{{ t('dd_task_status', 'Task status') }}</th>
            <th class="text-left">{{ t('dd_assigned_to', 'Assigned to') }}</th>
            <th class="text-left">{{ t('dd_assigned_by', 'Assigned by') }}</th>
            <th class="text-left">{{ t('dd_assigned_on', 'Assigned on') }}</th>
            <th class="text-left">{{ t('dd_completed_on', 'Completed on') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="task in assignedToMe" :key="task.id">
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
            <td>
              <router-link
                :to="{ name: 'admin-deposit-task', params: { id: String(task.id) } }"
                class="text-decoration-none"
              >
                <v-chip size="small" variant="tonal" :color="taskColor(task.status)">
                  {{ taskLabel(task.status) }}
                </v-chip>
              </router-link>
            </td>
            <td>{{ task.assigned_to }}</td>
            <td>{{ task.assigned_by }}</td>
            <td>{{ formatDate(task.date_assigned) }}</td>
            <td>{{ formatDate(task.date_completed) || '—' }}</td>
          </tr>
          <tr v-if="!loading && !assignedToMe.length">
            <td colspan="6" class="text-medium-emphasis">
              {{ t('no_records_found', 'No tasks') }}
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <h2 class="text-h6 font-weight-semibold text-high-emphasis mb-3">
      {{ t('dd_tasks_assigned_others', 'Tasks assigned to others') }}
    </h2>
    <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
      <v-table density="comfortable" class="text-body-2">
        <thead>
          <tr>
            <th class="text-left">{{ t('title', 'Title') }}</th>
            <th class="text-left">{{ t('dd_task_status', 'Task status') }}</th>
            <th class="text-left">{{ t('dd_assigned_to', 'Assigned to') }}</th>
            <th class="text-left">{{ t('dd_assigned_by', 'Assigned by') }}</th>
            <th class="text-left">{{ t('dd_assigned_on', 'Assigned on') }}</th>
            <th class="text-left">{{ t('dd_completed_on', 'Completed on') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="task in assignedByMe" :key="task.id">
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
            <td>
              <router-link
                :to="{ name: 'admin-deposit-task', params: { id: String(task.id) } }"
                class="text-decoration-none"
              >
                <v-chip size="small" variant="tonal" :color="taskColor(task.status)">
                  {{ taskLabel(task.status) }}
                </v-chip>
              </router-link>
            </td>
            <td>{{ task.assigned_to }}</td>
            <td>{{ task.assigned_by }}</td>
            <td>{{ formatDate(task.date_assigned) }}</td>
            <td>{{ formatDate(task.date_completed) || '—' }}</td>
          </tr>
          <tr v-if="!loading && !assignedByMe.length">
            <td colspan="6" class="text-medium-emphasis">
              {{ t('no_records_found', 'No tasks') }}
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'AdminDepositMyTasksPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { fetchMyTasks } = useAdminDepositApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const assignedToMe = ref([]);
const assignedByMe = ref([]);
const loading = ref(false);
const loadError = ref('');

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBaseUrl.value}/admin` },
  {
    title: t('title_project_management', 'Data deposit'),
    to: { name: 'admin-deposit-list' },
  },
  { title: t('dd_my_tasks', 'My tasks'), disabled: true },
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
  try {
    const result = await fetchMyTasks();
    assignedToMe.value = Array.isArray(result?.assigned_to_me) ? result.assigned_to_me : [];
    assignedByMe.value = Array.isArray(result?.assigned_by_me) ? result.assigned_by_me : [];
  } catch (e) {
    assignedToMe.value = [];
    assignedByMe.value = [];
    loadError.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  load();
});
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
