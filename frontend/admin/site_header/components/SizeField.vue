<template>
  <div class="sh-size">
    <v-icon :icon="icon" size="16" class="text-medium-emphasis" :title="iconTitle" />
    <v-slider
      :model-value="modelValue"
      :min="min"
      :max="max"
      :step="1"
      density="compact"
      color="primary"
      hide-details
      class="sh-size__slider"
      @update:model-value="(v) => $emit('update:modelValue', Math.round(v))"
    />
    <v-text-field
      :model-value="zeroLabel && !modelValue ? '' : modelValue"
      :placeholder="zeroLabel"
      type="number"
      :min="min"
      :max="max"
      density="compact"
      variant="outlined"
      hide-details
      suffix="px"
      class="sh-size__input"
      @update:model-value="onInput"
    />
  </div>
</template>

<script setup>
defineOptions({ name: 'ShSizeField' });

const props = defineProps({
  modelValue: { type: Number, default: 0 },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 200 },
  icon: { type: String, default: 'mdi-monitor' },
  iconTitle: { type: String, default: '' },
  zeroLabel: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

function onInput(v) {
  if (v === '' || v === null) {
    if (props.zeroLabel) emit('update:modelValue', 0);
    return;
  }
  const n = Number(v);
  if (!Number.isFinite(n)) return;
  emit('update:modelValue', Math.max(props.min, Math.min(props.max, Math.round(n))));
}
</script>

<style scoped>
.sh-size {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
  min-height: 32px;
}
.sh-size > .v-icon {
  flex: none;
  width: 18px;
  margin-inline-end: 2px;
}
.sh-size__slider {
  flex: 1 1 auto;
  min-width: 80px;
  padding-inline-start: 4px;
}
.sh-size__slider :deep(.v-slider) {
  margin-inline: 0;
}
.sh-size__slider :deep(.v-slider__container) {
  margin-inline-start: 2px;
}
.sh-size__input {
  flex: 0 0 78px;
}
.sh-size__input :deep(.v-field) {
  --v-input-control-height: 32px;
  font-size: 0.8125rem;
}
.sh-size__input :deep(.v-field__field) {
  padding-inline: 6px;
}
.sh-size__input :deep(.v-field__input) {
  min-height: 32px !important;
  padding: 0 !important;
  align-items: center;
}
.sh-size__input :deep(input) {
  padding: 0 !important;
  line-height: 1.2;
}
.sh-size__input :deep(.v-field__append-inner) {
  padding: 0 !important;
  margin: 0;
  align-items: center;
  padding-inline-start: 2px !important;
}
.sh-size__input :deep(.v-text-field__suffix) {
  padding: 0;
  font-size: 0.75rem;
  opacity: 0.7;
}
</style>
