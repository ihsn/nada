<script setup>
/**
 * Array field router:
 * - type "array" (scalar columns) → ME-style table grid
 * - type "nested_array" (or nested props) → card/stack editor
 */
import { computed } from 'vue';
import { emptyRowForProps, enumToSelectItems, normalizeProps } from '../utils/enumOptions';
import { useMetadataFormStore } from '../composables/useMetadataFormStore';
import MetadataArrayGrid from './MetadataArrayGrid.vue';
import MetadataFieldHelp from './MetadataFieldHelp.vue';
import MetadataFieldInput from './MetadataFieldInput.vue';
import MetadataSimpleArrayField from './MetadataSimpleArrayField.vue';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';

const props = defineProps({
  field: { type: Object, required: true },
  path: { type: String, required: true },
});

const store = useMetadataFormStore();
const labels = useMetadataFormLabels();
const propDefs = computed(() => normalizeProps(props.field.props));
const helpText = computed(() => props.field.help_text || props.field.help || '');
const label = computed(() => props.field.title || props.field.key || '');

const hasNestedProps = computed(() =>
  propDefs.value.some((p) =>
    ['array', 'nested_array', 'simple_array', 'section', 'section_container'].includes(p.type)
  )
);

const useGrid = computed(() => props.field.type === 'array' && !hasNestedProps.value);

const rows = computed({
  get() {
    const v = store.getValue(props.path);
    return Array.isArray(v) ? v : [];
  },
  set(v) {
    store.setValue(props.path, v);
  },
});

function addRow() {
  rows.value = [...rows.value, emptyRowForProps(propDefs.value)];
}

function removeRow(index) {
  const next = rows.value.slice();
  next.splice(index, 1);
  rows.value = next;
}

function rowPath(index, propKey) {
  return `${props.path}.${index}.${propKey}`;
}

function isScalarProp(prop) {
  return !['array', 'nested_array', 'simple_array', 'section', 'section_container'].includes(
    prop.type
  );
}

const enumQuickAdds = computed(() => {
  const items = enumToSelectItems(props.field.enum);
  return items.filter((i) => i.raw && (i.raw.abbreviation || i.raw.name));
});

function addFromEnum(item) {
  const raw = item.raw || {};
  const row = emptyRowForProps(propDefs.value);
  if (Object.prototype.hasOwnProperty.call(row, 'name') && raw.name != null) {
    row.name = raw.name;
  }
  if (Object.prototype.hasOwnProperty.call(row, 'abbreviation') && raw.abbreviation != null) {
    row.abbreviation = raw.abbreviation;
  }
  rows.value = [...rows.value, row];
}
</script>

<template>
  <MetadataArrayGrid v-if="useGrid" :field="field" :path="path" />

  <div v-else class="mf-array">
    <div class="d-flex align-start justify-space-between mb-2 flex-wrap ga-2">
      <div class="flex-grow-1" style="min-width: 160px">
        <MetadataFieldHelp :label="label" :help-text="helpText" />
      </div>
      <div class="d-flex ga-2 flex-wrap">
        <v-menu v-if="enumQuickAdds.length" location="bottom end">
          <template #activator="{ props: menuProps }">
            <v-btn v-bind="menuProps" size="small" variant="tonal" class="text-none">
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
        <v-btn size="small" color="primary" variant="tonal" class="text-none" @click="addRow">
          {{ labels.add }}
        </v-btn>
      </div>
    </div>

    <div v-if="!rows.length" class="text-caption text-medium-emphasis mb-2">{{ labels.noItems }}</div>

    <v-card
      v-for="(row, index) in rows"
      :key="index"
      variant="outlined"
      class="mb-3 mf-array-row"
    >
      <v-card-title class="d-flex align-center justify-space-between text-body-2 py-2 px-3">
        <span>#{{ index + 1 }}</span>
        <v-btn
          icon="mdi-delete-outline"
          size="x-small"
          variant="text"
          color="error"
          @click="removeRow(index)"
        />
      </v-card-title>
      <v-card-text class="pt-0">
        <template v-for="prop in propDefs" :key="prop.key">
          <MetadataArrayField
            v-if="prop.type === 'array' || prop.type === 'nested_array'"
            :field="prop"
            :path="rowPath(index, prop.key)"
          />
          <MetadataSimpleArrayField
            v-else-if="prop.type === 'simple_array'"
            :field="prop"
            :path="rowPath(index, prop.key)"
          />
          <MetadataFieldInput
            v-else-if="isScalarProp(prop)"
            :field="prop"
            :path="rowPath(index, prop.key)"
          />
        </template>
      </v-card-text>
    </v-card>
  </div>
</template>

<style scoped>
.mf-array-row {
  background: rgba(var(--v-theme-surface), 1);
}
</style>
