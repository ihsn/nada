<template>
  <v-card class="pa-4" elevation="1">
    <div class="d-flex align-center mb-2">
      <div class="text-subtitle-1 font-weight-medium flex-grow-1">
        {{ t('api_keys', 'API keys') }}
        <span class="text-caption text-medium-emphasis">({{ keys.length }} / {{ maxKeys }})</span>
      </div>
      <v-btn
        v-if="canManage"
        size="small"
        variant="outlined"
        prepend-icon="mdi-key-plus"
        :disabled="keys.length >= maxKeys"
        @click="createOpen = true"
      >
        {{ t('generate_api_key', 'Generate API key') }}
      </v-btn>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-2" />
    <div v-else-if="!keys.length" class="text-body-2 text-medium-emphasis py-2">
      {{ t('no_api_keys_found', 'No API keys.') }}
    </div>
    <v-table v-else density="compact">
      <thead>
        <tr>
          <th>{{ t('api_key', 'Key') }}</th>
          <th>{{ t('name', 'Name') }}</th>
          <th>{{ t('status', 'Status') }}</th>
          <th>{{ t('created', 'Created') }}</th>
          <th>{{ t('expires', 'Expires') }}</th>
          <th>{{ t('last_used', 'Last used') }}</th>
          <th />
        </tr>
      </thead>
      <tbody>
        <tr v-for="k in keys" :key="k.id">
          <td class="text-no-wrap"><code>{{ k.prefix }}</code></td>
          <td>{{ k.name }}</td>
          <td><ApiKeyStatusChip :row="k" /></td>
          <td class="text-no-wrap">{{ formatDate(k.date_created) }}</td>
          <td class="text-no-wrap">{{ k.expires_at ? formatDate(k.expires_at) : '—' }}</td>
          <td class="text-no-wrap">{{ k.last_used_at ? formatDate(k.last_used_at) : t('never', 'Never') }}</td>
          <td class="text-end">
            <ApiKeyActions :row="k" @changed="onChanged" @error="(m) => notify(m, 'error')" />
          </td>
        </tr>
      </tbody>
    </v-table>

    <CreateApiKeyDialog
      v-model="createOpen"
      :user-id="userId"
      :max-days="maxDays"
      @created="onCreated"
      @error="(m) => notify(m, 'error')"
    />
    <ApiKeySecretDialog v-model="secretOpen" :secret="secret" @closed="secret = ''" />

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </v-card>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useUsersApi, apiErrorMessage, formatDate } from '../composables/useUsersApi';
import ApiKeyActions from './ApiKeyActions.vue';
import ApiKeyStatusChip from './ApiKeyStatusChip.vue';
import ApiKeySecretDialog from './ApiKeySecretDialog.vue';
import CreateApiKeyDialog from './CreateApiKeyDialog.vue';

defineOptions({ name: 'UserApiKeysCard' });

const props = defineProps({
  userId: { type: Number, required: true },
  canManage: { type: Boolean, default: false },
  maxKeys: { type: Number, default: 5 },
  maxDays: { type: Number, default: 730 },
});
const { t } = useI18n();
const api = useUsersApi();

const keys = ref([]);
const loading = ref(false);
const createOpen = ref(false);
const secretOpen = ref(false);
const secret = ref('');
const toast = ref({ open: false, message: '', color: 'success' });

function notify(message, color = 'success') {
  toast.value = { open: true, message, color };
}

async function load() {
  loading.value = true;
  try {
    keys.value = await api.userApiKeys(props.userId);
  } catch (e) {
    notify(apiErrorMessage(e, t), 'error');
  } finally {
    loading.value = false;
  }
}

function onCreated({ secret: value }) {
  secret.value = value;
  secretOpen.value = true;
  load();
}

function onChanged(message) {
  notify(message);
  load();
}

onMounted(load);
</script>
