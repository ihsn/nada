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
              {{ t('dd_assign_task', 'Assign task') }}
            </h1>
            <div class="text-body-2 text-medium-emphasis">
              {{ project.title }}
              <span v-if="project.id"> · {{ t('projectId', 'Project ID') }} {{ project.id }}</span>
            </div>
          </div>
        </div>
      </div>

      <v-card class="admin-catalog-surface pa-4" rounded="lg" elevation="1">
        <v-alert v-if="saveMsg" :type="saveOk ? 'success' : 'error'" variant="tonal" density="compact" class="mb-4">
          {{ saveMsg }}
        </v-alert>

        <p v-if="!team.length" class="text-body-2 text-medium-emphasis mb-0">
          {{ t('no_records_found', 'No users available') }}
        </p>
        <v-radio-group v-else v-model="userId" hide-details>
          <div class="dd-assign-group">
            <label
              v-for="member in team"
              :key="member.id"
              class="dd-assign-member"
              :class="{ 'dd-assign-member--active': Number(userId) === Number(member.id) }"
            >
              <v-radio :value="member.id" color="primary" hide-details />
              <span>
                <span class="font-weight-medium d-block">{{ memberName(member) }}</span>
                <span class="text-caption text-medium-emphasis">{{ member.email }}</span>
              </span>
            </label>
          </div>
        </v-radio-group>

        <div class="d-flex align-center ga-3 mt-6">
          <v-btn color="primary" :loading="saving" :disabled="!canEdit || !userId" @click="save">
            {{ t('submit', 'Submit') }}
          </v-btn>
          <router-link :to="{ name: 'admin-deposit-list' }" class="text-primary text-decoration-none">
            {{ t('cancel', 'Cancel') }}
          </router-link>
        </div>
      </v-card>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';

const props = defineProps({
  id: { type: [String, Number], required: true },
});

defineOptions({ name: 'AdminDepositAssignPage' });

const { t } = useI18n();
const router = useRouter();
const { siteUrl, canEdit } = useAppConfig();
const { fetchAssign, assignTask } = useAdminDepositApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const project = ref(null);
const team = ref([]);
const userId = ref(null);
const pageLoading = ref(false);
const saving = ref(false);
const loadError = ref('');
const saveMsg = ref('');
const saveOk = ref(false);

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBaseUrl.value}/admin` },
  {
    title: t('title_project_management', 'Data deposit'),
    to: { name: 'admin-deposit-list' },
  },
  {
    title: t('dd_assign_task', 'Assign task'),
    disabled: true,
  },
]);

function memberName(member) {
  return [member.first_name, member.last_name].filter(Boolean).join(' ').trim() || member.email || String(member.id);
}

async function load() {
  pageLoading.value = true;
  loadError.value = '';
  try {
    const result = await fetchAssign(props.id);
    project.value = result.project || null;
    team.value = Array.isArray(result.team) ? result.team : [];
    userId.value = result.assigned_user_id || null;
    if (!project.value) {
      loadError.value = t('dd_project_not_found', 'Project was not found');
    }
  } catch (e) {
    project.value = null;
    team.value = [];
    loadError.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
  } finally {
    pageLoading.value = false;
  }
}

async function save() {
  if (!userId.value || !props.id) return;
  saving.value = true;
  saveMsg.value = '';
  try {
    await assignTask(props.id, userId.value);
    await router.push({ name: 'admin-deposit-list' });
  } catch (e) {
    saveOk.value = false;
    saveMsg.value = e?.response?.data?.message || e?.message || t('form_update_fail', 'Update failed');
  } finally {
    saving.value = false;
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

.dd-assign-group {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 12px;
}

.dd-assign-member {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin: 0;
  padding: 12px 14px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 8px;
  cursor: pointer;
}

.dd-assign-member--active {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.06);
}
</style>
