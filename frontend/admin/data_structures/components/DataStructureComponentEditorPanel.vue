<template>
  <div>
    <v-form ref="formRef">
      <div class="text-caption text-medium-emphasis mb-1">Name</div>
      <v-text-field
        v-model="form.name"
        hint="Unique within this data structure"
        persistent-hint
        :disabled="readOnly"
        :rules="[v => !!v?.trim() || 'Required']"
        density="compact"
        variant="outlined"
      />
      <div style="margin-top: 16px" class="text-caption text-medium-emphasis mb-1">Label</div>
      <v-text-field v-model="form.label" :disabled="readOnly" density="compact" variant="outlined" hide-details />
      <div style="margin-top: 16px" class="text-caption text-medium-emphasis mb-1">Column type</div>
      <v-select
        v-model="form.column_type"
        :items="columnTypeItems"
        item-title="title"
        item-value="value"
        :rules="[v => !!v || 'Required']"
        :disabled="readOnly"
        density="compact"
        variant="outlined"
      />
      <div style="margin-top: 16px" class="text-caption text-medium-emphasis mb-1">Data type</div>
      <v-select
        v-model="form.data_type"
        :items="dataTypeItems"
        item-title="title"
        item-value="value"
        hint="Optional"
        persistent-hint
        clearable
        :disabled="readOnly"
        density="compact"
        variant="outlined"
      />
      <div v-if="form.column_type === 'time_period'" style="margin-top: 16px" class="text-caption text-medium-emphasis mb-1">
        Time period format
      </div>
      <v-text-field
        v-if="form.column_type === 'time_period'"
        v-model="form.time_period_format"
        hint="ISO-8601-style or SDMX pattern for this period column"
        persistent-hint
        :disabled="readOnly"
        density="compact"
        variant="outlined"
      />

      <div style="margin-top: 16px">
        <div class="text-caption text-medium-emphasis mb-1">Codelist</div>
        <v-autocomplete
          v-model="form.codelist_id"
          :items="codelistPickerItems"
          item-title="picker_title"
          item-value="id"
          hint="Search by name, idno, or catalogue id"
          persistent-hint
          hide-details
          clearable
          no-filter
          placeholder="Type to search…"
          prepend-inner-icon="mdi-format-list-bulleted-type"
          :loading="codelistPickerLoading"
          :disabled="readOnly"
          density="compact"
          variant="outlined"
          @update:search="scheduleCodelistPickerSearch"
          @update:menu="onCodelistPickerMenu"
          @update:model-value="initEntryList"
        />

        <p style="margin-top: 16px" class="text-caption text-medium-emphasis mb-1">Codes (flat value / label from the item row)</p>
        <div class="text-caption text-medium-emphasis mb-1">Filter codes</div>
        <v-text-field
          v-model="entrySearchDraft"
          placeholder="Value or label…"
          prepend-inner-icon="mdi-magnify"
          density="compact"
          variant="outlined"
          hide-details
          class="mb-2"
          :disabled="readOnly || form.codelist_id == null"
          @update:model-value="scheduleEntrySearch"
        />
        <v-sheet border rounded class="overflow-hidden">
          <v-table density="compact" fixed-header class="elevation-0" :height="codesTableHeight">
            <thead>
              <tr>
                <th class="text-left" style="width: 38%">Value</th>
                <th class="text-left">Label</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="form.codelist_id == null">
                <td colspan="2" class="text-center text-medium-emphasis py-6">Select a codelist above to load codes.</td>
              </tr>
              <tr v-else-if="entryLoading && !entryRows.length">
                <td colspan="2" class="text-center text-medium-emphasis py-6">Loading…</td>
              </tr>
              <tr v-else-if="!entryRows.length">
                <td colspan="2" class="text-center text-medium-emphasis py-6">No codes match this filter.</td>
              </tr>
              <template v-else>
                <tr v-for="(row, idx) in entryRows" :key="`${row.value}-${idx}`">
                  <td><code class="text-body-2">{{ row.value }}</code></td>
                  <td>{{ row.label }}</td>
                </tr>
              </template>
            </tbody>
          </v-table>
        </v-sheet>
        <div v-if="form.codelist_id != null" class="d-flex flex-wrap align-center justify-space-between gap-2 mt-2">
          <span class="text-caption text-medium-emphasis">Total codes: {{ entryTotal }}</span>
          <div class="d-flex flex-wrap align-center gap-2">
            <div class="text-caption text-medium-emphasis">Per page</div>
            <v-select
              v-model="entryPerPage"
              :items="entryPerPageChoices"
              density="compact"
              variant="outlined"
              hide-details
              style="max-width: 120px"
              :disabled="readOnly"
              @update:model-value="onEntryPerPageChange"
            />
            <v-pagination
              v-if="entryPageCount > 1"
              v-model="entryPage"
              :length="entryPageCount"
              density="comfortable"
              rounded="circle"
              @update:model-value="loadEntryPage"
            />
          </div>
        </div>
      </div>

      <div style="margin-top: 16px" class="text-caption text-medium-emphasis mb-1">Sort order</div>
      <v-text-field
        v-model.number="form.sort_order"
        type="number"
        :disabled="readOnly"
        density="compact"
        variant="outlined"
        hide-details
      />
      <div style="margin-top: 16px" class="text-caption text-medium-emphasis mb-1">Description</div>
      <v-textarea
        v-model="form.description"
        rows="2"
        auto-grow
        :disabled="readOnly"
        density="compact"
        variant="outlined"
      />
    </v-form>
    <div class="d-flex flex-wrap justify-end gap-2 mt-4">
      <v-btn variant="text" @click="$emit('cancel')">Cancel</v-btn>
      <v-btn color="primary" :loading="saving" :disabled="readOnly" @click="submit">{{ isEdit ? 'Save' : 'Create' }}</v-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, inject, onMounted, onUnmounted } from 'vue';
import { COLUMN_TYPES, DATA_TYPES, useDataStructuresApi } from '../composables/useDataStructuresApi';

defineOptions({ name: 'DataStructureComponentEditorPanel' });

const setMessage = inject('setMessage', () => {});

const props = defineProps({
  structureId: { type: [Number, String], required: true },
  component: { type: Object, default: null },
  readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['saved', 'cancel']);

const { fetchCodelistMetaForPicker, fetchCodelistItemsFlatPage, fetchCodelistsPage } = useDataStructuresApi();

const formRef = ref(null);
const form = ref({
  name: '',
  label: '',
  column_type: 'dimension',
  data_type: null,
  time_period_format: '',
  codelist_id: null,
  sort_order: 0,
  description: '',
});
const saving = ref(false);

const codelistPickerItems = ref([]);
const codelistPickerLoading = ref(false);
let codelistPickerSearchTimer = null;

const entryLoading = ref(false);
const entryRows = ref([]);
const entryTotal = ref(0);
const entryPage = ref(1);
const entryPerPage = ref(50);
const entryPerPageChoices = [25, 50, 100];
const entrySearchDraft = ref('');
const entrySearchApplied = ref('');
let entrySearchTimer = null;

/** Grows with viewport so the codes block is not a short fixed strip on tall screens */
const codesTableHeight = ref(280);
function syncCodesTableHeight() {
  if (typeof window === 'undefined') return;
  codesTableHeight.value = Math.min(520, Math.max(200, Math.round(window.innerHeight * 0.32)));
}

const isEdit = computed(() => !!props.component?.id);
const readOnly = computed(() => !!props.readOnly);

const columnTypeItems = COLUMN_TYPES.map((v) => ({ title: v, value: v }));
const dataTypeItems = DATA_TYPES.map((v) => ({ title: v, value: v }));

const entryPageCount = computed(() =>
  Math.max(1, Math.ceil(entryTotal.value / Math.max(1, entryPerPage.value)))
);

function formatCodelistSummary(row) {
  if (!row) return '';
  const bits = [
    row.name,
    [row.agency, row.version].filter(Boolean).join(' '),
    row.idno ? `— ${row.idno}` : '',
    row.id != null ? `[#${row.id}]` : '',
  ].filter(Boolean);
  return bits.join(' · ');
}

function mapCodelistPickerRow(row) {
  if (!row) return null;
  const id = row.id != null && row.id !== '' ? Number(row.id) : null;
  if (!Number.isFinite(id)) return null;
  const slim = {
    id,
    name: row.name,
    agency: row.agency,
    version: row.version,
    idno: row.idno,
  };
  return {
    ...slim,
    picker_title: formatCodelistSummary(slim),
  };
}

/** Keep current selection visible when it is not in the latest search page. */
function mergeSelectedIntoPickerItems(rows) {
  const sid = form.value.codelist_id;
  if (sid == null) return rows;
  if (rows.some((r) => Number(r.id) === Number(sid))) return rows;
  const cur = codelistPickerItems.value.find((r) => Number(r.id) === Number(sid));
  return cur ? [cur, ...rows] : rows;
}

async function ensureSelectedCodelistInPickerItems() {
  const id = form.value.codelist_id;
  if (id == null) {
    codelistPickerItems.value = [];
    return;
  }
  try {
    const row = await fetchCodelistMetaForPicker(id);
    const mapped = mapCodelistPickerRow(row);
    if (mapped) {
      const rest = codelistPickerItems.value.filter((r) => Number(r.id) !== Number(id));
      codelistPickerItems.value = [mapped, ...rest];
    }
  } catch {
    codelistPickerItems.value = [
      {
        id: Number(id),
        picker_title: `Codelist #${id}`,
      },
    ];
  }
}

async function loadCodelistPickerPage(search = '') {
  codelistPickerLoading.value = true;
  try {
    const { codelists } = await fetchCodelistsPage({
      page: 1,
      perPage: 50,
      search: typeof search === 'string' ? search : '',
      withCounts: false,
    });
    const rows = (codelists || []).map(mapCodelistPickerRow).filter(Boolean);
    codelistPickerItems.value = mergeSelectedIntoPickerItems(rows);
  } catch (e) {
    setMessage(e?.message || 'Could not load codelists.', 'error');
    await ensureSelectedCodelistInPickerItems();
  } finally {
    codelistPickerLoading.value = false;
  }
}

function scheduleCodelistPickerSearch(q) {
  if (codelistPickerSearchTimer) clearTimeout(codelistPickerSearchTimer);
  codelistPickerSearchTimer = setTimeout(() => {
    codelistPickerSearchTimer = null;
    loadCodelistPickerPage(typeof q === 'string' ? q : '');
  }, 300);
}

function onCodelistPickerMenu(open) {
  if (open && !codelistPickerLoading.value) {
    loadCodelistPickerPage('');
  }
}

function initEntryList() {
  entrySearchDraft.value = '';
  entrySearchApplied.value = '';
  entryPage.value = 1;
  if (form.value.codelist_id == null) {
    entryRows.value = [];
    entryTotal.value = 0;
    return;
  }
  loadEntryPage();
}

function scheduleEntrySearch() {
  if (entrySearchTimer) clearTimeout(entrySearchTimer);
  entrySearchTimer = setTimeout(() => {
    entrySearchTimer = null;
    entrySearchApplied.value = entrySearchDraft.value.trim();
    entryPage.value = 1;
    loadEntryPage();
  }, 350);
}

async function loadEntryPage() {
  if (form.value.codelist_id == null) {
    entryRows.value = [];
    entryTotal.value = 0;
    return;
  }
  entryLoading.value = true;
  try {
    const { items, total } = await fetchCodelistItemsFlatPage(form.value.codelist_id, {
      page: entryPage.value,
      perPage: entryPerPage.value,
      search: entrySearchApplied.value,
    });
    entryRows.value = items;
    entryTotal.value = total;
  } catch (e) {
    entryRows.value = [];
    entryTotal.value = 0;
    setMessage(e?.message || 'Could not load codelist codes.', 'error');
  } finally {
    entryLoading.value = false;
  }
}

function onEntryPerPageChange() {
  entryPage.value = 1;
  loadEntryPage();
}

async function resetFromProps() {
  const c = props.component;
  if (c?.id) {
    form.value = {
      name: c.name || '',
      label: c.label || '',
      column_type: c.column_type || 'dimension',
      data_type: c.data_type || null,
      time_period_format: c.time_period_format || '',
      codelist_id: c.codelist_id != null && c.codelist_id !== '' ? Number(c.codelist_id) : null,
      sort_order: c.sort_order != null ? Number(c.sort_order) : 0,
      description: c.description || '',
    };
    await ensureSelectedCodelistInPickerItems();
  } else {
    form.value = {
      name: '',
      label: '',
      column_type: 'dimension',
      data_type: null,
      time_period_format: '',
      codelist_id: null,
      sort_order: 0,
      description: '',
    };
    codelistPickerItems.value = [];
  }
  initEntryList();
}

watch(
  () => [props.component, props.structureId],
  () => {
    resetFromProps();
  },
  { deep: true, immediate: true }
);

onMounted(() => {
  syncCodesTableHeight();
  window.addEventListener('resize', syncCodesTableHeight);
});

onUnmounted(() => {
  window.removeEventListener('resize', syncCodesTableHeight);
  if (codelistPickerSearchTimer) clearTimeout(codelistPickerSearchTimer);
});

async function submit() {
  if (readOnly.value) return;
  const valid = await formRef.value?.validate();
  if (!valid?.valid) return;

  saving.value = true;
  try {
    const basePayload = {
      label: form.value.label?.trim() || null,
      column_type: form.value.column_type,
      data_type: form.value.data_type || null,
      time_period_format:
        form.value.column_type === 'time_period'
          ? form.value.time_period_format?.trim() || null
          : null,
      codelist_id:
        form.value.codelist_id != null && form.value.codelist_id !== ''
          ? Number(form.value.codelist_id)
          : null,
      sort_order: form.value.sort_order != null ? Number(form.value.sort_order) : 0,
      description: form.value.description?.trim() || null,
    };
    if (isEdit.value) {
      emit('saved', {
        isEdit: true,
        componentId: props.component.id,
        payload: basePayload,
      });
    } else {
      emit('saved', {
        isEdit: false,
        structureId: props.structureId,
        payload: {
          name: form.value.name.trim(),
          ...basePayload,
        },
      });
    }
  } finally {
    saving.value = false;
  }
}

defineExpose({ submit, resetFromProps });
</script>
