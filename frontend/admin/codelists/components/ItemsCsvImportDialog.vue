<template>
  <v-dialog :model-value="modelValue" max-width="760" persistent @update:model-value="close">
    <v-card>
      <v-card-title class="d-flex align-center">
        <span>Import items from CSV</span>
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" size="small" :disabled="saving" @click="close" />
      </v-card-title>
      <v-card-text>
        <div class="text-body-2 text-medium-emphasis mb-3">
          The first row must be a header with a <code>code</code> column, plus optional <code>title</code> and
          <code>sort_order</code> columns (the same layout as <em>Export CSV</em>). Items are matched by code:
          existing codes are updated, new codes are added. Columns left out of the file keep their current values.
        </div>
        <v-file-input
          v-model="file"
          accept=".csv,text/csv,.tsv,.txt"
          label="CSV file"
          variant="outlined"
          density="comfortable"
          prepend-icon=""
          prepend-inner-icon="mdi-file-delimited-outline"
          show-size
          :disabled="saving"
          @update:model-value="onFile"
        />

        <template v-if="parsed.rows.length">
          <div class="text-caption text-medium-emphasis mb-1">
            {{ parsed.rows.length }} rows · columns: {{ parsed.columns.join(', ') }}
          </div>
          <v-table density="compact" class="preview-table mb-2">
            <thead>
              <tr>
                <th>Code</th>
                <th v-if="parsed.columns.includes('title')">Title</th>
                <th v-if="parsed.columns.includes('sort_order')">Sort</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in parsed.rows.slice(0, 8)" :key="r.code">
                <td><code>{{ r.code }}</code></td>
                <td v-if="parsed.columns.includes('title')">{{ r.title }}</td>
                <td v-if="parsed.columns.includes('sort_order')">{{ r.sort_order }}</td>
              </tr>
              <tr v-if="parsed.rows.length > 8">
                <td :colspan="parsed.columns.length" class="text-medium-emphasis">… {{ parsed.rows.length - 8 }} more</td>
              </tr>
            </tbody>
          </v-table>
          <v-checkbox
            v-model="deleteMissing"
            density="compact"
            hide-details
            color="error"
            label="Delete existing items that are not in the file"
            @update:model-value="preview = null"
          />
        </template>

        <v-alert v-if="allErrors.length" type="error" variant="tonal" density="compact" class="mt-3">
          <div v-for="(e, i) in allErrors.slice(0, 15)" :key="i">{{ e }}</div>
          <div v-if="allErrors.length > 15">…and {{ allErrors.length - 15 }} more</div>
        </v-alert>
        <v-alert v-else-if="preview" :type="preview.deleted ? 'warning' : 'info'" variant="tonal" density="compact" class="mt-3">
          {{ preview.created }} to add, {{ preview.updated }} to update, {{ preview.unchanged }} unchanged<template v-if="deleteMissing">, <strong>{{ preview.deleted }} to delete</strong> (with their translations and group links)</template>.
        </v-alert>
      </v-card-text>
      <v-card-actions class="px-4 pb-4">
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="close">Cancel</v-btn>
        <v-btn
          v-if="!preview"
          color="primary"
          variant="outlined"
          :loading="saving"
          :disabled="!canSubmit"
          @click="run(true)"
        >Preview changes</v-btn>
        <v-btn
          v-else
          :color="preview.deleted ? 'error' : 'primary'"
          :loading="saving"
          :disabled="!canSubmit || (!preview.created && !preview.updated && !preview.deleted)"
          @click="run(false)"
        >Import</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useCodelistsApi } from '../composables/useCodelistsApi';
import { parseCsv } from '../composables/csv';

defineOptions({ name: 'ItemsCsvImportDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  codelistId: { type: [Number, String], required: true },
});
const emit = defineEmits(['update:modelValue', 'saved']);

const { batchUpdateItems } = useCodelistsApi();

const file = ref(null);
const parsed = ref({ rows: [], columns: [], errors: [] });
const deleteMissing = ref(false);
const preview = ref(null);
const serverErrors = ref([]);
const saving = ref(false);

const HEADER_ALIASES = {
  code: 'code', value: 'code', id_code: 'code',
  title: 'title', label: 'title', name: 'title',
  sort_order: 'sort_order', sort: 'sort_order', order: 'sort_order',
};

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      file.value = null;
      parsed.value = { rows: [], columns: [], errors: [] };
      deleteMissing.value = false;
      preview.value = null;
      serverErrors.value = [];
    }
  }
);

const allErrors = computed(() => [...parsed.value.errors, ...serverErrors.value]);
const canSubmit = computed(() => !saving.value && parsed.value.rows.length > 0 && parsed.value.errors.length === 0);

function close() {
  if (saving.value) return;
  emit('update:modelValue', false);
}

async function onFile(v) {
  preview.value = null;
  serverErrors.value = [];
  const f = Array.isArray(v) ? v[0] : v;
  if (!f) {
    parsed.value = { rows: [], columns: [], errors: [] };
    return;
  }
  const text = await f.text();
  parsed.value = parseItemsCsv(text);
}

function parseItemsCsv(text) {
  const table = parseCsv(text);
  const errors = [];
  if (!table.length) return { rows: [], columns: [], errors: ['The file is empty.'] };

  const header = table[0].map((h) => HEADER_ALIASES[String(h).trim().toLowerCase().replace(/\s+/g, '_')] || null);
  const idx = { code: header.indexOf('code'), title: header.indexOf('title'), sort_order: header.indexOf('sort_order') };
  if (idx.code < 0) return { rows: [], columns: [], errors: ['Header row must include a "code" column.'] };
  const columns = ['code', 'title', 'sort_order'].filter((c) => idx[c] >= 0);

  const rows = [];
  const seen = new Map();
  table.slice(1).forEach((cols, i) => {
    const line = i + 2;
    const code = String(cols[idx.code] ?? '').trim();
    if (!code) { errors.push(`Line ${line}: code is empty.`); return; }
    if (seen.has(code)) { errors.push(`Line ${line}: duplicate code "${code}" (also line ${seen.get(code)}).`); return; }
    seen.set(code, line);
    const row = { code };
    if (idx.title >= 0) row.title = String(cols[idx.title] ?? '').trim();
    if (idx.sort_order >= 0) {
      const s = String(cols[idx.sort_order] ?? '').trim();
      if (s !== '') {
        if (!/^-?\d+$/.test(s)) { errors.push(`Line ${line}: sort_order "${s}" is not a whole number.`); return; }
        row.sort_order = parseInt(s, 10);
      }
    }
    rows.push(row);
  });
  if (!rows.length && !errors.length) errors.push('No data rows found below the header.');
  return { rows, columns, errors };
}

async function run(dryRun) {
  if (!canSubmit.value) return;
  saving.value = true;
  serverErrors.value = [];
  try {
    const r = await batchUpdateItems(props.codelistId, parsed.value.rows, {
      delete_missing: deleteMissing.value,
      dry_run: dryRun,
    });
    if (dryRun) {
      preview.value = r;
    } else {
      emit('saved', r);
      emit('update:modelValue', false);
    }
  } catch (e) {
    const d = e?.response?.data;
    serverErrors.value = Array.isArray(d?.errors) && d.errors.length ? d.errors : [d?.message || e?.message || 'Import failed'];
    preview.value = null;
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.preview-table {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 6px;
}
</style>
