<template>
  <div class="d-inline-flex">
    <v-menu v-if="row.can_manage || row.can_delete">
      <template #activator="{ props: menuProps }">
        <v-btn v-bind="menuProps" icon="mdi-dots-vertical" size="small" variant="text" :loading="busy" :title="t('manage', 'Manage')" />
      </template>
      <v-list density="compact">
        <v-list-item
          v-if="row.can_manage && !row.is_legacy"
          prepend-icon="mdi-calendar-plus"
          :title="t('extend_expiry', 'Extend expiry')"
          @click="extendOpen = true"
        />
        <v-list-item
          v-if="row.can_manage"
          prepend-icon="mdi-cancel"
          :title="t('revoke_api_key', 'Revoke')"
          @click="revoke"
        />
        <v-list-item
          v-if="row.can_delete"
          prepend-icon="mdi-delete"
          base-color="error"
          :title="t('delete_api_key', 'Delete permanently')"
          @click="remove"
        />
      </v-list>
    </v-menu>

    <v-dialog v-model="extendOpen" max-width="400">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('extend_expiry', 'Extend expiry') }}</v-card-title>
        <v-card-text>
          <div class="text-body-2 mb-3">
            {{ t('current_expiry', 'Current expiry') }}: {{ row.expires_at ? formatDate(row.expires_at) : t('never', 'Never') }}
          </div>
          <v-select v-model="months" :items="monthOptions" density="compact" variant="outlined" hide-details />
          <div class="text-caption text-medium-emphasis mt-2">
            {{ t('api_key_extend_limit', 'Keys cannot be set to expire more than two years from today.') }}
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="extendOpen = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="primary" variant="flat" :loading="busy" @click="extend">{{ t('extend', 'Extend') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import $dialog from '@/shared/composables/dialog';
import { useUsersApi, apiErrorMessage, formatDate } from '../composables/useUsersApi';

defineOptions({ name: 'ApiKeyActions' });

const props = defineProps({
  /** Row from the API (_format_api_key_rows). */
  row: { type: Object, required: true },
});
const emit = defineEmits(['changed', 'error']);
const { t } = useI18n();
const api = useUsersApi();

const busy = ref(false);
const extendOpen = ref(false);
const months = ref(12);

const monthOptions = computed(() =>
  [1, 3, 6, 12, 24].map((m) => ({ title: m === 1 ? t('one_month', '1 month') : t('n_months', '%d months', m), value: m }))
);

function label() {
  return props.row.name ? `${props.row.prefix} (${props.row.name})` : props.row.prefix;
}

async function run(fn, message) {
  busy.value = true;
  try {
    await fn();
    emit('changed', message);
  } catch (e) {
    emit('error', apiErrorMessage(e, t));
  } finally {
    busy.value = false;
  }
}

async function revoke() {
  const ok = await $dialog.confirm({
    title: t('revoke_api_key', 'Revoke API key'),
    message: t('confirm_revoke_api_key', 'Revoke the key %s? Requests using it will be refused straight away.', label()),
    confirmText: t('revoke', 'Revoke'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (ok) await run(() => api.revokeApiKey(props.row.id), t('api_key_revoked', 'API key revoked.'));
}

async function remove() {
  const ok = await $dialog.confirm({
    title: t('delete_api_key', 'Delete API key'),
    message: t('confirm_permanent_delete_api_key', 'Permanently delete the key %s? This cannot be undone.', label()),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (ok) await run(() => api.deleteApiKey(props.row.id), t('api_key_deleted', 'API key deleted.'));
}

async function extend() {
  await run(() => api.extendApiKey(props.row.id, months.value), t('api_key_extended', 'Expiry extended.'));
  extendOpen.value = false;
}
</script>
