<template>
  <v-dialog :model-value="modelValue" max-width="560" persistent>
    <v-card>
      <v-card-title class="text-subtitle-1">{{ t('new_api_key_generated', 'New API key') }}</v-card-title>
      <v-card-text>
        <v-alert type="warning" variant="tonal" density="compact" class="mb-3">
          {{ t('key_will_not_be_shown_again', 'Copy this key now. It will not be shown again.') }}
        </v-alert>
        <v-text-field
          :model-value="secret"
          readonly
          density="compact"
          variant="outlined"
          hide-details
          class="api-key-secret"
          append-inner-icon="mdi-content-copy"
          @click:append-inner="copy"
          @focus="$event.target.select()"
        />
        <div v-if="copied" class="text-caption text-success mt-1">{{ t('api_key_copied', 'Copied to clipboard.') }}</div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="primary" variant="flat" @click="close">{{ t('done', 'Done') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'ApiKeySecretDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  secret: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'closed']);
const { t } = useI18n();
const copied = ref(false);

watch(
  () => props.modelValue,
  () => {
    copied.value = false;
  }
);

async function copy() {
  try {
    await navigator.clipboard.writeText(props.secret);
    copied.value = true;
  } catch {
    copied.value = false;
  }
}

function close() {
  emit('update:modelValue', false);
  emit('closed');
}
</script>

<style>
.api-key-secret input {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}
</style>
