<template>
  <div class="cit-names">
    <div class="cit-field-label">{{ label }}</div>
    <div v-for="(n, i) in modelValue" :key="i" class="cit-names__row">
      <template v-if="isOrganization(n)">
        <v-text-field
          :model-value="n.literal"
          :label="t('cit_organization_name', 'Organization name')"
          density="compact"
          variant="outlined"
          hide-details
          @update:model-value="(v) => patch(i, { literal: v })"
        />
      </template>
      <template v-else>
        <v-text-field
          :model-value="n.family"
          :label="t('cit_family_name', 'Last name')"
          density="compact"
          variant="outlined"
          hide-details
          @update:model-value="(v) => patch(i, { family: v })"
        />
        <v-text-field
          :model-value="n.given"
          :label="t('cit_given_names', 'First name(s) and initials')"
          density="compact"
          variant="outlined"
          hide-details
          @update:model-value="(v) => patch(i, { given: v })"
        />
      </template>
      <v-btn
        :icon="isOrganization(n) ? 'mdi-account' : 'mdi-domain'"
        size="small"
        variant="text"
        :title="isOrganization(n) ? t('cit_is_person', 'This is a person') : t('cit_is_organization', 'This is an organization')"
        @click="toggleKind(i)"
      />
      <v-btn icon="mdi-arrow-up" size="small" variant="text" :disabled="i === 0" :title="t('cit_move_up', 'Move up')" @click="move(i, -1)" />
      <v-btn
        icon="mdi-arrow-down"
        size="small"
        variant="text"
        :disabled="i === modelValue.length - 1"
        :title="t('cit_move_down', 'Move down')"
        @click="move(i, 1)"
      />
      <v-btn icon="mdi-close" size="small" variant="text" :title="t('remove', 'Remove')" @click="remove(i)" />
    </div>
    <v-btn size="small" variant="text" prepend-icon="mdi-plus" @click="add">{{ addLabel || t('cit_add_name', 'Add') }}</v-btn>
  </div>
</template>

<script setup>
/** v-model is a list of CSL names: {family, given} for a person, {literal} for an organization. */
import { useI18n } from '@/shared/composables/useI18n';
import { isOrganization } from '../utils';

defineOptions({ name: 'NameListEditor' });

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  addLabel: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();

function emitList(list) {
  emit('update:modelValue', list);
}

function patch(i, change) {
  emitList(props.modelValue.map((n, k) => (k === i ? { ...n, ...change } : n)));
}

function add() {
  emitList([...props.modelValue, { family: '', given: '' }]);
}

function remove(i) {
  emitList(props.modelValue.filter((_, k) => k !== i));
}

function move(i, by) {
  const list = [...props.modelValue];
  const j = i + by;
  if (j < 0 || j >= list.length) return;
  [list[i], list[j]] = [list[j], list[i]];
  emitList(list);
}

function toggleKind(i) {
  const n = props.modelValue[i];
  if (isOrganization(n)) {
    emitList(props.modelValue.map((x, k) => (k === i ? { family: n.literal || '', given: '' } : x)));
  } else {
    const full = [n.given, n.family].filter(Boolean).join(' ');
    emitList(props.modelValue.map((x, k) => (k === i ? { literal: full } : x)));
  }
}
</script>

<style scoped>
.cit-names__row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
</style>
