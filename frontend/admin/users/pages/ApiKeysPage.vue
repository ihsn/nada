<template>
  <div class="users-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="users-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center flex-wrap ga-2">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 flex-grow-1">
        {{ t('api_keys_management', 'API keys') }}
      </h1>
      <v-btn variant="outlined" size="small" prepend-icon="mdi-text-box-search" :href="`${siteBase}/admin/api_logs`">
        {{ t('api_logs', 'API logs') }}
      </v-btn>
      <v-btn variant="text" size="small" prepend-icon="mdi-arrow-left" :to="{ name: 'users-list' }">
        {{ t('back_to_users', 'Back to users') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" density="compact">{{ loadError }}</v-alert>

    <v-card class="pa-4" elevation="1">
      <v-row dense align="end">
        <v-col cols="12" md="4">
          <div class="users-field-label">{{ t('user_search', 'User') }}</div>
          <v-text-field
            v-model="filters.user"
            :placeholder="t('username_or_email', 'Username or email')"
            maxlength="100"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            @keyup.enter="applyFilters"
          />
        </v-col>
        <v-col cols="12" md="3">
          <div class="users-field-label">{{ t('key_prefix', 'Key prefix') }}</div>
          <v-text-field
            v-model="filters.prefix"
            maxlength="12"
            :rules="[prefixRule]"
            density="compact"
            variant="outlined"
            hide-details="auto"
            clearable
            @keyup.enter="applyFilters"
          />
        </v-col>
        <v-col cols="12" md="3">
          <div class="users-field-label">{{ t('status', 'Status') }}</div>
          <v-select v-model="filters.status" :items="statusOptions" density="compact" variant="outlined" hide-details />
        </v-col>
        <v-col cols="12" md="2">
          <v-btn color="primary" block @click="applyFilters">{{ t('search', 'Search') }}</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="1">
      <v-data-table-server
        v-model:page="page"
        v-model:items-per-page="pageSize"
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="rows"
        :items-length="total"
        :loading="loading"
        :items-per-page-options="[25, 50, 100]"
        item-value="id"
        density="comfortable"
        @update:options="load"
      >
        <template #item.key_prefix="{ item }">
          <code>{{ item.prefix }}</code>
          <div v-if="item.name" class="text-caption text-medium-emphasis">{{ item.name }}</div>
        </template>
        <template #item.username="{ item }">
          <router-link
            v-if="item.user_id"
            :to="{ name: 'user-edit', params: { id: item.user_id } }"
            class="text-primary text-decoration-none"
          >
            {{ item.user_display_name }}
          </router-link>
          <span v-else class="text-medium-emphasis">{{ t('deleted_user', 'Deleted user') }}</span>
          <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
        </template>
        <template #item.status="{ item }"><ApiKeyStatusChip :row="item" /></template>
        <template #item.date_created="{ item }">{{ formatDate(item.date_created) }}</template>
        <template #item.expires_at="{ item }">{{ item.expires_at ? formatDate(item.expires_at) : '—' }}</template>
        <template #item.last_used_at="{ item }">
          {{ item.last_used_at ? formatDate(item.last_used_at) : t('never', 'Never') }}
        </template>
        <template #item.actions="{ item }">
          <ApiKeyActions :row="item" @changed="onChanged" @error="(m) => notify(m, 'error')" />
        </template>
        <template #no-data>
          <div class="py-6 text-medium-emphasis">{{ t('no_api_keys_found', 'No API keys found.') }}</div>
        </template>
      </v-data-table-server>
    </v-card>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useUsersApi, apiErrorMessage, formatDate } from '../composables/useUsersApi';
import ApiKeyActions from '../components/ApiKeyActions.vue';
import ApiKeyStatusChip from '../components/ApiKeyStatusChip.vue';

defineOptions({ name: 'ApiKeysPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const api = useUsersApi();

const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('users', 'Users'), to: { name: 'users-list' } },
  { title: t('api_keys', 'API keys'), disabled: true },
]);

const PREFIX_PATTERN = /^[A-Za-z0-9_-]*$/;

const route = useRoute();
const initialUser = typeof route.query.user === 'string' ? route.query.user.slice(0, 100) : '';
const filters = reactive({ user: initialUser, prefix: '', status: 'active' });
const applied = ref({ ...filters });
const page = ref(1);
const pageSize = ref(25);
const sortBy = ref([{ key: 'date_created', order: 'desc' }]);
const rows = ref([]);
const total = ref(0);
const loading = ref(false);
const loadError = ref('');
const toast = ref({ open: false, message: '', color: 'success' });

const statusOptions = computed(() => [
  { title: t('active', 'Active'), value: 'active' },
  { title: t('expired', 'Expired'), value: 'expired' },
  { title: t('revoked', 'Revoked'), value: 'revoked' },
  { title: t('all', 'All'), value: 'all' },
]);

const headers = computed(() => [
  { title: t('api_key', 'Key'), key: 'key_prefix', sortable: true },
  { title: t('user', 'User'), key: 'username', sortable: true },
  { title: t('status', 'Status'), key: 'status', sortable: false, width: '130px' },
  { title: t('created', 'Created'), key: 'date_created', sortable: true, width: '120px' },
  { title: t('expires', 'Expires'), key: 'expires_at', sortable: true, width: '120px' },
  { title: t('last_used', 'Last used'), key: 'last_used_at', sortable: true, width: '120px' },
  { title: '', key: 'actions', sortable: false, width: '60px', align: 'end' },
]);

function prefixRule(v) {
  return PREFIX_PATTERN.test(v || '') || t('invalid_key_prefix', 'Letters, numbers, - and _ only.');
}

function notify(message, color = 'success') {
  toast.value = { open: true, message, color };
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    const sort = sortBy.value[0] || { key: 'date_created', order: 'desc' };
    const params = {
      page: page.value,
      ps: pageSize.value,
      status: applied.value.status,
      sort_by: sort.key,
      sort_order: sort.order === 'asc' ? 'asc' : 'desc',
    };
    if (applied.value.user) params.user = applied.value.user;
    if (applied.value.prefix) params.prefix = applied.value.prefix;
    const result = await api.listApiKeys(params);
    rows.value = result.rows || [];
    total.value = result.total || 0;
  } catch (e) {
    rows.value = [];
    total.value = 0;
    loadError.value = apiErrorMessage(e, t);
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  const prefix = (filters.prefix || '').trim();
  if (!PREFIX_PATTERN.test(prefix)) return;
  applied.value = { user: (filters.user || '').trim(), prefix, status: filters.status };
  if (page.value !== 1) page.value = 1;
  else load();
}

function onChanged(message) {
  notify(message);
  load();
}
</script>
