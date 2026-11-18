<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="perm-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex flex-wrap align-start justify-space-between ga-3 mb-4">
      <div>
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
          {{ t('Manage roles', 'Manage roles') }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{
            t(
              'roles_page_intro',
              'System roles are built-in. Custom roles can be edited and assigned permissions.'
            )
          }}
        </p>
      </div>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        :disabled="pageLoading"
        @click="showCreate = !showCreate"
      >
        {{ t('Create a new role', 'Create a new role') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>

    <v-expand-transition>
      <v-card v-if="showCreate" class="pa-4 mb-4" elevation="1">
        <div class="text-subtitle-2 font-weight-semibold mb-3">
          {{ t('Create a new role', 'Create a new role') }}
        </div>
        <v-row dense align="end">
          <v-col cols="12" sm="4" md="3">
            <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">
              {{ t('role', 'Role') }}
            </div>
            <v-text-field
              v-model="createForm.role"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
              maxlength="100"
              autocomplete="off"
              autofocus
            />
          </v-col>
          <v-col cols="12" sm="8" md="6">
            <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">
              {{ t('description', 'Description') }}
            </div>
            <v-text-field
              v-model="createForm.description"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
              maxlength="255"
              autocomplete="off"
            />
          </v-col>
          <v-col cols="12" md="3" class="d-flex flex-wrap ga-2">
            <v-btn color="primary" :loading="creating" :disabled="!canCreate" @click="submitCreate">
              {{ t('create', 'Create') }}
            </v-btn>
            <v-btn variant="text" :disabled="creating" @click="cancelCreate">
              {{ t('cancel', 'Cancel') }}
            </v-btn>
          </v-col>
        </v-row>
        <v-alert v-if="createError" type="error" class="mt-3 mb-0" density="compact">
          {{ createError }}
        </v-alert>
      </v-card>
    </v-expand-transition>

    <v-card v-if="pageLoading && !roles.length" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="48" class="mb-3" />
        <p class="text-medium-emphasis mb-0">{{ t('loading', 'Loading…') }}</p>
      </v-card-text>
    </v-card>

    <div v-else class="perm-roles-sections">
      <v-card v-if="systemRoles.length" elevation="1">
        <div class="perm-section-header px-4 py-3">
          <div class="text-subtitle-2 font-weight-semibold text-high-emphasis">
            {{ t('system_roles', 'System roles') }}
          </div>
          <div class="text-caption text-medium-emphasis mt-1">
            {{ t('system_roles_note', 'Built-in roles. These cannot be edited or deleted.') }}
          </div>
        </div>
        <v-divider />
        <v-table density="comfortable" class="text-body-2 perm-roles-table">
          <thead>
            <tr>
              <th class="perm-col-name">{{ t('role', 'Role') }}</th>
              <th>{{ t('description', 'Description') }}</th>
              <th class="perm-col-users">{{ t('users', 'Users') }}</th>
              <th class="perm-col-actions text-end">{{ t('status', 'Status') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in systemRoles" :key="role.id" class="perm-row--system">
              <td>
                <div class="d-flex align-center ga-2 min-w-0">
                  <v-icon
                    size="20"
                    :icon="role.is_admin ? 'mdi-shield-crown-outline' : 'mdi-shield-lock-outline'"
                    class="text-medium-emphasis flex-shrink-0"
                  />
                  <router-link
                    :to="{ name: 'role', params: { id: role.id } }"
                    class="perm-role-link font-weight-semibold text-capitalize text-truncate"
                  >
                    {{ role.name }}
                  </router-link>
                  <v-chip size="x-small" variant="tonal" color="secondary" class="flex-shrink-0">
                    {{ t('system', 'System') }}
                  </v-chip>
                </div>
              </td>
              <td class="text-medium-emphasis">{{ role.description || '—' }}</td>
              <td>
                <a :href="usersUrl(role)" class="perm-role-link">{{ role.user_count }}</a>
              </td>
              <td class="text-end">
                <span class="text-caption text-medium-emphasis">{{ t('locked', 'Locked') }}</span>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>

      <v-card elevation="1">
        <div class="perm-section-header px-4 py-3 d-flex align-center justify-space-between ga-2 flex-wrap">
          <div>
            <div class="text-subtitle-2 font-weight-semibold text-high-emphasis">
              {{ t('custom_roles', 'Custom roles') }}
            </div>
            <div class="text-caption text-medium-emphasis mt-1">
              {{ t('custom_roles_note', 'Assignable roles with configurable permissions.') }}
            </div>
          </div>
          <span class="text-caption text-medium-emphasis">
            {{ customRoles.length }}
            {{ customRoles.length === 1 ? t('role', 'role') : t('roles', 'roles') }}
          </span>
        </div>
        <v-divider />

        <v-table v-if="customRoles.length" density="comfortable" class="text-body-2 perm-roles-table">
          <thead>
            <tr>
              <th class="perm-col-name">{{ t('role', 'Role') }}</th>
              <th>{{ t('description', 'Description') }}</th>
              <th class="perm-col-users">{{ t('users', 'Users') }}</th>
              <th class="perm-col-actions text-end">{{ t('actions', 'Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in customRoles" :key="role.id">
              <td>
                <div class="d-flex align-center ga-2 min-w-0">
                  <v-icon
                    size="20"
                    icon="mdi-account-key-outline"
                    class="text-medium-emphasis flex-shrink-0"
                  />
                  <router-link
                    :to="{ name: 'role', params: { id: role.id } }"
                    class="perm-role-link font-weight-medium text-capitalize text-truncate"
                  >
                    {{ role.name }}
                  </router-link>
                </div>
              </td>
              <td class="text-medium-emphasis">{{ role.description || '—' }}</td>
              <td>
                <a :href="usersUrl(role)" class="perm-role-link">{{ role.user_count }}</a>
              </td>
              <td class="text-end text-no-wrap">
                <v-tooltip :text="t('edit_role_permissions', 'Edit role and permissions')" location="top">
                  <template #activator="{ props: tip }">
                    <v-btn
                      v-bind="tip"
                      icon
                      variant="text"
                      size="small"
                      :to="{ name: 'role', params: { id: role.id } }"
                    >
                      <v-icon size="20" icon="mdi-pencil-outline" />
                    </v-btn>
                  </template>
                </v-tooltip>
                <v-tooltip :text="t('delete', 'Delete')" location="top">
                  <template #activator="{ props: tip }">
                    <v-btn
                      v-bind="tip"
                      icon
                      variant="text"
                      size="small"
                      color="error"
                      @click="openDelete(role)"
                    >
                      <v-icon size="20" icon="mdi-delete-outline" />
                    </v-btn>
                  </template>
                </v-tooltip>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-else class="text-center text-medium-emphasis py-10 px-4">
          <v-icon size="40" color="grey" class="mb-2">mdi-account-group-outline</v-icon>
          <div class="text-body-2 mb-3">{{ t('no_custom_roles', 'No custom roles yet.') }}</div>
          <v-btn
            v-if="!showCreate"
            color="primary"
            variant="tonal"
            prepend-icon="mdi-plus"
            @click="showCreate = true"
          >
            {{ t('Create a new role', 'Create a new role') }}
          </v-btn>
        </div>
      </v-card>
    </div>

    <v-dialog v-model="deleteDialog.open" max-width="420">
      <v-card>
        <v-card-title class="text-h6">{{ t('delete', 'Delete') }}</v-card-title>
        <v-card-text>
          <v-alert
            v-if="deleteDialog.error"
            type="error"
            class="mb-3"
            density="compact"
            closable
            @click:close="deleteDialog.error = ''"
          >
            {{ deleteDialog.error }}
          </v-alert>
          <div v-if="deleteDialog.role" class="perm-delete-text">
            <p>
              {{ t('delete_role_confirm', 'Delete the role "%s"? This cannot be undone.', deleteDialog.role.name) }}
            </p>
            <p v-if="deleteDialog.role.user_count > 0">
              {{
                deleteDialog.role.user_count === 1
                  ? t('delete_role_users_one', '1 user will lose this role and its permissions.')
                  : t('delete_role_users', '%d users will lose this role and its permissions.', deleteDialog.role.user_count)
              }}
            </p>
          </div>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.loading" @click="confirmDelete">
            {{ t('delete', 'Delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { usePermissionsApi } from '../composables/usePermissionsApi';

defineOptions({ name: 'RolesListPage' });

const router = useRouter();
const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { listRoles, createRole, deleteRole } = usePermissionsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const usersUrl = (role) => `${siteBaseUrl.value}/admin/users?role=${encodeURIComponent(String(role.id))}`;
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Manage roles', 'Manage roles'), disabled: true },
]);

const roles = ref([]);
const pageLoading = ref(true);
const loadError = ref('');
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');
const createForm = reactive({ role: '', description: '' });
const canCreate = computed(() => createForm.role.trim().length > 0);

const systemRoles = computed(() => roles.value.filter((r) => r.is_system));
const customRoles = computed(() => roles.value.filter((r) => !r.is_system));

const deleteDialog = reactive({ open: false, role: null, error: '', loading: false });
const toast = reactive({ open: false, message: '', color: 'success' });

function cancelCreate() {
  showCreate.value = false;
  createError.value = '';
  createForm.role = '';
  createForm.description = '';
}

async function load() {
  pageLoading.value = true;
  loadError.value = '';
  try {
    roles.value = await listRoles();
  } catch (e) {
    loadError.value = e.message || 'Failed to load';
  } finally {
    pageLoading.value = false;
  }
}

async function submitCreate() {
  if (!canCreate.value) return;
  creating.value = true;
  createError.value = '';
  try {
    const created = await createRole({
      role: createForm.role.trim(),
      description: createForm.description.trim(),
    });
    cancelCreate();
    await router.push({ name: 'role', params: { id: created.id }, query: { created: '1' } });
  } catch (e) {
    createError.value = e.message || 'Create failed';
  } finally {
    creating.value = false;
  }
}

function openDelete(role) {
  deleteDialog.role = role;
  deleteDialog.error = '';
  deleteDialog.open = true;
}

async function confirmDelete() {
  if (!deleteDialog.role) return;
  deleteDialog.loading = true;
  deleteDialog.error = '';
  try {
    await deleteRole(deleteDialog.role.id);
    deleteDialog.open = false;
    toast.message = t('role_deleted', 'Role "%s" was deleted.', deleteDialog.role.name);
    toast.color = 'success';
    toast.open = true;
    await load();
  } catch (e) {
    deleteDialog.error = e.message || 'Delete failed';
  } finally {
    deleteDialog.loading = false;
  }
}

onMounted(() => {
  const flash = typeof history !== 'undefined' ? history.state?.flashSuccess : null;
  if (flash) {
    toast.message = String(flash);
    toast.color = 'success';
    toast.open = true;
    // Clear so refresh does not re-show
    if (typeof history !== 'undefined' && history.replaceState) {
      const rest = { ...(history.state || {}) };
      delete rest.flashSuccess;
      history.replaceState(rest, '');
    }
  }
  load();
});
</script>

<style scoped>
.perm-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.perm-breadcrumbs :deep(.v-breadcrumbs-item),
.perm-breadcrumbs :deep(.v-breadcrumbs-divider) {
  opacity: 0.7;
}

.perm-roles-sections {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.perm-section-header {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.perm-roles-table :deep(th) {
  font-size: 0.75rem !important;
  font-weight: 600 !important;
  letter-spacing: 0.02em;
  color: rgba(var(--v-theme-on-surface), 0.6) !important;
  white-space: nowrap;
}

.perm-col-name {
  width: 28%;
  min-width: 160px;
}

.perm-col-users {
  width: 1%;
  white-space: nowrap;
}

.perm-delete-text {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.perm-col-actions {
  width: 1%;
  white-space: nowrap;
}

.perm-row--system {
  background: rgba(var(--v-theme-on-surface), 0.015);
}

.perm-roles-table :deep(td) {
  vertical-align: middle;
}

.perm-role-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}

.perm-role-link:hover {
  text-decoration: underline;
}
</style>
