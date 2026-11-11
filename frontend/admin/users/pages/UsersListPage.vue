<template>
  <div class="users-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="users-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center flex-wrap ga-2">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 flex-grow-1">
        {{ t('title_user_management', 'User management') }}
      </h1>
      <v-btn
        v-if="perms.can_create"
        color="primary"
        size="small"
        prepend-icon="mdi-account-plus"
        :to="{ name: 'user-create' }"
      >
        {{ t('create_user_account', 'Create user account') }}
      </v-btn>
      <v-btn variant="outlined" size="small" prepend-icon="mdi-key" :to="{ name: 'api-keys' }">
        {{ t('api_keys_management', 'API keys') }}
      </v-btn>
      <v-btn
        v-if="perms.is_site_admin"
        variant="outlined"
        size="small"
        prepend-icon="mdi-account-group"
        :href="`${siteBase}/admin/permissions`"
      >
        {{ t('User roles', 'User roles') }}
      </v-btn>
      <v-menu>
        <template #activator="{ props: menuProps }">
          <v-btn v-bind="menuProps" variant="outlined" size="small" prepend-icon="mdi-download">
            {{ t('export', 'Export') }}
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item :href="`${siteBase}/admin/users/export/csv`" :title="t('export_to_csv', 'Export to CSV')" />
          <v-list-item :href="`${siteBase}/admin/users/export/json`" :title="t('export_to_json', 'Export to JSON')" />
        </v-list>
      </v-menu>
    </div>

    <v-alert v-if="loadError" type="error" density="compact">{{ loadError }}</v-alert>

    <v-card class="pa-4" elevation="1">
      <v-row dense align="end">
        <v-col cols="12" md="4">
          <div class="users-field-label">{{ t('search', 'Search') }}</div>
          <div class="d-flex ga-2">
            <v-text-field
              v-model="filters.keywords"
              density="compact"
              variant="outlined"
              hide-details
              clearable
              maxlength="100"
              prepend-inner-icon="mdi-magnify"
              @keyup.enter="applyFilters"
            />
            <v-select
              v-model="filters.field"
              :items="fieldOptions"
              density="compact"
              variant="outlined"
              hide-details
              style="max-width: 150px"
            />
          </div>
        </v-col>
        <v-col cols="6" md="2">
          <div class="users-field-label">{{ t('status', 'Status') }}</div>
          <v-select v-model="filters.status" :items="statusOptions" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="6" md="2">
          <div class="users-field-label">{{ t('role', 'Role') }}</div>
          <v-select v-model="filters.role" :items="roleOptions" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="6" md="2">
          <div class="users-field-label">{{ t('last_login', 'Last login') }}</div>
          <v-select v-model="filters.last_login" :items="lastLoginOptions" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="6" md="2">
          <div class="users-field-label">{{ t('collection_access', 'Collection access') }}</div>
          <v-select
            v-model="filters.collection_access"
            :items="collectionAccessOptions"
            density="compact"
            variant="outlined"
            hide-details
          />
        </v-col>
        <v-col cols="6" md="2">
          <div class="users-field-label">{{ t('api_keys', 'API keys') }}</div>
          <v-select v-model="filters.api_keys" :items="apiKeyOptions" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="12" md="auto" class="d-flex ga-2">
          <v-btn color="primary" @click="applyFilters">{{ t('search', 'Search') }}</v-btn>
          <v-btn v-if="hasFilters" variant="text" @click="clearFilters">{{ t('clear_filters', 'Clear filters') }}</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="1">
      <div v-if="selected.length" class="d-flex align-center flex-wrap ga-2 px-4 py-2 bg-blue-lighten-5">
        <span class="text-body-2 me-2">{{ t('n_selected', '%d selected', selected.length) }}</span>
        <template v-if="perms.can_edit">
          <v-btn size="small" variant="text" prepend-icon="mdi-check" :disabled="busy" @click="runBatch('activate')">
            {{ t('activate_users', 'Activate') }}
          </v-btn>
          <v-btn size="small" variant="text" prepend-icon="mdi-cancel" :disabled="busy" @click="runBatch('deactivate')">
            {{ t('deactivate_users', 'Deactivate') }}
          </v-btn>
          <v-btn size="small" variant="text" prepend-icon="mdi-account-multiple-plus" :disabled="busy" @click="openRoleDialog('assign_roles')">
            {{ t('assign_roles', 'Assign roles') }}
          </v-btn>
          <v-btn size="small" variant="text" prepend-icon="mdi-account-multiple-minus" :disabled="busy" @click="openRoleDialog('remove_roles')">
            {{ t('remove_roles', 'Remove roles') }}
          </v-btn>
        </template>
        <v-btn
          v-if="perms.can_delete"
          size="small"
          variant="text"
          color="error"
          prepend-icon="mdi-delete"
          :disabled="busy"
          @click="runBatch('delete')"
        >
          {{ t('delete_users', 'Delete') }}
        </v-btn>
        <v-btn size="small" variant="text" :disabled="busy" @click="selected = []">
          {{ t('clear_selection', 'Clear selection') }}
        </v-btn>
      </div>

      <v-data-table-server
        v-model="selected"
        v-model:page="page"
        v-model:items-per-page="pageSize"
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="rows"
        :items-length="total"
        :loading="loading"
        :items-per-page-options="[25, 50, 100]"
        :show-select="canBulk"
        :item-selectable="(item) => item.can_edit && !item.is_self"
        item-value="id"
        density="comfortable"
        @update:options="load"
      >
        <template #item.username="{ item }">
          <router-link :to="{ name: 'user-edit', params: { id: item.id } }" class="text-primary text-decoration-none font-weight-medium">
            {{ item.username }}
          </router-link>
          <v-chip v-if="item.is_self" size="x-small" class="ms-1" variant="tonal">{{ t('you', 'You') }}</v-chip>
        </template>
        <template #item.roles="{ item }">
          <div class="users-badges">
            <span
              v-for="role in item.roles"
              :key="`r${role.id}`"
              class="users-badge"
              :class="{ 'users-badge--admin': role.is_admin }"
            >
              {{ t(role.name, role.name) }}
            </span>
            <a
              v-for="c in item.collections"
              :key="`c${c.id}`"
              class="users-badge users-badge--collection"
              :title="t('collection_access', 'Collection access')"
              :href="`${siteBase}/admin/collections#/permissions/${Number(c.id)}`"
            >
              <v-icon icon="mdi-folder-account" size="14" />{{ c.title || c.repositoryid }}
            </a>
          </div>
        </template>
        <template #item.active="{ item }">
          <v-chip size="x-small" :color="statusColor(item.status)" variant="flat">{{ statusLabel(item.status) }}</v-chip>
        </template>
        <template #item.created_on="{ item }">{{ formatDate(item.created_on) }}</template>
        <template #item.last_login="{ item }">
          <span v-if="item.last_login">{{ formatDate(item.last_login) }}</span>
          <span v-else class="text-medium-emphasis">-</span>
        </template>
        <template #item.api_key_count="{ item }">
          <router-link
            v-if="item.api_key_count"
            :to="{ name: 'user-api-keys', params: { id: String(item.id) } }"
            class="text-primary text-decoration-none"
          >
            {{ item.api_key_count }}
          </router-link>
          <span v-else>0</span>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex justify-end">
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              :title="item.can_edit ? t('edit', 'Edit') : t('view', 'View')"
              :to="{ name: 'user-edit', params: { id: item.id } }"
            />
            <v-btn
              v-if="item.can_delete"
              icon="mdi-delete"
              size="small"
              variant="text"
              color="error"
              :title="t('delete', 'Delete')"
              @click="deleteOne(item)"
            />
          </div>
        </template>
        <template #no-data>
          <div class="py-6 text-medium-emphasis">{{ t('no_records_found', 'No records found') }}</div>
        </template>
      </v-data-table-server>
    </v-card>

    <v-dialog v-model="roleDialog.open" max-width="480">
      <v-card>
        <v-card-title class="text-subtitle-1">
          {{ roleDialog.action === 'assign_roles' ? t('assign_roles', 'Assign roles') : t('remove_roles', 'Remove roles') }}
        </v-card-title>
        <v-card-text>
          <div class="text-body-2 mb-2">{{ t('n_selected', '%d selected', selected.length) }}</div>
          <v-checkbox
            v-for="role in assignableRoles"
            :key="role.id"
            v-model="roleDialog.roleIds"
            :value="role.id"
            :label="t(role.name, role.name)"
            density="compact"
            hide-details
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="roleDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn
            color="primary"
            variant="flat"
            :disabled="!roleDialog.roleIds.length || busy"
            @click="confirmRoleDialog"
          >
            {{ t('apply', 'Apply') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="5000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import $dialog from '@/shared/composables/dialog';
import { useUsersApi, apiErrorMessage, formatDate } from '../composables/useUsersApi';

defineOptions({ name: 'UsersListPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const api = useUsersApi();
const route = useRoute();
const router = useRouter();

const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('users', 'Users'), disabled: true },
]);

const FILTER_KEYS = ['keywords', 'field', 'status', 'role', 'last_login', 'collection_access', 'api_keys'];
const SORT_KEYS = ['username', 'email', 'country', 'active', 'created_on', 'last_login'];

function filtersFromQuery() {
  const q = route.query;
  const str = (k) => (typeof q[k] === 'string' ? q[k] : '');
  return {
    keywords: str('keywords'),
    field: str('field') || 'all',
    status: str('status'),
    role: str('role') ? Number(str('role')) || '' : '',
    last_login: str('last_login'),
    collection_access: str('collection_access'),
    api_keys: str('api_keys'),
  };
}

const filters = reactive(filtersFromQuery());
const appliedFilters = ref({ ...filters });
const page = ref(Math.max(1, Number(route.query.page) || 1));
const pageSize = ref([25, 50, 100].includes(Number(route.query.ps)) ? Number(route.query.ps) : 25);
const sortBy = ref([
  {
    key: SORT_KEYS.includes(String(route.query.sort_by)) ? String(route.query.sort_by) : 'username',
    order: route.query.sort_order === 'desc' ? 'desc' : 'asc',
  },
]);

const rows = ref([]);
const total = ref(0);
const loading = ref(false);
const busy = ref(false);
const loadError = ref('');
const selected = ref([]);
const options = ref(null);
const toast = ref({ open: false, message: '', color: 'success' });
const roleDialog = reactive({ open: false, action: 'assign_roles', roleIds: [] });

const perms = computed(() => options.value?.permissions || {});
const canBulk = computed(() => !!(perms.value.can_edit || perms.value.can_delete));
const assignableRoles = computed(() => {
  const ids = new Set(options.value?.assignable_role_ids || []);
  return (options.value?.roles || []).filter((r) => ids.has(r.id));
});
const hasFilters = computed(() =>
  FILTER_KEYS.some((k) => k !== 'field' && appliedFilters.value[k] !== '' && appliedFilters.value[k] != null)
);

const fieldOptions = computed(() => [
  { title: t('all_fields', 'All fields'), value: 'all' },
  { title: t('username', 'Username'), value: 'username' },
  { title: t('email', 'Email'), value: 'email' },
  { title: t('first_name', 'First name'), value: 'first_name' },
  { title: t('last_name', 'Last name'), value: 'last_name' },
  { title: t('company', 'Organization'), value: 'company' },
  { title: t('country', 'Country'), value: 'country' },
]);
const statusOptions = computed(() => [
  { title: t('all', 'All'), value: '' },
  { title: t('active', 'Active'), value: 'active' },
  { title: t('disabled', 'Disabled'), value: 'disabled' },
  { title: t('pending_activation', 'Pending activation'), value: 'pending' },
]);
const roleOptions = computed(() => [
  { title: t('all', 'All'), value: '' },
  ...(options.value?.roles || []).map((r) => ({ title: t(r.name, r.name), value: r.id })),
]);
const lastLoginOptions = computed(() => [
  { title: t('any_time', 'Any time'), value: '' },
  { title: t('today', 'Today'), value: 'today' },
  { title: t('last_7_days', 'Last 7 days'), value: 'week' },
  { title: t('last_30_days', 'Last 30 days'), value: 'month' },
  { title: t('never', 'Never'), value: 'never' },
]);
const collectionAccessOptions = computed(() => [
  { title: t('all', 'All'), value: '' },
  { title: t('has_collection_access', 'Has collection access'), value: 'has' },
  { title: t('no_collection_access', 'No collection access'), value: 'none' },
]);
const apiKeyOptions = computed(() => [
  { title: t('all', 'All'), value: '' },
  { title: t('has_api_keys', 'Has API keys'), value: 'has' },
  { title: t('no_api_keys', 'No API keys'), value: 'none' },
]);

const headers = computed(() => [
  { title: t('username', 'Username'), key: 'username', sortable: true },
  { title: t('email', 'Email'), key: 'email', sortable: true },
  { title: t('roles_and_collections', 'Roles and collections'), key: 'roles', sortable: false },
  { title: t('status', 'Status'), key: 'active', sortable: true, width: '110px' },
  { title: t('api_keys', 'API keys'), key: 'api_key_count', sortable: false, width: '90px', align: 'center' },
  { title: t('country', 'Country'), key: 'country', sortable: true },
  { title: t('join_date', 'Join date'), key: 'created_on', sortable: true, width: '120px' },
  { title: t('last_login', 'Last login'), key: 'last_login', sortable: true, width: '120px' },
  { title: t('actions', 'Actions'), key: 'actions', sortable: false, width: '100px', align: 'end' },
]);

function statusLabel(status) {
  if (status === 'active') return t('active', 'Active');
  if (status === 'pending') return t('pending_activation', 'Pending activation');
  return t('disabled', 'Disabled');
}

function statusColor(status) {
  if (status === 'active') return 'success';
  if (status === 'pending') return 'warning';
  return 'grey';
}

function showToast(message, color = 'success') {
  toast.value = { open: true, message, color };
}

function syncQuery() {
  const query = {};
  FILTER_KEYS.forEach((k) => {
    const v = appliedFilters.value[k];
    if (v !== '' && v != null && !(k === 'field' && v === 'all')) query[k] = String(v);
  });
  if (page.value > 1) query.page = String(page.value);
  if (pageSize.value !== 25) query.ps = String(pageSize.value);
  const sort = sortBy.value[0];
  if (sort && (sort.key !== 'username' || sort.order === 'desc')) {
    query.sort_by = sort.key;
    query.sort_order = sort.order === 'desc' ? 'desc' : 'asc';
  }
  router.replace({ query });
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    if (!options.value) options.value = await api.options();
    const sort = sortBy.value[0] || { key: 'username', order: 'asc' };
    const params = { page: page.value, ps: pageSize.value, sort_by: sort.key, sort_order: sort.order === 'desc' ? 'desc' : 'asc' };
    FILTER_KEYS.forEach((k) => {
      const v = appliedFilters.value[k];
      if (v !== '' && v != null) params[k] = v;
    });
    if (!params.keywords) delete params.field;
    const result = await api.listUsers(params);
    rows.value = result.rows || [];
    total.value = result.total || 0;
    const ids = new Set(rows.value.map((r) => r.id));
    selected.value = selected.value.filter((id) => ids.has(id));
    syncQuery();
  } catch (e) {
    rows.value = [];
    total.value = 0;
    loadError.value = apiErrorMessage(e, t);
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  appliedFilters.value = { ...filters, keywords: (filters.keywords || '').trim() };
  selected.value = [];
  if (page.value !== 1) page.value = 1;
  else load();
}

function clearFilters() {
  Object.assign(filters, { keywords: '', field: 'all', status: '', role: '', last_login: '', collection_access: '', api_keys: '' });
  applyFilters();
}

function batchSummary(res) {
  const changed = (res.changed || []).length;
  const skipped = res.skipped || [];
  let msg = t('n_users_updated', '%d user(s) updated.', changed);
  if (skipped.length) {
    const protectedCount = skipped.filter((s) => s.reason === 'protected_admin').length;
    msg += ` ${t('n_users_skipped', '%d skipped.', skipped.length)}`;
    if (protectedCount) msg += ` ${t('admins_skipped_hint', 'Administrator accounts can only be changed by a site administrator.')}`;
  }
  return { msg, color: skipped.length ? 'warning' : 'success' };
}

function roleNames(roleIds) {
  const byId = new Map((options.value?.roles || []).map((r) => [r.id, t(r.name, r.name)]));
  return roleIds.map((id) => byId.get(id)).filter(Boolean).join(', ');
}

function batchConfirmation(action, count, roleIds) {
  switch (action) {
    case 'activate':
      return {
        title: t('activate_users', 'Activate users'),
        message: t('confirm_activate_users', 'Activate %d user account(s)? They will be able to sign in.', count),
        confirmText: t('activate', 'Activate'),
      };
    case 'deactivate':
      return {
        title: t('deactivate_users', 'Deactivate users'),
        message: t('confirm_deactivate_users', 'Deactivate %d user account(s)? They will be signed out and unable to sign in.', count),
        confirmText: t('deactivate', 'Deactivate'),
      };
    case 'assign_roles':
      return {
        title: t('assign_roles', 'Assign roles'),
        message: t('confirm_assign_roles', 'Add these roles to %d user account(s)?\n%s', count, roleNames(roleIds)),
        confirmText: t('assign_roles', 'Assign roles'),
      };
    case 'remove_roles':
      return {
        title: t('remove_roles', 'Remove roles'),
        message: t('confirm_remove_roles', 'Remove these roles from %d user account(s)?\n%s', count, roleNames(roleIds)),
        confirmText: t('remove_roles', 'Remove roles'),
      };
    default:
      return {
        title: t('delete_users', 'Delete users'),
        message: t('confirm_delete_users', 'Permanently delete %d user account(s)? Their roles, collection access and API keys are removed too.', count),
        confirmText: t('delete', 'Delete'),
      };
  }
}

async function runBatch(action, roleIds = []) {
  if (!selected.value.length) return;
  const ok = await $dialog.confirm({
    ...batchConfirmation(action, selected.value.length, roleIds),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  busy.value = true;
  try {
    const res = await api.batch({ action, user_ids: selected.value, role_ids: roleIds });
    const { msg, color } = batchSummary(res);
    showToast(msg, color);
    selected.value = [];
    await load();
  } catch (e) {
    showToast(apiErrorMessage(e, t), 'error');
  } finally {
    busy.value = false;
  }
}

function openRoleDialog(action) {
  roleDialog.action = action;
  roleDialog.roleIds = [];
  roleDialog.open = true;
}

async function confirmRoleDialog() {
  const ids = [...roleDialog.roleIds];
  roleDialog.open = false;
  await runBatch(roleDialog.action, ids);
}

async function deleteOne(item) {
  const ok = await $dialog.confirm({
    title: t('delete_user', 'Delete user'),
    message: t('confirm_delete_user', 'Permanently delete the account "%s"? Their roles, collection access and API keys are removed too.', item.username),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  try {
    await api.deleteUser(item.id);
    showToast(t('user_deleted', 'User deleted.'));
    await load();
  } catch (e) {
    showToast(apiErrorMessage(e, t), 'error');
  }
}
</script>
