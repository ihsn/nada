<script setup>
/**
 * ME-style table/grid editor for template type "array" (scalar columns).
 */
import { computed, ref } from 'vue';
import { useMetadataFormStore } from '../composables/useMetadataFormStore';
import {
  emptyRowForProps,
  enumToSelectItems,
  normalizeProps,
  resolveDisplayType,
} from '../utils/enumOptions';
import MetadataFieldHelp from './MetadataFieldHelp.vue';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';

const props = defineProps({
  field: { type: Object, required: true },
  path: { type: String, required: true },
});

const store = useMetadataFormStore();
const labels = useMetadataFormLabels();
const sortField = ref('');
const sortAsc = ref(true);

const columns = computed(() =>
  normalizeProps(props.field.props).filter(
    (p) => p && p.key && !['array', 'nested_array', 'simple_array', 'section', 'section_container'].includes(p.type)
  )
);
const helpText = computed(() => props.field.help_text || props.field.help || '');
const label = computed(() => props.field.title || props.field.key || '');

const rows = computed({
  get() {
    const v = store.getValue(props.path);
    return Array.isArray(v) ? v : [];
  },
  set(v) {
    store.setValue(props.path, Array.isArray(v) ? v : []);
  },
});

const enumQuickAdds = computed(() => {
  const items = enumToSelectItems(props.field.enum);
  return items.filter((i) => i.raw && (i.raw.abbreviation || i.raw.name));
});

function cellValue(rowIndex, key) {
  const row = rows.value[rowIndex];
  if (!row || typeof row !== 'object') return '';
  const v = row[key];
  return v === undefined || v === null ? '' : v;
}

function updateCell(rowIndex, column, rawValue) {
  const next = rows.value.map((r) => ({ ...(r && typeof r === 'object' ? r : {}) }));
  while (next.length <= rowIndex) next.push({});
  let value = rawValue;
  const t = column.type;
  if (t === 'number' || t === 'integer') {
    const n = Number(rawValue);
    if (String(n) === String(rawValue) && rawValue !== '') value = n;
  } else if (t === 'boolean') {
    const s = String(rawValue).toLowerCase();
    if (s === 'true') value = true;
    else if (s === 'false') value = false;
  }
  next[rowIndex] = { ...next[rowIndex], [column.key]: value };
  rows.value = next;
}

function addRow() {
  rows.value = [...rows.value, emptyRowForProps(columns.value)];
}

function removeRow(index) {
  if (!confirm(labels.value.deleteRowConfirm)) return;
  const next = rows.value.slice();
  next.splice(index, 1);
  rows.value = next;
}

function addFromEnum(item) {
  const raw = item.raw || {};
  const row = emptyRowForProps(columns.value);
  if (Object.prototype.hasOwnProperty.call(row, 'name') && raw.name != null) {
    row.name = raw.name;
  }
  if (Object.prototype.hasOwnProperty.call(row, 'abbreviation') && raw.abbreviation != null) {
    row.abbreviation = raw.abbreviation;
  }
  rows.value = [...rows.value, row];
}

function sortBy(columnKey) {
  if (sortField.value === columnKey) {
    sortAsc.value = !sortAsc.value;
  } else {
    sortField.value = columnKey;
    sortAsc.value = true;
  }
  const asc = sortAsc.value;
  const sorted = rows.value.slice().sort((a, b) => {
    const av = a && a[columnKey] != null ? String(a[columnKey]) : '';
    const bv = b && b[columnKey] != null ? String(b[columnKey]) : '';
    return asc
      ? av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' })
      : bv.localeCompare(av, undefined, { numeric: true, sensitivity: 'base' });
  });
  rows.value = sorted;
}

function columnSelectItems(column) {
  return enumToSelectItems(column.enum);
}

function isDropdownColumn(column) {
  const dt = resolveDisplayType(column);
  return dt === 'dropdown' || dt === 'dropdown-custom';
}

function isTextareaColumn(column) {
  return resolveDisplayType(column) === 'textarea';
}

function allowCustomDropdown(column) {
  return resolveDisplayType(column) === 'dropdown-custom';
}
</script>

<template>
  <div class="mf-array-grid">
    <div class="d-flex align-start justify-space-between mb-2 flex-wrap ga-2">
      <div class="flex-grow-1" style="min-width: 160px">
        <MetadataFieldHelp :label="label" :help-text="helpText" />
      </div>
      <div class="d-flex ga-2 flex-wrap">
        <v-menu v-if="enumQuickAdds.length" location="bottom end">
          <template #activator="{ props: menuProps }">
            <v-btn
              v-bind="menuProps"
              size="small"
              variant="tonal"
              class="text-none"
              prepend-icon="mdi-form-select"
            >
              {{ labels.addFromList }}
            </v-btn>
          </template>
          <v-list density="compact" max-height="280" class="overflow-y-auto">
            <v-list-item
              v-for="(item, idx) in enumQuickAdds"
              :key="idx"
              :title="item.title"
              @click="addFromEnum(item)"
            />
          </v-list>
        </v-menu>
      </div>
    </div>

    <div class="mf-array-grid-table-wrap">
      <table class="mf-array-grid-table">
        <thead>
          <tr>
            <th class="mf-array-grid-col-index">#</th>
            <th
              v-for="column in columns"
              :key="column.key"
              class="mf-array-grid-th"
              scope="col"
            >
              <button type="button" class="mf-array-grid-sort" @click="sortBy(column.key)">
                <span>{{ column.title || column.key }}</span>
                <v-icon v-if="sortField === column.key" size="14" class="ms-1">
                  {{ sortAsc ? 'mdi-menu-up' : 'mdi-menu-down' }}
                </v-icon>
              </button>
            </th>
            <th class="mf-array-grid-col-actions" scope="col"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!rows.length">
            <td :colspan="columns.length + 2" class="mf-array-grid-empty text-medium-emphasis">
              {{ labels.noRows }}
            </td>
          </tr>
          <tr v-for="(row, index) in rows" :key="index">
            <td class="mf-array-grid-col-index text-medium-emphasis">{{ index + 1 }}</td>
            <td v-for="column in columns" :key="column.key" class="mf-array-grid-td">
              <v-textarea
                v-if="isTextareaColumn(column)"
                :model-value="cellValue(index, column.key)"
                density="compact"
                variant="outlined"
                hide-details
                auto-grow
                rows="1"
                class="mf-array-grid-input"
                @update:model-value="updateCell(index, column, $event)"
              />
              <v-select
                v-else-if="isDropdownColumn(column) && !allowCustomDropdown(column)"
                :model-value="cellValue(index, column.key)"
                :items="columnSelectItems(column)"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                hide-details
                clearable
                class="mf-array-grid-input"
                @update:model-value="updateCell(index, column, $event)"
              />
              <v-combobox
                v-else-if="isDropdownColumn(column) && allowCustomDropdown(column)"
                :model-value="cellValue(index, column.key)"
                :items="columnSelectItems(column)"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                hide-details
                clearable
                class="mf-array-grid-input"
                @update:model-value="updateCell(index, column, $event)"
              />
              <v-text-field
                v-else
                :model-value="cellValue(index, column.key)"
                density="compact"
                variant="outlined"
                hide-details
                class="mf-array-grid-input"
                :type="column.type === 'integer' || column.type === 'number' ? 'number' : 'text'"
                @update:model-value="updateCell(index, column, $event)"
              />
            </td>
            <td class="mf-array-grid-col-actions">
              <v-btn
                icon="mdi-trash-can-outline"
                size="x-small"
                variant="text"
                color="error"
                :title="labels.deleteRow"
                @click="removeRow(index)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-center mt-2">
      <v-btn size="small" variant="text" class="text-none" prepend-icon="mdi-plus" @click="addRow">
        {{ labels.addRow }}
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
.mf-array-grid {
  margin-bottom: 16px;
}
.mf-array-grid-table-wrap {
  width: 100%;
  overflow-x: auto;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 8px;
}
.mf-array-grid-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 480px;
}
.mf-array-grid-table thead {
  background: rgba(var(--v-theme-on-surface), 0.04);
}
.mf-array-grid-table th,
.mf-array-grid-table td {
  padding: 6px 8px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  vertical-align: top;
}
.mf-array-grid-table tbody tr:nth-child(even) {
  background: rgba(var(--v-theme-on-surface), 0.015);
}
.mf-array-grid-th {
  font-size: 0.75rem;
  font-weight: 600;
  text-align: left;
  white-space: nowrap;
}
.mf-array-grid-sort {
  display: inline-flex;
  align-items: center;
  border: 0;
  background: transparent;
  padding: 0;
  cursor: pointer;
  color: inherit;
  font: inherit;
  font-weight: 600;
}
.mf-array-grid-sort:hover {
  color: rgb(var(--v-theme-primary));
}
.mf-array-grid-col-index {
  width: 40px;
  text-align: center;
  font-size: 0.75rem;
}
.mf-array-grid-col-actions {
  width: 44px;
  text-align: center;
}
.mf-array-grid-empty {
  padding: 16px 12px !important;
  text-align: center;
  font-size: 0.8125rem;
}
.mf-array-grid-input :deep(.v-field) {
  --v-field-padding-start: 8px;
  --v-field-padding-end: 8px;
}
.mf-array-grid-input :deep(.v-field__input) {
  min-height: 36px;
  padding-top: 4px;
  padding-bottom: 4px;
  font-size: 0.8125rem;
}
</style>
