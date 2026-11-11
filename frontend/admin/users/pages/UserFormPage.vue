<template>
  <div class="users-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="users-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center flex-wrap ga-2">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 flex-grow-1">
        {{ isEdit ? t('edit_user_account', 'Edit user account') : t('create_user_account', 'Create user account') }}
      </h1>
      <v-btn variant="text" size="small" prepend-icon="mdi-arrow-left" :to="{ name: 'users-list' }">
        {{ t('back_to_users', 'Back to users') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" density="compact">{{ loadError }}</v-alert>
    <v-alert v-if="justCreated" type="success" density="compact" closable>
      {{ t('user_created', 'User account created.') }}
    </v-alert>
    <v-alert v-if="isEdit && user && readOnly" type="info" variant="tonal" density="compact">
      {{
        user.is_protected_admin
          ? t('protected_account', 'Only a site administrator can change an administrator account.')
          : t('read_only_account', 'You can view this account but not change it.')
      }}
    </v-alert>

    <v-progress-linear v-if="loading" indeterminate color="primary" />

    <v-form v-if="!loading && !loadError" ref="formRef" @submit.prevent="save">
      <v-row>
        <v-col cols="12" :lg="isEdit ? 8 : 12" class="users-stack">
          <v-card class="pa-4" elevation="1">
            <div class="text-subtitle-1 font-weight-medium mb-3">{{ t('account', 'Account') }}</div>
            <v-row dense>
              <v-col cols="12" md="6">
                <div class="users-field-label">{{ t('username', 'Username') }} *</div>
                <v-text-field
                  v-model="form.username"
                  :readonly="readOnly"
                  :maxlength="isEdit ? 100 : 20"
                  :error-messages="fieldErrors.username"
                  density="compact"
                  variant="outlined"
                  autocomplete="off"
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="users-field-label">{{ t('email', 'Email') }} *</div>
                <v-text-field
                  v-model="form.email"
                  :readonly="readOnly"
                  maxlength="100"
                  type="email"
                  :error-messages="fieldErrors.email"
                  density="compact"
                  variant="outlined"
                  autocomplete="off"
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="users-field-label">{{ t('first_name', 'First name') }} *</div>
                <v-text-field
                  v-model="form.first_name"
                  :readonly="readOnly"
                  maxlength="50"
                  :error-messages="fieldErrors.first_name"
                  density="compact"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="users-field-label">{{ t('last_name', 'Last name') }} *</div>
                <v-text-field
                  v-model="form.last_name"
                  :readonly="readOnly"
                  maxlength="50"
                  :error-messages="fieldErrors.last_name"
                  density="compact"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="users-field-label">{{ t('company', 'Organization') }}</div>
                <v-text-field
                  v-model="form.company"
                  :readonly="readOnly"
                  maxlength="100"
                  :error-messages="fieldErrors.company"
                  density="compact"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3">
                <div class="users-field-label">{{ t('phone', 'Phone') }}</div>
                <v-text-field
                  v-model="form.phone"
                  :readonly="readOnly"
                  maxlength="20"
                  :error-messages="fieldErrors.phone"
                  density="compact"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="3">
                <div class="users-field-label">{{ t('country', 'Country') }}</div>
                <v-autocomplete
                  v-model="form.country"
                  :items="countryItems"
                  :readonly="readOnly"
                  :error-messages="fieldErrors.country"
                  density="compact"
                  variant="outlined"
                  clearable
                />
              </v-col>
            </v-row>
          </v-card>

          <v-card v-if="!readOnly" class="pa-4" elevation="1">
            <div class="text-subtitle-1 font-weight-medium mb-1">{{ t('password', 'Password') }}</div>
            <div v-if="isEdit" class="text-caption text-medium-emphasis mb-3">
              {{ t('leave_password_blank', 'Leave blank to keep the current password.') }}
            </div>
            <v-row dense>
              <v-col cols="12" md="6">
                <div class="users-field-label">{{ t('password', 'Password') }}{{ isEdit ? '' : ' *' }}</div>
                <v-text-field
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  :maxlength="opts.password_max_length || undefined"
                  :error-messages="fieldErrors.password"
                  :hint="passwordHint"
                  persistent-hint
                  density="compact"
                  variant="outlined"
                  autocomplete="new-password"
                  @click:append-inner="showPassword = !showPassword"
                />
              </v-col>
              <v-col cols="12" md="6">
                <div class="users-field-label">{{ t('password_confirmation', 'Confirm password') }}{{ isEdit ? '' : ' *' }}</div>
                <v-text-field
                  v-model="form.password_confirm"
                  :type="showPassword ? 'text' : 'password'"
                  :error-messages="fieldErrors.password_confirm"
                  density="compact"
                  variant="outlined"
                  autocomplete="new-password"
                />
              </v-col>
            </v-row>
          </v-card>

          <v-card class="pa-4" elevation="1">
            <div class="text-subtitle-1 font-weight-medium mb-2">{{ t('user_account_status', 'Account status') }}</div>
            <v-switch
              v-model="form.active"
              :readonly="readOnly"
              :disabled="isSelf"
              color="success"
              density="compact"
              hide-details
              :label="form.active ? t('user_active', 'Active') : t('user_blocked', 'Disabled')"
            />
            <div v-if="isSelf" class="text-caption text-medium-emphasis mt-1">
              {{ t('cannot_disable_own_account', 'You cannot disable your own account.') }}
            </div>
          </v-card>

          <v-card class="pa-4" elevation="1">
            <div class="text-subtitle-1 font-weight-medium mb-2">{{ t('User roles', 'User roles') }}</div>
            <div v-if="rolesLocked" class="text-caption text-medium-emphasis mb-2">
              {{ isSelf ? t('cannot_change_own_roles', 'You cannot change your own roles.') : t('roles_read_only', 'You cannot change the roles of this account.') }}
            </div>
            <v-row no-gutters>
              <v-col v-for="role in opts.roles || []" :key="role.id" cols="12" sm="6" md="4">
                <v-checkbox
                  v-model="form.role_ids"
                  :value="role.id"
                  :disabled="rolesLocked || !assignableIds.has(role.id)"
                  density="compact"
                  hide-details
                >
                  <template #label>
                    <span>{{ t(role.name, role.name) }}</span>
                    <v-chip v-if="role.is_admin" size="x-small" color="deep-orange" variant="tonal" class="ms-2">
                      {{ t('administrator', 'Administrator') }}
                    </v-chip>
                  </template>
                </v-checkbox>
              </v-col>
            </v-row>
          </v-card>

          <div class="d-flex flex-wrap ga-2">
            <v-btn v-if="!readOnly" type="submit" color="primary" :loading="saving">
              {{ isEdit ? t('update', 'Save changes') : t('create', 'Create') }}
            </v-btn>
            <v-btn variant="text" :to="{ name: 'users-list' }">{{ t('cancel', 'Cancel') }}</v-btn>
            <v-spacer />
            <v-btn
              v-if="isEdit && user && user.can_delete"
              color="error"
              variant="text"
              prepend-icon="mdi-delete"
              @click="remove"
            >
              {{ t('delete', 'Delete') }}
            </v-btn>
          </div>
        </v-col>

        <v-col v-if="isEdit && user" cols="12" lg="4">
          <v-card class="pa-4 user-info-card" elevation="1">
            <div class="d-flex align-center ga-2">
              <v-avatar color="primary" variant="tonal" size="40">
                <span class="text-subtitle-1">{{ initials }}</span>
              </v-avatar>
              <div class="flex-grow-1 user-info-name">
                <div class="text-subtitle-1 font-weight-medium">{{ displayName }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ user.email }}</div>
              </div>
            </div>

            <v-divider class="user-info-divider" />

            <dl class="user-info-list">
              <dt>{{ t('status', 'Status') }}</dt>
              <dd>
                <v-chip size="x-small" :color="statusColor" variant="flat">{{ statusLabel }}</v-chip>
                <v-chip v-if="user.is_self" size="x-small" variant="tonal" class="ms-1">{{ t('you', 'You') }}</v-chip>
              </dd>
              <dt>{{ t('user_id', 'User ID') }}</dt>
              <dd>{{ user.id }}</dd>
              <dt>{{ t('join_date', 'Join date') }}</dt>
              <dd>{{ formatDate(user.created_on, true) || '-' }}</dd>
              <dt>{{ t('last_login', 'Last login') }}</dt>
              <dd>{{ user.last_login ? formatDate(user.last_login, true) : t('never', 'Never') }}</dd>
              <dt>{{ t('User roles', 'User roles') }}</dt>
              <dd>
                <div v-if="user.roles.length" class="users-badges">
                  <span
                    v-for="role in user.roles"
                    :key="role.id"
                    class="users-badge"
                    :class="{ 'users-badge--admin': role.is_admin }"
                  >
                    {{ t(role.name, role.name) }}
                  </span>
                </div>
                <span v-else class="text-medium-emphasis">-</span>
              </dd>
              <dt>{{ t('collection_access', 'Collection access') }}</dt>
              <dd>
                <div v-if="user.collections.length" class="users-badges">
                  <a
                    v-for="c in user.collections"
                    :key="c.id"
                    class="users-badge users-badge--collection"
                    :href="`${siteBase}/admin/collections#/permissions/${Number(c.id)}`"
                  >
                    <v-icon icon="mdi-folder-account" size="14" />{{ c.title || c.repositoryid }}
                  </a>
                </div>
                <span v-else class="text-medium-emphasis">-</span>
              </dd>
              <dt>{{ t('api_keys', 'API keys') }}</dt>
              <dd>{{ t('n_active_api_keys', '%d active of %d allowed', user.api_key_count, opts.max_api_keys || 5) }}</dd>
            </dl>

            <v-btn
              block
              variant="outlined"
              size="small"
              prepend-icon="mdi-key"
              class="user-info-action"
              :to="{ name: 'user-api-keys', params: { id: String(user.id) } }"
            >
              {{ readOnly ? t('view_api_keys', 'View API keys') : t('manage_api_keys', 'Manage API keys') }}
            </v-btn>
          </v-card>
        </v-col>
      </v-row>
    </v-form>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="5000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import $dialog from '@/shared/composables/dialog';
import { useUsersApi, apiErrorMessage, apiFieldErrors, formatDate } from '../composables/useUsersApi';
defineOptions({ name: 'UserFormPage' });

const props = defineProps({
  id: { type: String, default: '' },
});

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const api = useUsersApi();
const route = useRoute();
const router = useRouter();

const PROFILE_FIELDS = ['username', 'email', 'first_name', 'last_name', 'company', 'phone', 'country'];

const isEdit = computed(() => !!props.id);
const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('users', 'Users'), to: { name: 'users-list' } },
  { title: isEdit.value ? user.value?.username || t('edit', 'Edit') : t('add', 'Add'), disabled: true },
]);

const opts = ref({});
const user = ref(null);
const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const fieldErrors = ref({});
const showPassword = ref(false);
const justCreated = ref(route.query.created === '1');
const toast = ref({ open: false, message: '', color: 'success' });
const formRef = ref(null);

const form = reactive({
  username: '',
  email: '',
  first_name: '',
  last_name: '',
  company: '',
  phone: '',
  country: '',
  password: '',
  password_confirm: '',
  active: true,
  role_ids: [],
});
/** Values as loaded, to send only changed fields. */
let original = {};

const isSelf = computed(() => !!user.value?.is_self);
const readOnly = computed(() => {
  if (!isEdit.value) return !opts.value.permissions?.can_create;
  return !user.value?.can_edit;
});
const rolesLocked = computed(() => readOnly.value || (isEdit.value && !user.value?.can_change_roles));
const assignableIds = computed(() => new Set(opts.value.assignable_role_ids || []));
const countryItems = computed(() => {
  const list = [...(opts.value.countries || [])];
  if (form.country && !list.includes(form.country)) list.unshift(form.country);
  return list;
});
const displayName = computed(() => {
  const u = user.value;
  if (!u) return '';
  return `${u.first_name || ''} ${u.last_name || ''}`.trim() || u.username;
});
const initials = computed(() => {
  const u = user.value;
  if (!u) return '';
  const parts = [u.first_name, u.last_name].filter(Boolean);
  const source = parts.length ? parts.map((p) => p.charAt(0)) : [String(u.username || '').charAt(0)];
  return source.join('').toUpperCase().slice(0, 2);
});
const statusLabel = computed(() => {
  if (user.value?.status === 'active') return t('active', 'Active');
  if (user.value?.status === 'pending') return t('pending_activation', 'Pending activation');
  return t('disabled', 'Disabled');
});
const statusColor = computed(() => {
  if (user.value?.status === 'active') return 'success';
  if (user.value?.status === 'pending') return 'warning';
  return 'grey';
});
const passwordHint = computed(() => {
  const parts = [];
  if (opts.value.password_min_length) {
    parts.push(t('password_min_chars', 'At least %d characters', opts.value.password_min_length));
  }
  if (opts.value.require_complex_password) {
    parts.push(t('password_complexity_hint', 'with upper and lower case letters, a number and a symbol'));
  }
  return parts.join(', ');
});

function fillForm(u) {
  PROFILE_FIELDS.forEach((f) => {
    form[f] = u[f] || '';
  });
  form.active = u.status === 'active';
  form.role_ids = (u.roles || []).map((r) => r.id);
  form.password = '';
  form.password_confirm = '';
  original = {
    ...Object.fromEntries(PROFILE_FIELDS.map((f) => [f, form[f]])),
    active: form.active,
    role_ids: [...form.role_ids].sort((a, b) => a - b),
  };
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    opts.value = await api.options();
    if (isEdit.value) {
      user.value = await api.getUser(props.id);
      fillForm(user.value);
    }
  } catch (e) {
    loadError.value = apiErrorMessage(e, t);
  } finally {
    loading.value = false;
  }
}

function sameIds(a, b) {
  const x = [...a].sort((m, n) => m - n);
  return x.length === b.length && x.every((v, i) => v === b[i]);
}

function buildBody() {
  const trimmed = Object.fromEntries(PROFILE_FIELDS.map((f) => [f, String(form[f] ?? '').trim()]));
  if (!isEdit.value) {
    return {
      ...trimmed,
      password: form.password,
      password_confirm: form.password_confirm,
      active: form.active,
      role_ids: form.role_ids.filter((id) => assignableIds.value.has(id)),
    };
  }
  const body = {};
  PROFILE_FIELDS.forEach((f) => {
    if (trimmed[f] !== original[f]) body[f] = trimmed[f];
  });
  if (form.password) {
    body.password = form.password;
    body.password_confirm = form.password_confirm;
  }
  if (!isSelf.value && form.active !== original.active) body.active = form.active;
  if (!rolesLocked.value && !sameIds(form.role_ids, original.role_ids)) body.role_ids = [...form.role_ids];
  return body;
}

async function save() {
  if (readOnly.value) return;
  fieldErrors.value = {};
  const body = buildBody();
  if (isEdit.value && !Object.keys(body).length) {
    toast.value = { open: true, message: t('nothing_to_update', 'There are no changes to save.'), color: 'info' };
    return;
  }
  saving.value = true;
  try {
    if (isEdit.value) {
      user.value = await api.updateUser(user.value.id, body);
      fillForm(user.value);
      toast.value = { open: true, message: t('changes_saved', 'Changes saved.'), color: 'success' };
    } else {
      const created = await api.createUser(body);
      router.push({ name: 'user-edit', params: { id: String(created.id) }, query: { created: '1' } });
    }
  } catch (e) {
    fieldErrors.value = apiFieldErrors(e);
    toast.value = { open: true, message: apiErrorMessage(e, t), color: 'error' };
  } finally {
    saving.value = false;
  }
}

async function remove() {
  const ok = await $dialog.confirm({
    title: t('delete_user', 'Delete user'),
    message: t('confirm_delete_user', 'Permanently delete the account "%s"? Their roles, collection access and API keys are removed too.', user.value.username),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  try {
    await api.deleteUser(user.value.id);
    router.push({ name: 'users-list' });
  } catch (e) {
    toast.value = { open: true, message: apiErrorMessage(e, t), color: 'error' };
  }
}

onMounted(load);
</script>

<style>
.user-info-card {
  position: sticky;
  top: 16px;
}
.user-info-name {
  min-width: 0;
  overflow-wrap: anywhere;
}
.user-info-divider {
  margin: 12px 0;
}
.user-info-list {
  display: grid;
  grid-template-columns: max-content 1fr;
  column-gap: 16px;
  row-gap: 10px;
  font-size: 0.875rem;
}
.user-info-list dt {
  color: rgba(0, 0, 0, 0.6);
}
.user-info-list dd {
  min-width: 0;
  overflow-wrap: anywhere;
}
.user-info-action {
  margin-top: 16px;
}
</style>
