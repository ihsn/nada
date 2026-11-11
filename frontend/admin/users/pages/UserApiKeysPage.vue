<template>
  <div class="users-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="users-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center flex-wrap ga-2">
      <div class="flex-grow-1">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">{{ t('api_keys', 'API keys') }}</h1>
        <div v-if="user" class="text-body-2 text-medium-emphasis">{{ user.username }} · {{ user.email }}</div>
      </div>
      <v-btn variant="text" size="small" prepend-icon="mdi-arrow-left" :to="{ name: 'user-edit', params: { id } }">
        {{ t('back_to_user', 'Back to user account') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" density="compact">{{ loadError }}</v-alert>
    <v-alert v-else-if="user && !canManage" type="info" variant="tonal" density="compact">
      {{
        user.is_protected_admin
          ? t('protected_account', 'Only a site administrator can change an administrator account.')
          : t('read_only_account', 'You can view this account but not change it.')
      }}
    </v-alert>

    <v-progress-linear v-if="loading" indeterminate color="primary" />

    <UserApiKeysCard
      v-if="user"
      :user-id="user.id"
      :can-manage="canManage"
      :max-keys="opts.max_api_keys || 5"
      :max-days="opts.api_key_max_days || 730"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useUsersApi, apiErrorMessage } from '../composables/useUsersApi';
import UserApiKeysCard from '../components/UserApiKeysCard.vue';

defineOptions({ name: 'UserApiKeysPage' });

const props = defineProps({
  id: { type: String, required: true },
});

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const api = useUsersApi();

const user = ref(null);
const opts = ref({});
const loading = ref(true);
const loadError = ref('');

const canManage = computed(() => !!user.value?.can_edit);
const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('users', 'Users'), to: { name: 'users-list' } },
  { title: user.value?.username || t('user', 'User'), to: { name: 'user-edit', params: { id: props.id } } },
  { title: t('api_keys', 'API keys'), disabled: true },
]);

onMounted(async () => {
  try {
    const [options, u] = await Promise.all([api.options(), api.getUser(props.id)]);
    opts.value = options;
    user.value = u;
  } catch (e) {
    loadError.value = apiErrorMessage(e, t);
  } finally {
    loading.value = false;
  }
});
</script>
