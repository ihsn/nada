<template>
  <v-dialog :model-value="modelValue" max-width="560" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>Import DSD from SDMX XML</v-card-title>
      <v-card-text>
        <div class="text-caption text-medium-emphasis mb-1">Structure XML file</div>
        <v-file-input
          v-model="file"
          variant="outlined"
          density="compact"
          accept=".xml,text/xml,application/xml"
          prepend-icon=""
          prepend-inner-icon="mdi-file-xml-box"
          show-size
          hide-details
          :disabled="saving"
        />
        <v-switch
          v-model="overwriteCodelists"
          color="primary"
          inset
          label="Overwrite existing codelists"
          hint="When on, replaces codes for codelists that already exist. When off, reuses them as-is."
          persistent-hint
          class="mt-2"
          :disabled="saving"
        />
        <div class="text-caption text-medium-emphasis mt-3 mb-1">Data structure id (optional)</div>
        <v-text-field
          v-model="dsdId"
          variant="outlined"
          density="compact"
          hide-details
          :disabled="saving"
        />
        <div class="text-caption text-medium-emphasis mt-1">
          Match DataStructure @id if the file contains more than one.
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="$emit('update:modelValue', false)">Cancel</v-btn>
        <v-btn color="primary" :loading="saving" :disabled="!file" @click="submit">Import</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, inject } from 'vue';
import { useDataStructuresApi } from '../composables/useDataStructuresApi';

defineOptions({ name: 'DataStructureImportDialog' });

const setMessage = inject('setMessage', () => {});

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'imported']);

const { importFromSdmxXml } = useDataStructuresApi();

const file = ref(null);
const overwriteCodelists = ref(false);
const dsdId = ref('');
const saving = ref(false);

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      file.value = null;
      overwriteCodelists.value = false;
      dsdId.value = '';
    }
  }
);

async function submit() {
  const f = file.value;
  if (!f) return;
  const rawFile = Array.isArray(f) ? f[0] : f;
  if (!rawFile) return;
  saving.value = true;
  try {
    const fd = new FormData();
    fd.append('file', rawFile);
    fd.append('overwrite_codelists', overwriteCodelists.value ? '1' : '0');
    const id = dsdId.value?.trim();
    if (id) fd.append('dsd_id', id);
    const result = await importFromSdmxXml(fd);
    emit('imported', result);
    emit('update:modelValue', false);
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Import failed', 'error');
  } finally {
    saving.value = false;
  }
}
</script>
