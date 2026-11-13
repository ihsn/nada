<template>
  <div>
    <div v-if="rawMode" class="d-flex ga-2 align-center">
      <v-text-field
        :model-value="rawText"
        :label="label"
        density="compact"
        variant="outlined"
        hide-details="auto"
        :hint="t('cit_date_as_entered', 'This date could not be split into year, month and day.')"
        persistent-hint
        @update:model-value="onRaw"
      />
      <v-btn size="small" variant="text" @click="clearRaw">{{ t('cit_date_use_parts', 'Enter year, month, day') }}</v-btn>
    </div>
    <div v-else>
      <div class="d-flex ga-2">
        <v-text-field
          :model-value="year"
          :label="label"
          :placeholder="t('cit_year', 'Year')"
          density="compact"
          variant="outlined"
          hide-details="auto"
          maxlength="4"
          inputmode="numeric"
          :error-messages="yearError"
          style="min-width: 110px"
          @update:model-value="(v) => set('year', v)"
        />
        <v-select
          :model-value="month"
          :items="monthItems"
          :placeholder="t('cit_month', 'Month')"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          :disabled="!year"
          @update:model-value="(v) => set('month', v)"
        />
        <v-text-field
          :model-value="day"
          :placeholder="t('cit_day', 'Day')"
          density="compact"
          variant="outlined"
          hide-details
          maxlength="2"
          inputmode="numeric"
          :disabled="!month"
          style="max-width: 90px"
          @update:model-value="(v) => set('day', v)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
/** v-model is a CSL date: {'date-parts': [[year, month?, day?]]}, or {raw} for a date that can't be split. */
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { MONTHS, buildDate, dateParts } from '../utils';

defineOptions({ name: 'CslDateInput' });

const props = defineProps({
  modelValue: { type: Object, default: undefined },
  label: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();

const year = ref('');
const month = ref(null);
const day = ref('');
const forceParts = ref(false);

const monthItems = computed(() => MONTHS.map((m, i) => ({ title: t(`cit_month_${i + 1}`, m), value: i + 1 })));

function fromModel() {
  const p = dateParts(props.modelValue);
  year.value = p[0] ? String(p[0]) : '';
  month.value = p[1] || null;
  day.value = p[2] ? String(p[2]) : '';
}
fromModel();

// the date changed from outside (DOI lookup, loading): refresh unless it is what this input just sent
watch(
  () => props.modelValue,
  (v) => {
    const mine = buildDate(year.value, month.value, day.value);
    if (JSON.stringify(mine) !== JSON.stringify(v)) fromModel();
  },
  { deep: true }
);

const hasParts = computed(() => dateParts(props.modelValue).length > 0);
const rawText = computed(() => (props.modelValue && (props.modelValue.raw || props.modelValue.literal)) || '');
const rawMode = computed(() => !forceParts.value && !hasParts.value && !!rawText.value);
const yearError = computed(() => (year.value && !buildDate(year.value) ? [t('cit_err_date', 'This date is not valid.')] : []));

function set(field, value) {
  if (field === 'year') year.value = String(value ?? '').replace(/\D/g, '');
  if (field === 'month') {
    month.value = value || null;
    if (!month.value) day.value = '';
  }
  if (field === 'day') day.value = String(value ?? '').replace(/\D/g, '');
  emit('update:modelValue', buildDate(year.value, month.value, day.value));
}

function onRaw(v) {
  const s = String(v ?? '').trim();
  emit('update:modelValue', s ? { raw: s } : undefined);
}

function clearRaw() {
  forceParts.value = true;
  emit('update:modelValue', undefined);
}
</script>
