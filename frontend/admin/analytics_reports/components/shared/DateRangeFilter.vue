<template>
  <v-row dense>
    <v-col cols="12" sm="4" md="3">
      <FormField :label="t('analytics_from', 'From')">
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
      <FormField :label="t('analytics_to', 'To')">
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
    <v-col v-if="showStudyFilter" cols="12" sm="4" md="3">
      <FormField :label="t('analytics_study_filter', 'Study (IDNO or ID)')">
        <v-text-field
          v-model="local.studyId"
          density="compact"
          variant="outlined"
          hide-details
          clearable
        />
      </FormField>
    </v-col>
    <v-col cols="12" :md="showStudyFilter ? 3 : 6" class="d-flex align-end ga-2 pb-1">
      <v-btn color="primary" :loading="loading" @click="emitApply">{{ t('analytics_filter', 'Filter') }}</v-btn>
      <v-btn variant="outlined" :disabled="loading" @click="emitClear">{{ t('analytics_clear', 'Clear') }}</v-btn>
    </v-col>
  </v-row>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import FormField from './FormField.vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({ date_from: '', date_to: '', study_id: '' }) },
  loading: { type: Boolean, default: false },
  /** When set, Clear resets to this range instead of empty dates. */
  resetOnClear: { type: Object, default: null },
  showStudyFilter: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'apply', 'clear']);

defineOptions({ name: 'DateRangeFilter' });

const { t } = useI18n();
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
