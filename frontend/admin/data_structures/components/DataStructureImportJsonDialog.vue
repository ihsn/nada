<template>
  <v-dialog :model-value="modelValue" max-width="560" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>Import DSD from JSON</v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-3">
          Catalogue JSON envelope (<code>data_structure</code>, optional <code>overwrite</code>,
          <code>dry_run</code>) as used by
          <code>POST /api/admin/data_structures/import_json</code>. Same shape as Export JSON.
        </p>
        <v-file-input
          v-model="file"
          label="JSON file"
          accept=".json,application/json"
          prepend-icon="mdi-code-json"
          show-size
          density="comfortable"
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
        <v-switch
          v-model="dryRun"
          color="secondary"
          inset
          label="Dry run (validate only)"
          hint="When on, nothing is persisted."
          persistent-hint
          class="mt-2"
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

defineOptions({ name: 'DataStructureImportJsonDialog' });

const setMessage = inject('setMessage', () => {});

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'imported']);

const { importFromJson } = useDataStructuresApi();

const file = ref(null);
const overwrite = ref(false);
const dryRun = ref(false);
const saving = ref(false);

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      file.value = null;
      overwrite.value = false;
      dryRun.value = false;
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
    payload.dry_run = dryRun.value;
    const result = await importFromJson(payload);
    emit('imported', result);
    emit('update:modelValue', false);
  } catch (e) {
    const d = e?.response?.data;
    let msg = d?.message || e?.message || 'Import failed';
    if (Array.isArray(d?.errors) && d.errors.length) {
      msg = `${msg} (${d.errors.length} validation issue${d.errors.length === 1 ? '' : 's'})`;
    }
    setMessage(msg, 'error');
  } finally {
    saving.value = false;
  }
}
</script>
