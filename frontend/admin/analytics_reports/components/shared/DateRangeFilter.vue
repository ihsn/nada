<template>
  <v-row dense>
    <v-col cols="12" sm="4" md="3">
      <FormField label="From">
        <v-text-field
          v-model="local.from"
          type="date"
          density="compact"
          variant="outlined"
          hide-details
          clearable
        />
      </FormField>
    </v-col>
    <v-col cols="12" sm="4" md="3">
      <FormField label="To">
        <v-text-field
          v-model="local.to"
          type="date"
          density="compact"
          variant="outlined"
          hide-details
          clearable
        />
      </FormField>
    </v-col>
    <v-col cols="12" sm="4" md="3">
      <FormField label="Study (IDNO or ID)">
        <v-text-field
          v-model="local.studyId"
          density="compact"
          variant="outlined"
          hide-details
          clearable
        />
      </FormField>
    </v-col>
    <v-col cols="12" md="3" class="d-flex align-end ga-2 pb-1">
      <v-btn color="primary" :loading="loading" @click="emitApply">Filter</v-btn>
      <v-btn variant="outlined" :disabled="loading" @click="emitClear">Clear</v-btn>
    </v-col>
  </v-row>
</template>

<script setup>
import { reactive, watch } from 'vue';
import FormField from './FormField.vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({ date_from: '', date_to: '', study_id: '' }) },
  loading: { type: Boolean, default: false },
  /** When set, Clear resets to this range instead of empty dates. */
  resetOnClear: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'apply', 'clear']);

defineOptions({ name: 'DateRangeFilter' });

const local = reactive({
  from: props.modelValue.date_from || '',
  to: props.modelValue.date_to || '',
  studyId: props.modelValue.study_id || '',
});

watch(
  () => props.modelValue,
  (v) => {
    local.from = v.date_from || '';
    local.to = v.date_to || '';
    local.studyId = v.study_id || '';
  },
  { deep: true }
);

function emitApply() {
  const next = {
    date_from: local.from || '',
    date_to: local.to || '',
    study_id: local.studyId || '',
  };
  emit('update:modelValue', next);
  emit('apply', next);
}

function emitClear() {
  const base = props.resetOnClear || { date_from: '', date_to: '', study_id: '' };
  const next = {
    date_from: base.date_from || '',
    date_to: base.date_to || '',
    study_id: base.study_id || '',
  };
  local.from = next.date_from;
  local.to = next.date_to;
  local.studyId = next.study_id;
  emit('update:modelValue', next);
  emit('clear', next);
}
</script>
