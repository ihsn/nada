<script setup>
import { computed } from 'vue';
import { useMetadataFormStore } from '../composables/useMetadataFormStore';
import { enumToSelectItems, resolveDisplayType } from '../utils/enumOptions';
import MetadataFieldHelp from './MetadataFieldHelp.vue';

const props = defineProps({
  field: { type: Object, required: true },
  path: { type: String, required: true },
});

const store = useMetadataFormStore();

const displayType = computed(() => resolveDisplayType(props.field));
const helpText = computed(() => props.field.help_text || props.field.help || '');
const label = computed(() => props.field.title || props.field.key || '');
const required = computed(() => {
  if (props.field.is_required || props.field.required || props.field.isRequired) return true;
  const when = props.field.required_when;
  if (!when) return false;
  const flag = store.getValue(when);
  return flag === true || flag === 1 || flag === '1' || flag === 'true';
});

const model = computed({
  get() {
    const v = store.getValue(props.path);
    return v === undefined || v === null ? '' : v;
  },
  set(v) {
    store.setValue(props.path, v);
  },
});

const selectItems = computed(() => enumToSelectItems(props.field.enum));
const isDropdown = computed(
  () => displayType.value === 'dropdown' || displayType.value === 'dropdown-custom'
);
const allowCustom = computed(() => displayType.value === 'dropdown-custom');
</script>

<template>
  <div class="mf-field">
    <MetadataFieldHelp :label="label" :help-text="helpText" :required="required" />

    <v-textarea
      v-if="displayType === 'textarea'"
      v-model="model"
      density="compact"
      variant="outlined"
      auto-grow
      rows="2"
      hide-details="auto"
    />
    <v-select
      v-else-if="isDropdown && !allowCustom"
      v-model="model"
      :items="selectItems"
      item-title="title"
      item-value="value"
      density="compact"
      variant="outlined"
      clearable
      hide-details="auto"
    />
    <v-combobox
      v-else-if="isDropdown && allowCustom"
      v-model="model"
      :items="selectItems"
      item-title="title"
      item-value="value"
      density="compact"
      variant="outlined"
      clearable
      hide-details="auto"
    />
    <v-checkbox
      v-else-if="field.type === 'boolean' && !isDropdown"
      v-model="model"
      density="compact"
      hide-details="auto"
      :label="label"
    />
    <v-text-field
      v-else-if="displayType === 'date' || field.type === 'date'"
      v-model="model"
      type="date"
      density="compact"
      variant="outlined"
      hide-details="auto"
    />
    <v-text-field
      v-else
      v-model="model"
      density="compact"
      variant="outlined"
      hide-details="auto"
      :type="field.type === 'integer' || field.type === 'number' ? 'number' : 'text'"
    />
  </div>
</template>

<style scoped>
.mf-field {
  margin-bottom: 12px;
}
</style>
