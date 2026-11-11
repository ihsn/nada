<template>
  <v-dialog :model-value="modelValue" max-width="480" @update:model-value="emit('update:modelValue', $event)">
    <v-card>
      <v-card-title class="text-subtitle-1">{{ t('generate_api_key', 'Generate API key') }}</v-card-title>
      <v-card-text>
        <div class="users-field-label">{{ t('name', 'Name') }}</div>
        <v-text-field
          v-model="name"
          maxlength="100"
          :placeholder="t('api_key_name_placeholder', 'What the key is used for (optional)')"
          :error-messages="errors.name"
          density="compact"
          variant="outlined"
        />
        <div class="users-field-label">{{ t('expires', 'Expires') }}</div>
        <v-select
          v-model="days"
          :items="dayOptions"
          :error-messages="errors.expires_in_days"
          density="compact"
          variant="outlined"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="emit('update:modelValue', false)">{{ t('cancel', 'Cancel') }}</v-btn>
        <v-btn color="primary" variant="flat" :loading="saving" @click="create">{{ t('generate', 'Generate') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useUsersApi, apiErrorMessage, apiFieldErrors } from '../composables/useUsersApi';

defineOptions({ name: 'CreateApiKeyDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  userId: { type: Number, required: true },
  maxDays: { type: Number, default: 730 },
});
const emit = defineEmits(['update:modelValue', 'created', 'error']);
const { t } = useI18n();
const api = useUsersApi();

const name = ref('');
const days = ref(365);
const saving = ref(false);
const errors = ref({});

const dayOptions = computed(() =>
  [30, 90, 180, 365, 730]
    .filter((d) => d <= props.maxDays)
    .map((d) => ({ title: t('n_days', '%d days', d), value: d }))
);

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      name.value = '';
      days.value = 365;
      errors.value = {};
    }
  }
);

async function create() {
  saving.value = true;
  errors.value = {};
  try {
    const res = await api.createApiKey(props.userId, { name: name.value.trim(), expires_in_days: days.value });
    emit('update:modelValue', false);
    emit('created', { key: res.key, secret: res.secret });
  } catch (e) {
    errors.value = apiFieldErrors(e);
    if (!Object.keys(errors.value).length) emit('error', apiErrorMessage(e, t));
  } finally {
    saving.value = false;
  }
}
</script>
