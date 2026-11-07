<template>
  <v-dialog
    :model-value="modelValue"
    max-width="480"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h6 d-flex align-center justify-space-between">
        <span>{{ lbl.replace_ddi || 'Replace DDI' }}</span>
        <v-btn icon variant="text" size="small" :disabled="saving" @click="close">
          <v-icon icon="mdi-close" />
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="px-6 py-5">
        <v-alert v-if="error" type="error" density="compact" class="mb-4" closable @click:close="error = ''">
          {{ error }}
        </v-alert>
        <div v-if="studyTitle" class="text-body-2 mb-6 text-medium-emphasis">
          {{ studyTitle }}
        </div>
        <div class="csb-field">
          <div class="csb-field-label">{{ lbl.msg_select_ddi || 'Select DDI file' }}</div>
          <v-file-input
            v-model="files"
            accept=".xml,application/xml,text/xml"
            variant="outlined"
            density="compact"
            prepend-icon=""
            prepend-inner-icon="mdi-file-upload-outline"
            show-size
            hide-details="auto"
            :disabled="saving"
          />
        </div>
      </v-card-text>
      <v-divider />
      <v-card-actions class="px-6 py-4">
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="close">{{ lbl.cancel || 'Cancel' }}</v-btn>
        <v-btn color="primary" :loading="saving" :disabled="!selectedFile" @click="submit">
          {{ lbl.submit || 'Submit' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCatalogStudySidebarApi } from '../composables/useCatalogStudySidebarApi';

defineOptions({ name: 'ReplaceDdiDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  studyTitle: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'replaced']);

const { config } = useAppConfig();
const { replaceDdi } = useCatalogStudySidebarApi();
const lbl = computed(() => config.value?.labels || {});

const files = ref([]);
const saving = ref(false);
const error = ref('');

const selectedFile = computed(() => {
  const f = files.value;
  if (Array.isArray(f)) return f[0] || null;
  return f || null;
});

function close() {
  emit('update:modelValue', false);
}

async function submit() {
  if (!selectedFile.value) return;
  saving.value = true;
  error.value = '';
  try {
    const data = await replaceDdi(selectedFile.value);
    emit('replaced', data);
    close();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Replace DDI failed';
  } finally {
    saving.value = false;
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (!open) {
      files.value = [];
      error.value = '';
    }
  }
);
</script>

<style scoped>
.csb-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  margin-bottom: 6px;
  color: rgba(var(--v-theme-on-surface), 0.9);
}
</style>
