<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="perm-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex flex-wrap align-start justify-space-between ga-3 mb-2">
      <div>
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
          {{ t('manage_permissions', 'Manage permissions') }}
        </h1>
        <p v-if="role" class="text-body-2 text-medium-emphasis mb-0 text-capitalize">
          {{ t('permissions_global_permissions_title', 'Global permissions - %s', role.name) }}
        </p>
      </div>
      <v-btn variant="text" prepend-icon="mdi-arrow-left" :to="{ name: 'roles-list' }">
        {{ t('Manage roles', 'Manage roles') }}
      </v-btn>
    </div>

    <v-alert
      v-if="loadError"
      type="error"
      class="perm-flash-alert"
      closable
      @click:close="loadError = ''"
    >
      {{ loadError }}
    </v-alert>
    <v-alert
      v-if="saveError"
      type="error"
      class="perm-flash-alert"
      closable
      @click:close="saveError = ''"
    >
      {{ saveError }}
    </v-alert>
    <v-alert
      v-if="saveOk"
      type="success"
      class="perm-flash-alert"
      closable
      @click:close="saveOk = false"
    >
      {{ t('form_update_success', 'Your changes have been saved!') }}
    </v-alert>

    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-4" />

    <v-row v-else-if="role" align="start">
      <v-col cols="12" md="3">
        <v-card elevation="1" class="perm-roles-nav">
          <div class="perm-section-header px-4 py-3">
            <div class="text-subtitle-2 font-weight-semibold">{{ t('roles', 'Roles') }}</div>
          </div>
          <v-divider />
          <v-list density="compact" nav class="py-1">
            <v-list-item
              v-for="r in roles"
              :key="r.id"
              :active="String(r.id) === String(id)"
              :to="{ name: 'role-permissions', params: { id: r.id } }"
              class="text-capitalize"
              rounded="0"
            >
              <template #prepend>
                <v-icon
                  size="18"
                  :icon="
                    r.is_locked || r.is_admin
                      ? 'mdi-shield-lock-outline'
                      : 'mdi-account-key-outline'
                  "
                  class="me-1 text-medium-emphasis"
                />
              </template>
              <v-list-item-title>{{ r.name }}</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <v-col cols="12" md="9">
        <div class="perm-matrix-sections">
          <v-card elevation="1" class="pa-4">
            <div class="text-body-2 text-medium-emphasis">
              <template v-if="collectionsNote.before || collectionsNote.after">
                {{ collectionsNote.before }}
                <a :href="collectionsUrl" class="text-decoration-none">
                  {{ t('permissions_collections_page_link', 'Collections page') }}
                </a>
                {{ collectionsNote.after }}
              </template>
              <template v-else>
                {{
                  t(
                    'permissions_site_wide_collection_note',
                    'To manage access per collection for studies and licensed requests, visit the Collections page.'
                  )
                }}
              </template>
            </div>
            <v-alert
              v-if="role.is_locked"
              type="warning"
              class="mt-3 mb-0"
              density="compact"
            >
              {{ t('system_role_locked', 'System roles cannot be edited.') }}
            </v-alert>
          </v-card>

          <v-card
            v-for="res in resourceDefs"
            :key="res.resource"
            elevation="1"
          >
            <div class="perm-section-header px-4 py-3 d-flex align-center justify-space-between ga-2 flex-wrap">
              <div class="min-w-0">
                <div class="text-subtitle-2 font-weight-semibold text-high-emphasis">
                  {{ res.title || res.resource }}
                </div>
                <div class="text-caption text-medium-emphasis mt-1 text-uppercase">
                  {{ res.resource }}
                </div>
              </div>
              <v-btn
                v-if="!role.is_locked && res.permissions?.length"
                variant="text"
                size="small"
                class="text-none flex-shrink-0"
                @click="toggleResourceAll(res)"
              >
                {{
                  isResourceFullyChecked(res)
                    ? t('clear_all', 'Clear all')
                    : t('select_all', 'Select all')
                }}
              </v-btn>
            </div>
            <v-divider />
            <div class="pa-2">
              <div
                v-for="perm in res.permissions"
                :key="`${res.resource}.${perm.permission}`"
                class="perm-row d-flex align-start ga-3 py-2 px-3"
              >
                <v-checkbox
                  :model-value="isChecked(res.resource, perm.permission)"
                  :disabled="role.is_locked"
                  density="compact"
                  hide-details
                  class="flex-grow-0 mt-0"
                  @update:model-value="(v) => setChecked(res.resource, perm.permission, v)"
                />
                <div class="min-w-0 pt-1">
                  <div class="text-body-2 text-capitalize font-weight-medium">
                    {{ perm.permission }}
                  </div>
                  <div v-if="perm.description" class="text-caption text-medium-emphasis">
                    {{ perm.description }}
                  </div>
                </div>
              </div>
              <div
                v-if="!res.permissions?.length"
                class="text-caption text-medium-emphasis px-3 py-4"
              >
                {{ t('no_permissions', 'No permissions defined for this resource.') }}
              </div>
            </div>
          </v-card>

          <div class="perm-form-actions d-flex flex-wrap ga-2">
            <v-btn
              color="primary"
              :loading="saving"
              :disabled="role.is_locked"
              @click="submit"
            >
              {{ t('submit', 'Submit') }}
            </v-btn>
            <v-btn variant="text" :to="{ name: 'roles-list' }">
              {{ t('cancel', 'Cancel') }}
            </v-btn>
          </div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { usePermissionsApi } from '../composables/usePermissionsApi';

defineOptions({ name: 'RolePermissionsPage' });

const props = defineProps({
  id: { type: [String, Number], required: true },
});

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { listRoles, listResources, getRolePermissions, saveRolePermissions } = usePermissionsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const collectionsUrl = computed(() => `${siteBaseUrl.value}/admin/collections`);
const collectionsNote = computed(() => {
  const tpl = t(
    'permissions_site_wide_collection_note',
    'To manage access per collection for studies and licensed requests, visit the %s.'
  );
  const parts = String(tpl).split('%s');
  return {
    before: parts[0] || '',
    after: parts.length > 1 ? parts.slice(1).join('%s') : '',
  };
});
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Manage roles', 'Manage roles'), to: { name: 'roles-list' } },
  { title: t('manage_permissions', 'Manage permissions'), disabled: true },
]);

const roles = ref([]);
const resourceDefs = ref([]);
const role = ref(null);
const grants = reactive({});
const pageLoading = ref(true);
const loadError = ref('');
const saving = ref(false);
const saveError = ref('');
const saveOk = ref(false);

function clearGrants() {
  Object.keys(grants).forEach((k) => delete grants[k]);
}

function applyGrants(map) {
  clearGrants();
  Object.entries(map || {}).forEach(([resource, perms]) => {
    grants[resource] = Array.isArray(perms) ? [...perms] : [];
  });
}

function isChecked(resource, permission) {
  const list = grants[resource];
  return Array.isArray(list) && list.includes(permission);
}

function setChecked(resource, permission, checked) {
  const current = Array.isArray(grants[resource]) ? [...grants[resource]] : [];
  const idx = current.indexOf(permission);
  if (checked && idx === -1) current.push(permission);
  if (!checked && idx !== -1) current.splice(idx, 1);
  if (current.length) grants[resource] = current;
  else delete grants[resource];
}

function isResourceFullyChecked(res) {
  const perms = res.permissions || [];
  if (!perms.length) return false;
  return perms.every((p) => isChecked(res.resource, p.permission));
}

function toggleResourceAll(res) {
  const fully = isResourceFullyChecked(res);
  (res.permissions || []).forEach((p) => {
    setChecked(res.resource, p.permission, !fully);
  });
}

async function load() {
  pageLoading.value = true;
  loadError.value = '';
  saveOk.value = false;
  saveError.value = '';
  try {
    const [roleList, defs, detail] = await Promise.all([
      listRoles(),
      listResources(),
      getRolePermissions(props.id),
    ]);
    roles.value = roleList;
    resourceDefs.value = defs;
    role.value = detail.role;
    applyGrants(detail.resources);
  } catch (e) {
    role.value = null;
    loadError.value = e.message || 'Failed to load';
  } finally {
    pageLoading.value = false;
  }
}

async function submit() {
  if (!role.value || role.value.is_locked) return;
  saving.value = true;
  saveError.value = '';
  saveOk.value = false;
  try {
    const payload = {};
    Object.entries(grants).forEach(([resource, perms]) => {
      if (Array.isArray(perms) && perms.length) payload[resource] = [...perms];
    });
    const saved = await saveRolePermissions(props.id, payload);
    role.value = saved.role;
    applyGrants(saved.resources);
    saveOk.value = true;
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  } catch (e) {
    saveError.value = e.message || 'Save failed';
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  } finally {
    saving.value = false;
  }
}

watch(
  () => props.id,
  () => load(),
  { immediate: true }
);
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

.perm-flash-alert {
  margin-top: 0.75rem;
  margin-bottom: 1rem;
}

.perm-section-header {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.perm-matrix-sections {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.perm-roles-nav {
  position: sticky;
  top: 12px;
}

.perm-row {
  border-radius: 4px;
}

.perm-row:hover {
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.perm-form-actions {
  margin-top: 0.25rem;
  padding-top: 0.25rem;
}
</style>
