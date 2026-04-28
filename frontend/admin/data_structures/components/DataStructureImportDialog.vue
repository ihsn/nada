<template>
  <v-dialog :model-value="modelValue" max-width="560" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>Import DSD from SDMX XML</v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-3">
          SDMX-ML structure message (same family as OECD / Eurostat <code>Structure</code> exports). Imports codelists
          from the file, then the first <code>DataStructure</code> (or the one you specify by id).
        </p>
        <v-file-input
          v-model="file"
          label="Structure XML file"
          accept=".xml,text/xml,application/xml"
          prepend-icon="mdi-file-xml-box"
          show-size
          density="comfortable"
        />
        <v-switch
          v-model="overwriteCodelists"
          color="primary"
          inset
          label="Overwrite existing codelists"
          hint="When on, replaces codes for codelists that already exist. When off, reuses them as-is."
          persistent-hint
          class="mt-2"
        />
        <v-text-field
          v-model="dsdId"
          label="Data structure id (optional)"
          hint="Match DataStructure @id if the file contains more than one."
          persistent-hint
          density="comfortable"
          class="mt-4"
        />
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
  saving.value = true;
  try {
    const fd = new FormData();
    fd.append('file', f);
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
