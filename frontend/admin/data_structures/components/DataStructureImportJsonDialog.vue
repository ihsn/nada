<template>
  <v-dialog :model-value="modelValue" max-width="560" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>Import DSD from JSON</v-card-title>
      <v-card-text>
        <div class="text-caption text-medium-emphasis mb-1">JSON file</div>
        <v-file-input
          v-model="file"
          variant="outlined"
          density="compact"
          accept=".json,application/json"
          prepend-icon=""
          prepend-inner-icon="mdi-code-json"
          show-size
          hide-details
          :disabled="saving"
        />
        <v-switch
          v-model="overwrite"
          color="primary"
          inset
          label="Overwrite (matched codelists when inline items apply)"
          hint="When off, existing catalogue codelists are reused without replacing codes."
          persistent-hint
          class="mt-2"
        />
        <v-alert
          v-if="validationErrors.length"
          type="error"
          variant="tonal"
          density="compact"
          class="mt-4"
          closable
          @click:close="validationErrors = []"
        >
          <div class="font-weight-medium mb-2">Validation issues</div>
          <ul class="pl-4 mb-0">
            <li v-for="(err, i) in validationErrors" :key="i">
              <span v-if="err.path" class="text-caption">{{ err.path }}:</span>
              {{ err.message }}
            </li>
          </ul>
        </v-alert>
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

defineOptions({ name: 'DataStructureImportJsonDialog' });

const setMessage = inject('setMessage', () => {});

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'imported']);

const { importFromJson } = useDataStructuresApi();

const file = ref(null);
const overwrite = ref(false);
const saving = ref(false);
const validationErrors = ref([]);

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      file.value = null;
      overwrite.value = false;
      validationErrors.value = [];
    }
  }
);

function readFileAsText(f) {
  return new Promise((resolve, reject) => {
    const r = new FileReader();
    r.onload = () => resolve(String(r.result ?? ''));
    r.onerror = () => reject(new Error('Could not read file'));
    r.readAsText(f);
  });
}

/** Export JSON uses top-level `components`; import_json expects `data_structure.components`. */
function normalizeForImportJson(parsed) {
  const p =
    parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? { ...parsed } : parsed;
  const ds = p?.data_structure;
  if (
    ds &&
    typeof ds === 'object' &&
    !Array.isArray(ds) &&
    Array.isArray(p.components) &&
    !Array.isArray(ds.components)
  ) {
    p.data_structure = { ...ds, components: p.components };
    delete p.components;
  }
  return p;
}

async function submit() {
  const f = file.value;
  if (!f) return;
  const rawFile = Array.isArray(f) ? f[0] : f;
  if (!rawFile) return;
  saving.value = true;
  validationErrors.value = [];
  try {
    const text = await readFileAsText(rawFile);
    let parsed;
    try {
      parsed = JSON.parse(text);
    } catch {
      throw new Error('Invalid JSON file');
    }
    if (!parsed || typeof parsed !== 'object') {
      throw new Error('JSON root must be an object');
    }
    const payload = normalizeForImportJson(parsed);
    payload.overwrite = overwrite.value;
    const result = await importFromJson(payload);
    emit('imported', result);
    emit('update:modelValue', false);
  } catch (e) {
    const d = e?.response?.data;
    const errors = Array.isArray(d?.errors) ? d.errors : [];
    const msg = d?.message || e?.message || 'Import failed';
    validationErrors.value = errors;
    setMessage(msg, 'error', { errors });
  } finally {
    saving.value = false;
  }
}
</script>
