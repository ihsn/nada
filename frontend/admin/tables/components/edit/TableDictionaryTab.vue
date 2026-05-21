<template>
  <v-card flat rounded="0">
    <v-card-title class="d-flex flex-wrap align-center gap-2">
      <span>Data dictionary fields</span>
      <v-spacer />
      <v-btn color="warning" size="small" prepend-icon="mdi-sync" :loading="syncing" @click="syncDictionary">
        Sync fields
      </v-btn>
      <v-btn color="primary" size="small" prepend-icon="mdi-database-refresh" :loading="populating" @click="populate">
        Populate from data
      </v-btn>
      <v-btn color="info" size="small" prepend-icon="mdi-database-sync" @click="openConvertDialog">Convert types</v-btn>
      <v-btn color="success" size="small" prepend-icon="mdi-plus" @click="showAddDialog = true">Add field</v-btn>
    </v-card-title>
    <v-card-text class="pa-0 dictionary-layout">
      <v-row no-gutters class="dictionary-row">
        <v-col cols="12" md="5" class="dictionary-list-col">
          <div class="dictionary-list-toolbar pa-3">
            <v-text-field
              v-model="fieldSearch"
              placeholder="Search fields…"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              class="flex-grow-1"
            />
            <span class="text-caption text-medium-emphasis ml-2">{{ filteredFields.length }}/{{ fields.length }}</span>
            <v-menu>
              <template #activator="{ props: menuProps }">
                <v-btn size="small" variant="outlined" v-bind="menuProps" append-icon="mdi-menu-down">
                  {{ sortLabel }}
                </v-btn>
              </template>
              <v-list density="compact">
                <v-list-item
                  v-for="opt in FIELD_SORT_OPTIONS"
                  :key="opt.value"
                  :active="fieldSortBy === opt.value"
                  :title="opt.title"
                  @click="fieldSortBy = opt.value"
                />
              </v-list>
            </v-menu>
          </div>
          <div class="dictionary-list-scroll">
            <div
              v-for="field in sortedFields"
              :key="field.name"
              class="field-list-item"
              :class="{ active: selectedField?.name === field.name }"
              @click="selectField(field)"
            >
              <div class="d-flex align-center">
                <v-icon size="small" class="mr-2">mdi-table-column</v-icon>
                <div class="flex-grow-1">
                  <div class="font-weight-medium">{{ field.name }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ field.label || 'N/A' }} · {{ field.data_type
                    }}{{ field.column_type ? ' · ' + field.column_type : '' }}
                  </div>
                </div>
                <v-btn icon size="small" color="error" variant="text" @click.stop="removeField(field)">
                  <v-icon size="small">mdi-delete</v-icon>
                </v-btn>
              </div>
            </div>
            <div v-if="!fields.length" class="text-center py-8 text-medium-emphasis">
              <v-icon size="48" color="grey" class="mb-2">mdi-table-off</v-icon>
              <div>No fields defined</div>
            </div>
            <div v-else-if="!filteredFields.length" class="text-center py-8 text-medium-emphasis">
              No fields match your search
            </div>
          </div>
        </v-col>
        <v-col cols="12" md="7" class="dictionary-editor-col">
          <div v-if="selectedField" class="dictionary-editor pa-6">
            <h3 class="text-h6 mb-4">{{ selectedField.name }}</h3>
            <v-text-field
              v-model="selectedField.label"
              label="Label"
              variant="outlined"
              density="compact"
              class="mb-3"
              @blur="saveField"
            />
            <v-select
              v-model="selectedField.data_type"
              :items="DATA_TYPES"
              item-title="title"
              item-value="value"
              label="Data type"
              variant="outlined"
              density="compact"
              class="mb-3"
              @update:model-value="saveField"
            />
            <v-select
              v-model="selectedField.column_type"
              :items="COLUMN_TYPES"
              item-title="title"
              item-value="value"
              label="Column type"
              variant="outlined"
              density="compact"
              class="mb-3"
              @update:model-value="saveField"
            />
            <v-textarea
              v-model="selectedField.description"
              label="Description"
              variant="outlined"
              rows="3"
              class="mb-3"
              @blur="saveField"
            />
            <v-text-field
              v-model="selectedField.unit_of_measurement"
              label="Unit of measurement"
              variant="outlined"
              density="compact"
              class="mb-3"
              @blur="saveField"
            />
            <v-text-field
              v-model="selectedField.format"
              label="Format"
              variant="outlined"
              density="compact"
              class="mb-3"
              @blur="saveField"
            />
            <v-text-field
              v-model="selectedField.time_period_format"
              label="Time period format"
              variant="outlined"
              density="compact"
              class="mb-3"
              :disabled="selectedField.column_type !== 'time_period'"
              @blur="saveField"
            />
            <v-divider class="my-4" />
            <h4 class="text-subtitle-1 mb-3">Code list</h4>
            <v-table v-if="selectedField.code_list?.length" density="compact" class="mb-3">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Label</th>
                  <th>Description</th>
                  <th width="60" />
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in selectedField.code_list" :key="idx">
                  <td><v-text-field v-model="item.code" density="compact" hide-details variant="plain" @blur="saveField" /></td>
                  <td><v-text-field v-model="item.label" density="compact" hide-details variant="plain" @blur="saveField" /></td>
                  <td><v-text-field v-model="item.description" density="compact" hide-details variant="plain" @blur="saveField" /></td>
                  <td>
                    <v-btn icon size="x-small" color="error" @click="removeCodeItem(idx)"><v-icon>mdi-delete</v-icon></v-btn>
                  </td>
                </tr>
              </tbody>
            </v-table>
            <v-btn size="small" color="primary" prepend-icon="mdi-plus" class="mb-4" @click="addCodeItem">Add code</v-btn>
            <v-expansion-panels variant="accordion" class="mb-4">
              <v-expansion-panel title="Code list reference">
                <v-expansion-panel-text>
                  <v-text-field v-model="selectedField.code_list_reference.id" label="ID" variant="outlined" density="compact" class="mb-2" @blur="saveField" />
                  <v-text-field v-model="selectedField.code_list_reference.name" label="Name" variant="outlined" density="compact" class="mb-2" @blur="saveField" />
                  <v-text-field v-model="selectedField.code_list_reference.version" label="Version" variant="outlined" density="compact" class="mb-2" @blur="saveField" />
                  <v-text-field v-model="selectedField.code_list_reference.uri" label="URI" variant="outlined" density="compact" class="mb-2" @blur="saveField" />
                  <v-textarea v-model="selectedField.code_list_reference.note" label="Note" variant="outlined" rows="2" density="compact" @blur="saveField" />
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
            <div class="d-flex justify-space-between">
              <v-btn color="error" size="small" prepend-icon="mdi-delete" @click="removeField(selectedField)">
                Delete field
              </v-btn>
              <v-btn color="primary" size="small" prepend-icon="mdi-content-save" :loading="savingField" @click="saveField">
                Save changes
              </v-btn>
            </div>
          </div>
          <div v-else class="dictionary-editor-empty text-medium-emphasis">
            <v-icon size="64" color="grey" class="mb-4">mdi-cursor-pointer</v-icon>
            <div>Select a field from the list to edit</div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>

    <v-dialog v-model="showAddDialog" max-width="560">
      <v-card>
        <v-card-title>Add field</v-card-title>
        <v-card-text>
          <v-text-field v-model="newField.name" label="Field name *" variant="outlined" density="compact" class="mb-3" />
          <v-text-field v-model="newField.label" label="Label" variant="outlined" density="compact" class="mb-3" />
          <v-select v-model="newField.data_type" :items="DATA_TYPES" item-title="title" item-value="value" label="Data type *" variant="outlined" density="compact" class="mb-3" />
          <v-select v-model="newField.column_type" :items="COLUMN_TYPES" item-title="title" item-value="value" label="Column type" variant="outlined" density="compact" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showAddDialog = false">Cancel</v-btn>
          <v-btn color="primary" :disabled="!newField.name || !newField.data_type" @click="addField">Add</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showConvertDialog" max-width="800" persistent>
      <v-card>
        <v-card-title class="d-flex align-center">
          Convert field data types
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" :disabled="converting" @click="showConvertDialog = false" />
        </v-card-title>
        <v-card-text>
          <v-alert type="info" variant="tonal" density="compact" class="mb-4">
            Select fields to convert, or leave empty to convert all from metadata. Date/datetime fields are excluded.
          </v-alert>
          <v-radio-group v-model="convertOnError" label="Error handling" inline density="compact">
            <v-radio label="Ignore" value="ignore" />
            <v-radio label="Replace with null" value="replace" />
            <v-radio label="Stop on error" value="stop" />
          </v-radio-group>
          <v-autocomplete
            v-model="fieldToAdd"
            :items="availableForConversion"
            item-title="name"
            item-value="name"
            label="Add field to convert"
            variant="outlined"
            density="compact"
            clearable
            return-object
            class="mt-4"
            @update:model-value="addToConversion"
          />
          <div v-if="selectedForConversion.length" class="mt-4 conversion-list">
            <div v-for="f in selectedForConversion" :key="f.name" class="d-flex align-center gap-2 mb-2 pa-2 border rounded">
              <div class="flex-grow-1">
                <div class="font-weight-medium">{{ f.name }}</div>
                <div class="text-caption">Current: {{ f.current_type }}</div>
              </div>
              <v-select
                v-model="f.target_type"
                :items="DATA_TYPES"
                item-title="title"
                item-value="value"
                label="Target"
                density="compact"
                variant="outlined"
                hide-details
                style="max-width: 160px"
              />
              <v-btn icon size="small" color="error" @click="removeFromConversion(f.name)"><v-icon>mdi-close</v-icon></v-btn>
            </div>
          </div>
          <p v-else class="text-caption text-medium-emphasis mt-4">No fields selected — all eligible fields will be converted from metadata.</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="converting" @click="showConvertDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="converting" prepend-icon="mdi-database-sync" @click="runConvert">
            Convert {{ selectedForConversion.length ? 'selected' : 'all' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { DATA_TYPES, COLUMN_TYPES, FIELD_SORT_OPTIONS } from '../../constants';
import { useTablesApi } from '../../composables/useTablesApi';
import {
  normalizeFieldFromApi,
  cloneFieldForEdit,
  buildFieldUpsertPayload,
  sortFields,
} from '../../utils/fieldUtils';

const props = defineProps({
  dbId: { type: String, required: true },
  tableId: { type: String, required: true },
});

const emit = defineEmits(['toast']);

const api = useTablesApi();
const fields = ref([]);
const selectedField = ref(null);
const fieldSearch = ref('');
const fieldSortBy = ref('order');
const savingField = ref(false);
const syncing = ref(false);
const populating = ref(false);
const converting = ref(false);
const showAddDialog = ref(false);
const showConvertDialog = ref(false);
const convertOnError = ref('ignore');
const selectedForConversion = ref([]);
const fieldToAdd = ref(null);
const fieldsForConversion = ref([]);

const newField = reactive({ name: '', label: '', data_type: 'string', column_type: '' });

const sortLabel = computed(
  () => FIELD_SORT_OPTIONS.find((o) => o.value === fieldSortBy.value)?.title || 'Order'
);

const filteredFields = computed(() => {
  if (!fieldSearch.value) return fields.value;
  const s = fieldSearch.value.toLowerCase();
  return fields.value.filter(
    (f) =>
      f.name.toLowerCase().includes(s) ||
      (f.label && f.label.toLowerCase().includes(s)) ||
      (f.data_type && f.data_type.toLowerCase().includes(s)) ||
      (f.column_type && f.column_type.toLowerCase().includes(s))
  );
});

const sortedFields = computed(() => sortFields(filteredFields.value, fieldSortBy.value));

const availableForConversion = computed(() => {
  const selected = selectedForConversion.value.map((f) => f.name);
  return fieldsForConversion.value.filter((f) => !selected.includes(f.name));
});

async function loadSchema() {
  try {
    const raw = await api.fetchFields(props.dbId, props.tableId);
    fields.value = raw.map(normalizeFieldFromApi);
    if (fields.value.length && !selectedField.value) {
      selectField(fields.value[0]);
    }
  } catch (e) {
    emit('toast', e.message, 'error');
    fields.value = [];
  }
}

function selectField(field) {
  if (selectedField.value?.name) {
    const idx = fields.value.findIndex((f) => f.name === selectedField.value.name);
    if (idx >= 0) {
      fields.value[idx] = { ...fields.value[idx], ...selectedField.value };
    }
  }
  selectedField.value = cloneFieldForEdit(field);
}

async function saveField() {
  if (!selectedField.value?.name) return;
  savingField.value = true;
  try {
    const existing = fields.value.find((f) => f.name === selectedField.value.name);
    const payload = buildFieldUpsertPayload(selectedField.value, existing?.field_order ?? null);
    const data = await api.upsertField(props.dbId, props.tableId, payload);
    emit('toast', `Field ${data.action === 'created' ? 'created' : 'updated'} successfully`, 'success');
    try {
      const updated = await api.fetchField(props.dbId, props.tableId, selectedField.value.name);
      const normalized = normalizeFieldFromApi(updated);
      const idx = fields.value.findIndex((f) => f.name === normalized.name);
      if (idx >= 0) fields.value[idx] = normalized;
      else fields.value.push(normalized);
      if (selectedField.value?.name === normalized.name) selectField(normalized);
    } catch {
      const idx = fields.value.findIndex((f) => f.name === selectedField.value.name);
      if (idx >= 0) fields.value[idx] = { ...fields.value[idx], ...selectedField.value };
    }
  } catch (e) {
    emit('toast', e.message, 'error');
  } finally {
    savingField.value = false;
  }
}

function addCodeItem() {
  if (!selectedField.value.code_list) selectedField.value.code_list = [];
  selectedField.value.code_list.push({ code: null, label: null, description: '' });
  saveField();
}

function removeCodeItem(index) {
  selectedField.value.code_list.splice(index, 1);
  saveField();
}

async function addField() {
  if (!newField.name || !newField.data_type) {
    emit('toast', 'Field name and data type are required', 'error');
    return;
  }
  try {
    await api.upsertField(props.dbId, props.tableId, {
      name: newField.name,
      label: newField.label || newField.name,
      data_type: newField.data_type,
      column_type: newField.column_type || null,
      description: '',
    });
    emit('toast', `Field ${newField.name} added`, 'success');
    showAddDialog.value = false;
    Object.assign(newField, { name: '', label: '', data_type: 'string', column_type: '' });
    await loadSchema();
  } catch (e) {
    emit('toast', e.message, 'error');
  }
}

async function removeField(field) {
  if (!confirm(`Delete field "${field.name}"?`)) return;
  try {
    await api.deleteField(props.dbId, props.tableId, field.name);
    emit('toast', `Field ${field.name} deleted`, 'success');
    if (selectedField.value?.name === field.name) selectedField.value = null;
    await loadSchema();
  } catch (e) {
    emit('toast', e.message, 'error');
  }
}

async function syncDictionary() {
  if (!confirm('Remove dictionary fields that do not exist in the data?')) return;
  syncing.value = true;
  try {
    const data = await api.syncFields(props.dbId, props.tableId);
    emit(
      'toast',
      `Synced: ${data.fields_removed || 0} removed, ${data.fields_added || 0} added. Total: ${data.total_fields || 0}`,
      'success'
    );
    await loadSchema();
  } catch (e) {
    emit('toast', e.message, 'error');
  } finally {
    syncing.value = false;
  }
}

async function populate() {
  populating.value = true;
  try {
    const data = await api.populateFields(props.dbId, props.tableId);
    emit('toast', `Populated ${data.total_fields || 0} fields`, 'success');
    await loadSchema();
  } catch (e) {
    emit('toast', e.message, 'error');
  } finally {
    populating.value = false;
  }
}

function openConvertDialog() {
  fieldsForConversion.value = fields.value
    .filter((f) => !['date', 'datetime', 'null', ''].includes(f.data_type || ''))
    .map((f) => ({ name: f.name, current_type: f.data_type || 'string', target_type: f.data_type || 'string' }));
  selectedForConversion.value = [];
  fieldToAdd.value = null;
  showConvertDialog.value = true;
}

function addToConversion(field) {
  if (!field) return;
  if (selectedForConversion.value.some((f) => f.name === field.name)) return;
  selectedForConversion.value.push({ ...field });
  fieldToAdd.value = null;
}

function removeFromConversion(name) {
  selectedForConversion.value = selectedForConversion.value.filter((f) => f.name !== name);
}

async function runConvert() {
  const fieldsMap = {};
  selectedForConversion.value.forEach((f) => {
    fieldsMap[f.name] = f.target_type;
  });
  const payload = { on_error: convertOnError.value };
  if (Object.keys(fieldsMap).length) payload.fields = fieldsMap;
  converting.value = true;
  try {
    const data = await api.convertFieldTypes(props.dbId, props.tableId, payload);
    let msg = `Converted ${data.fields_converted || 0} field(s).`;
    if (data.documents_modified) msg += ` ${data.documents_modified} document(s) modified.`;
    emit('toast', msg, data.status === 'partial' ? 'warning' : 'success');
    showConvertDialog.value = false;
    await loadSchema();
  } catch (e) {
    emit('toast', e.message, 'error');
  } finally {
    converting.value = false;
  }
}

loadSchema();

defineExpose({ loadSchema });
</script>

<style scoped>
.dictionary-layout {
  min-height: 500px;
}
.dictionary-row {
  min-height: 500px;
}
.dictionary-list-col {
  border-right: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  display: flex;
  flex-direction: column;
}
.dictionary-list-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.dictionary-list-scroll {
  flex: 1;
  overflow-y: auto;
  max-height: calc(100vh - 280px);
}
.field-list-item {
  padding: 12px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  cursor: pointer;
}
.field-list-item:hover,
.field-list-item.active {
  background: rgba(var(--v-theme-primary), 0.08);
}
.dictionary-editor-col {
  min-height: 500px;
}
.dictionary-editor {
  overflow-y: auto;
  max-height: calc(100vh - 220px);
}
.dictionary-editor-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}
.conversion-list {
  max-height: 280px;
  overflow-y: auto;
}
.border {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
