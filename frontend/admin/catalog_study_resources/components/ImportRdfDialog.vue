<template>
  <v-dialog :model-value="modelValue" max-width="480" persistent @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title class="text-h6 d-flex align-center justify-space-between">
        <span>{{ lbl.import_rdf || 'Import RDF' }}</span>
        <v-btn icon variant="text" size="small" @click="close">
          <v-icon icon="mdi-close" />
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-4">
        <v-alert v-if="error" type="error" density="compact" class="mb-3" closable @click:close="error = ''">
          {{ error }}
        </v-alert>
        <div class="csr-field">
          <div class="csr-field-label">{{ lbl.choose_file || 'RDF file' }}</div>
          <v-file-input
            v-model="files"
            accept=".rdf,application/rdf+xml,text/xml"
            variant="outlined"
            density="compact"
            prepend-icon=""
            prepend-inner-icon="mdi-file-upload-outline"
            show-size
            hide-details="auto"
          />
        </div>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-3">
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="close">{{ lbl.cancel || 'Cancel' }}</v-btn>
        <v-btn color="primary" :loading="saving" :disabled="!selectedFile" @click="submit">
          {{ lbl.import || 'Import' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyResourcesApi } from '../composables/useStudyResourcesApi';

defineOptions({ name: 'ImportRdfDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'imported']);

const { config } = useAppConfig();
const { importRdf } = useStudyResourcesApi();
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
    const data = await importRdf(selectedFile.value);
    emit('imported', data);
    close();
  } catch (e) {
    error.value = e?.message || 'Import failed';
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
.csr-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  margin-bottom: 4px;
  color: rgba(var(--v-theme-on-surface), 0.9);
}
</style>
