<template>
  <div class="perm-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="perm-breadcrumbs pa-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="perm-page-header">
      <div class="min-w-0">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis text-capitalize">
          {{ role ? role.name : t('role', 'Role') }}
        </h1>
        <div v-if="role" class="perm-header-meta text-body-2 text-medium-emphasis">
          <v-chip v-if="role.is_system" size="x-small" variant="tonal" color="secondary">
            {{ t('system', 'System') }}
          </v-chip>
          <span>{{ role.description || t('no_description', 'No description') }}</span>
        </div>
      </div>
      <v-btn variant="text" prepend-icon="mdi-arrow-left" :to="{ name: 'roles-list' }">
        {{ t('Manage roles', 'Manage roles') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>
    <v-alert v-if="saveError" type="error" closable @click:close="saveError = ''">
      {{ saveError }}
    </v-alert>

    <v-progress-linear v-if="pageLoading" indeterminate color="primary" />

    <v-row v-else-if="role" align="start">
      <v-col cols="12" md="8" lg="9" class="perm-stack">
        <v-alert v-if="role.is_admin" type="info" variant="tonal" density="compact">
          {{ t('admin_role_full_access', 'Administrators have full access to every resource. This role cannot be edited.') }}
        </v-alert>
        <v-alert v-else-if="role.is_system" type="warning" variant="tonal" density="compact">
          {{ t('system_role_locked', 'System roles cannot be edited.') }}
        </v-alert>

        <v-card elevation="1" class="pa-4 text-body-2 text-medium-emphasis">
          {{ collectionsNote.before }}
          <a :href="collectionsUrl" class="text-decoration-none">
            {{ t('permissions_collections_page_link', 'Collections page') }}
          </a>
          {{ collectionsNote.after }}
        </v-card>

        <v-card v-for="res in resourceDefs" :key="res.resource" elevation="1">
          <div class="perm-section-header perm-resource-header px-4 py-3">
            <div class="min-w-0">
              <div class="text-subtitle-2 font-weight-semibold text-high-emphasis">
                {{ res.title || res.resource }}
              </div>
              <div class="text-caption text-medium-emphasis text-uppercase">
                {{ res.resource }}
              </div>
            </div>
            <div v-if="res.permissions?.length" class="perm-resource-tools">
              <span class="text-caption text-medium-emphasis">
                {{ t('permissions_granted_count', '%d of %d', grantedCount(res), res.permissions.length) }}
              </span>
              <v-btn
                v-if="!readOnly"
                variant="text"
                size="small"
                class="text-none"
                @click="toggleResourceAll(res)"
              >
                {{ isResourceFullyChecked(res) ? t('clear_all', 'Clear all') : t('select_all', 'Select all') }}
              </v-btn>
            </div>
          </div>
          <v-divider />
          <div class="pa-2 perm-grid">
            <div
              v-for="perm in res.permissions"
              :key="`${res.resource}.${perm.permission}`"
              class="perm-row d-flex align-start ga-3 py-2 px-3"
            >
              <v-checkbox
                :model-value="isChecked(res.resource, perm.permission)"
                :disabled="readOnly"
                density="compact"
                hide-details
                class="flex-grow-0"
                @update:model-value="(v) => setChecked(res.resource, perm.permission, v)"
              />
              <div class="min-w-0 pt-1">
                <div class="text-body-2 text-capitalize font-weight-medium">{{ perm.permission }}</div>
                <div v-if="perm.description" class="text-caption text-medium-emphasis">
                  {{ perm.description }}
                </div>
              </div>
            </div>
            <div v-if="!res.permissions?.length" class="perm-grid-empty text-caption text-medium-emphasis px-3 py-4">
              {{ t('no_permissions', 'No permissions defined for this resource.') }}
            </div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="4" lg="3">
        <div class="perm-sidebar perm-stack">
          <v-card elevation="1">
            <div class="perm-section-header px-4 py-3 text-subtitle-2 font-weight-semibold">
              {{ t('role_details', 'Role details') }}
            </div>
            <v-divider />
            <div class="pa-4 perm-stack">
              <div>
                <div class="perm-field-label">{{ t('Role name', 'Role name') }}</div>
                <v-text-field
                  v-model="form.name"
                  variant="outlined"
                  density="compact"
                  maxlength="100"
                  autocomplete="off"
                  :disabled="readOnly"
                  :error-messages="nameError"
                  hide-details="auto"
                />
              </div>
              <div>
                <div class="perm-field-label">{{ t('description', 'Description') }}</div>
                <v-textarea
                  v-model="form.description"
                  variant="outlined"
                  density="compact"
                  rows="3"
                  auto-grow
                  maxlength="255"
                  :disabled="readOnly"
                  hide-details="auto"
                />
              </div>
              <div>
                <div class="perm-field-label">{{ t('weight', 'Weight') }}</div>
                <v-text-field
                  v-model.number="form.weight"
                  variant="outlined"
                  density="compact"
                  type="number"
                  min="0"
                  max="999"
                  :disabled="readOnly"
                  :error-messages="weightError"
                  hide-details="auto"
                  class="perm-weight-field"
                />
              </div>
            </div>
            <v-divider />
            <div class="pa-4 perm-stack">
              <div class="perm-users-line text-body-2">
                <v-icon size="18" icon="mdi-account-multiple-outline" class="text-medium-emphasis" />
                <a :href="usersUrl" class="text-decoration-none">
                  {{
                    role.user_count === 1
                      ? t('role_user_count_one', '1 user has this role')
                      : t('role_user_count', '%d users have this role', role.user_count)
                  }}
                </a>
              </div>
              <template v-if="!readOnly">
                <div class="perm-save-row">
                  <v-btn
                    color="primary"
                    variant="flat"
                    class="flex-grow-1"
                    :loading="saving"
                    :disabled="!isDirty || !formValid"
                    @click="save"
                  >
                    {{ t('save_changes', 'Save changes') }}
                  </v-btn>
                  <v-btn v-if="isDirty" variant="text" :disabled="saving" @click="discard">
                    {{ t('discard', 'Discard') }}
                  </v-btn>
                </div>
                <div v-if="isDirty" class="text-caption text-warning">
                  {{ t('unsaved_changes_note', 'You have unsaved changes.') }}
                </div>
              </template>
            </div>
            <template v-if="!role.is_system">
              <v-divider />
              <div class="pa-4">
                <v-btn
                  block
                  variant="outlined"
                  color="error"
                  prepend-icon="mdi-delete-outline"
                  :loading="deleting"
                  :disabled="saving"
                  @click="remove"
                >
                  {{ t('delete_role', 'Delete role') }}
                </v-btn>
              </div>
            </template>
          </v-card>

          <v-card v-if="roles.length" elevation="1" class="perm-roles-card">
            <div class="perm-section-header px-4 py-3 text-subtitle-2 font-weight-semibold">
              {{ t('roles', 'Roles') }}
            </div>
            <v-divider />
            <v-list density="compact" nav class="perm-roles-list py-1">
              <v-list-item
                v-for="r in roles"
                :key="r.id"
                :active="r.id === role.id"
                :to="{ name: 'role', params: { id: r.id } }"
                color="primary"
                rounded="0"
              >
                <template #prepend>
                  <v-icon
                    size="18"
                    :icon="r.is_system ? 'mdi-shield-lock-outline' : 'mdi-account-key-outline'"
                    class="perm-roles-list-icon"
                  />
                </template>
                <v-list-item-title class="text-capitalize">{{ r.name }}</v-list-item-title>
                <template #append>
                  <span class="text-caption text-medium-emphasis">{{ r.user_count }}</span>
                </template>
              </v-list-item>
            </v-list>
          </v-card>
        </div>
      </v-col>
    </v-row>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave, onBeforeRouteUpdate } from 'vue-router';
import $dialog from '@/shared/composables/dialog';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { usePermissionsApi } from '../composables/usePermissionsApi';

defineOptions({ name: 'RolePage' });

const props = defineProps({
  id: { type: [String, Number], required: true },
});

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { listRoles, listResources, getRolePermissions, saveRole, deleteRole } = usePermissionsApi();

const NAME_PATTERN = /^[A-Za-z0-9 ]+$/;

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const collectionsUrl = computed(() => `${siteBaseUrl.value}/admin/collections`);
const usersUrl = computed(() => `${siteBaseUrl.value}/admin/users?role=${encodeURIComponent(String(props.id))}`);
const collectionsNote = computed(() => {
  const parts = String(
    t(
      'permissions_site_wide_collection_note',
      'To manage access per collection for studies and licensed requests, visit the %s.'
    )
  ).split('%s');
  return { before: parts[0] || '', after: parts.slice(1).join('%s') };
});

const roles = ref([]);
const resourceDefs = ref([]);
const role = ref(null);
const grants = reactive({});
const form = reactive({ name: '', description: '', weight: 0 });
const original = ref({ name: '', description: '', weight: 0, grants: '{}' });
const pageLoading = ref(true);
const loadError = ref('');
const saving = ref(false);
const saveError = ref('');
const deleting = ref(false);
const toast = reactive({ open: false, message: '', color: 'success' });
let skipLeaveGuard = false;

const readOnly = computed(() => !role.value || role.value.is_system);
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Manage roles', 'Manage roles'), to: { name: 'roles-list' } },
  { title: role.value ? role.value.name : '…', disabled: true },
]);

const nameError = computed(() => {
  const name = form.name.trim();
  if (!name) return t('role_name_required', 'Enter a role name.');
  if (!NAME_PATTERN.test(name)) {
    return t('role_name_invalid', 'Role names can only contain letters, numbers and spaces (up to 100 characters).');
  }
  return '';
});
const weightError = computed(() => {
  const w = form.weight;
  if (w === '' || w === null) return '';
  return Number.isInteger(w) && w >= 0 && w <= 999
    ? ''
    : t('role_weight_invalid', 'Weight must be a whole number from 0 to 999.');
});
const formValid = computed(() => !nameError.value && !weightError.value);

/** Only grants for permissions defined in config are sent or compared. */
function knownGrants() {
  const out = {};
  resourceDefs.value.forEach((res) => {
    const allowed = (res.permissions || []).map((p) => p.permission);
    const picked = (grants[res.resource] || []).filter((p) => allowed.includes(p)).sort();
    if (picked.length) out[res.resource] = picked;
  });
  return out;
}

const grantsKey = computed(() => JSON.stringify(knownGrants()));
const detailsDirty = computed(
  () =>
    form.name.trim() !== original.value.name ||
    form.description.trim() !== original.value.description ||
    normalizedWeight() !== original.value.weight
);
const permissionsDirty = computed(() => grantsKey.value !== original.value.grants);
const isDirty = computed(() => !readOnly.value && (detailsDirty.value || permissionsDirty.value));

function normalizedWeight() {
  return form.weight === '' || form.weight === null ? 0 : form.weight;
}

function applyGrants(map) {
  Object.keys(grants).forEach((k) => delete grants[k]);
  Object.entries(map || {}).forEach(([resource, perms]) => {
    grants[resource] = Array.isArray(perms) ? [...perms] : [];
  });
}

function applyRole(r, resources) {
  role.value = r;
  form.name = r.name || '';
  form.description = r.description || '';
  form.weight = Number.isInteger(Number(r.weight)) ? Number(r.weight) : 0;
  applyGrants(resources);
  original.value = {
    name: form.name.trim(),
    description: form.description.trim(),
    weight: form.weight,
    grants: grantsKey.value,
  };
}

function isChecked(resource, permission) {
  return Array.isArray(grants[resource]) && grants[resource].includes(permission);
}

function setChecked(resource, permission, checked) {
  const current = Array.isArray(grants[resource]) ? [...grants[resource]] : [];
  const idx = current.indexOf(permission);
  if (checked && idx === -1) current.push(permission);
  if (!checked && idx !== -1) current.splice(idx, 1);
  if (current.length) grants[resource] = current;
  else delete grants[resource];
}

function grantedCount(res) {
  return (res.permissions || []).filter((p) => isChecked(res.resource, p.permission)).length;
}

function isResourceFullyChecked(res) {
  const perms = res.permissions || [];
  return perms.length > 0 && perms.every((p) => isChecked(res.resource, p.permission));
}

function toggleResourceAll(res) {
  const fully = isResourceFullyChecked(res);
  (res.permissions || []).forEach((p) => setChecked(res.resource, p.permission, !fully));
}

function showToast(message, color = 'success') {
  toast.message = message;
  toast.color = color;
  toast.open = true;
}

async function load() {
  pageLoading.value = true;
  loadError.value = '';
  saveError.value = '';
  try {
    const [roleList, defs, detail] = await Promise.all([
      listRoles(),
      resourceDefs.value.length ? Promise.resolve(resourceDefs.value) : listResources(),
      getRolePermissions(props.id),
    ]);
    roles.value = roleList;
    resourceDefs.value = defs;
    applyRole(detail.role, detail.resources);
  } catch (e) {
    role.value = null;
    loadError.value = e.message;
  } finally {
    pageLoading.value = false;
  }
}

async function save() {
  if (!isDirty.value || !formValid.value) return;
  saving.value = true;
  saveError.value = '';
  const payload = {};
  if (detailsDirty.value) {
    payload.role = form.name.trim();
    payload.description = form.description.trim();
    payload.weight = normalizedWeight();
  }
  if (permissionsDirty.value) {
    payload.resources = knownGrants();
  }
  try {
    const saved = await saveRole(props.id, payload);
    roles.value = roles.value.map((r) => (r.id === saved.role.id ? saved.role : r));
    applyRole(saved.role, saved.resources);
    showToast(t('form_update_success', 'Your changes have been saved!'));
  } catch (e) {
    saveError.value = e.message;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } finally {
    saving.value = false;
  }
}

async function discard() {
  const ok = await $dialog.confirm({
    title: t('discard_changes', 'Discard changes'),
    message: t('discard_changes_confirm', 'Discard your unsaved changes to this role?'),
    confirmText: t('discard', 'Discard'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  form.name = original.value.name;
  form.description = original.value.description;
  form.weight = original.value.weight;
  applyGrants(JSON.parse(original.value.grants));
}

async function remove() {
  if (!role.value || role.value.is_system) return;
  const count = role.value.user_count || 0;
  const lines = [t('delete_role_confirm', 'Delete the role "%s"? This cannot be undone.', role.value.name)];
  if (count > 0) {
    lines.push(
      count === 1
        ? t('delete_role_users_one', '1 user will lose this role and its permissions.')
        : t('delete_role_users', '%d users will lose this role and its permissions.', count)
    );
  }
  const ok = await $dialog.confirm({
    title: t('delete_role', 'Delete role'),
    message: lines.join('\n\n'),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;

  deleting.value = true;
  saveError.value = '';
  try {
    await deleteRole(role.value.id);
    skipLeaveGuard = true;
    await router.push({
      name: 'roles-list',
      state: { flashSuccess: t('role_deleted', 'Role "%s" was deleted.', role.value.name) },
    });
  } catch (e) {
    saveError.value = e.message;
  } finally {
    deleting.value = false;
  }
}

async function confirmLeave() {
  if (skipLeaveGuard || !isDirty.value) return true;
  return $dialog.confirm({
    title: t('unsaved_changes', 'Unsaved changes'),
    message: t('unsaved_changes_leave', 'You have unsaved changes to this role. Leave without saving?'),
    confirmText: t('leave', 'Leave'),
    cancelText: t('stay', 'Stay'),
  });
}

onBeforeRouteLeave(() => confirmLeave());
onBeforeRouteUpdate((to, from) => (to.params.id === from.params.id ? true : confirmLeave()));

function onBeforeUnload(e) {
  if (isDirty.value) {
    e.preventDefault();
    e.returnValue = '';
  }
}

onMounted(() => window.addEventListener('beforeunload', onBeforeUnload));
onBeforeUnmount(() => window.removeEventListener('beforeunload', onBeforeUnload));

watch(
  () => props.id,
  async () => {
    await load();
    if (route.query.created && role.value) {
      showToast(t('role_created_next', 'Role created. Now choose its permissions.'));
      router.replace({ name: 'role', params: { id: props.id } });
    }
  },
  { immediate: true }
);
</script>

<style scoped>
.perm-page-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.perm-header-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 4px;
}

.perm-resource-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
}

.perm-resource-tools {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.perm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 2px 8px;
}

.perm-grid-empty {
  grid-column: 1 / -1;
}

.perm-row {
  border-radius: 4px;
}

.perm-row:hover {
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.perm-sidebar {
  position: sticky;
  top: 12px;
  max-height: calc(100vh - 24px);
  overflow-y: auto;
}

.perm-sidebar > * {
  flex-shrink: 0;
}

.perm-sidebar > .perm-roles-card {
  flex: 1 1 auto;
  min-height: 200px;
  display: flex;
  flex-direction: column;
}

.perm-roles-list {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
}

.perm-roles-list-icon {
  margin-inline-end: 8px;
  opacity: 0.6;
}

.perm-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.9);
  padding-bottom: 4px;
}

.perm-weight-field {
  max-width: 140px;
}

.perm-users-line {
  display: flex;
  align-items: center;
  gap: 8px;
}

.perm-save-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
