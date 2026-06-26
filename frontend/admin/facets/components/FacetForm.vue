<template>
  <div>
    <!-- Facet details -->
    <v-card elevation="1" class="mb-4">
      <v-card-title class="text-subtitle-1 font-weight-medium pa-4 pb-3">Facet Details</v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <v-row>
          <v-col cols="12" md="3">
            <div class="field-group">
              <label class="field-label">Name <span class="text-error">*</span></label>
              <v-text-field
                v-model="form.name"
                variant="outlined"
                density="compact"
                hide-details="auto"
                placeholder="A short name with no spaces"
                hint="Used as the unique identifier"
                persistent-hint
                :disabled="nameDisabled"
              />
            </div>
          </v-col>
          <v-col cols="12" md="5">
            <div class="field-group">
              <label class="field-label">Title <span class="text-error">*</span></label>
              <v-text-field
                v-model="form.title"
                variant="outlined"
                density="compact"
                hide-details="auto"
                placeholder="Display title"
              />
            </div>
          </v-col>
          <v-col cols="12" md="2">
            <div class="field-group">
              <label class="field-label">Status <span class="text-error">*</span></label>
              <v-select
                v-model="form.enabled"
                variant="outlined"
                density="compact"
                hide-details
                :items="[{ title: 'Enabled', value: '1' }, { title: 'Disabled', value: '0' }]"
              />
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Mappings -->
    <v-card elevation="1">
      <v-card-title class="text-subtitle-1 font-weight-medium pa-4 pb-3">Mappings</v-card-title>
      <v-divider />
      <v-data-table
        :headers="tableHeaders"
        :items="dataTypes"
        item-value="self"
        hide-default-footer
        :items-per-page="-1"
        class="elevation-0 mappings-table"
      >
        <template #item="{ item: dtype }">
          <tr>
            <td class="text-capitalize font-weight-medium">{{ dtype }}</td>
            <td>
              <v-combobox
                v-model="form.options[dtype].field"
                :items="getFieldItems(dtype)"
                item-title="text"
                item-value="value"
                :return-object="false"
                variant="outlined"
                density="compact"
                hide-details
                clearable
                class="my-1"
                @update:model-value="fieldChanged(dtype, $event)"
              />
            </td>
            <td>
              <v-combobox
                v-model="form.options[dtype].subfield"
                :items="getSubfieldItems(dtype, form.options[dtype].field)"
                :disabled="!isSubfieldEnabled(dtype, form.options[dtype].field)"
                variant="outlined"
                density="compact"
                hide-details
                clearable
                :placeholder="isSubfieldEnabled(dtype, form.options[dtype].field) && !getSubfieldItems(dtype, form.options[dtype].field).length ? 'Custom value…' : ''"
                class="my-1"
              />
            </td>
            <td>
              <v-combobox
                v-if="isSubfieldEnabled(dtype, form.options[dtype].field)"
                v-model="form.options[dtype].filter"
                :items="getSubfieldItems(dtype, form.options[dtype].field)"
                variant="outlined"
                density="compact"
                hide-details
                clearable
                :placeholder="getSubfieldItems(dtype, form.options[dtype].field).length ? '' : 'Custom value…'"
                class="my-1"
              />
            </td>
            <td>
              <v-text-field
                v-if="isSubfieldEnabled(dtype, form.options[dtype].field)"
                v-model="form.options[dtype].filter_value"
                variant="outlined"
                density="compact"
                hide-details
                class="my-1"
              />
            </td>
          </tr>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script setup>
import { inject } from 'vue';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';

const props = defineProps({
  form:         { type: Object,  required: true },
  nameDisabled: { type: Boolean, default: false },
});

const config    = inject(APP_CONFIG_KEY, window.APP_CONFIG || {});
const dataTypes = config.dataTypes || [];
const fields    = config.fields    || {};

const tableHeaders = [
  { title: 'Data Type',    key: 'dtype',        sortable: false, width: 130 },
  { title: 'Field',        key: 'field',        sortable: false, minWidth: 200 },
  { title: 'Subfield',     key: 'subfield',     sortable: false, minWidth: 180, subtitle: '(composite types)' },
  { title: 'Filter',       key: 'filter',       sortable: false, minWidth: 180 },
  { title: 'Filter Value', key: 'filter_value', sortable: false, minWidth: 160 },
];

function getFieldItems(dtype) {
  const typeFields = fields[dtype] || {};
  const items = [{ text: '— Select —', value: '' }];
  for (const [key, field] of Object.entries(typeFields)) {
    if (!field) continue;
    if (field.items)                  items.push({ text: key + ' *', value: key });
    else if (field.type === 'string') items.push({ text: key, value: key });
  }
  return items;
}

function resolveKey(k) {
  return (k && typeof k === 'object') ? k.value : k;
}

function getSubfields(dtype, fieldKey) {
  fieldKey = resolveKey(fieldKey);
  if (!fieldKey || typeof fieldKey !== 'string') return false;
  const f = (fields[dtype] || {})[fieldKey];
  if (!f || f.type !== 'array') return false;
  return (f.items && f.items.properties) || false;
}

function getSubfieldItems(dtype, fieldKey) {
  const props = getSubfields(dtype, fieldKey);
  return props ? Object.keys(props) : [];
}

function isSubfieldEnabled(dtype, fieldKey) {
  fieldKey = resolveKey(fieldKey);
  if (!fieldKey) return false;
  const f = (fields[dtype] || {})[fieldKey];
  if (!f) return true;
  return f.type === 'array';
}

function fieldChanged(dtype, fieldKey) {
  fieldKey = resolveKey(fieldKey);
  if (!fieldKey) { props.form.options[dtype].subfield = ''; return; }
  const f = (fields[dtype] || {})[fieldKey];
  if (f && f.type === 'array') {
    try {
      const keys = Object.keys(f.items.properties);
      props.form.options[dtype].subfield = keys.length ? keys[0] : '';
    } catch {
      props.form.options[dtype].subfield = '';
    }
  } else {
    props.form.options[dtype].subfield = '';
  }
}
</script>

<style scoped>
.field-group {
  margin-bottom: 4px;
}
.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.7);
  margin-bottom: 4px;
}
.mappings-table :deep(th:nth-child(3) .v-data-table-header__content::after) {
  content: ' (composite types)';
  font-size: 0.75rem;
  font-weight: 400;
  color: rgba(0, 0, 0, 0.5);
}
</style>
