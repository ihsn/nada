<template>
  <div class="sh-color-field">
    <label class="sh-color-field__swatch" :style="{ background: valid ? modelValue : 'transparent' }" :title="t('sh_pick_color', 'Pick a colour')">
      <input type="color" :value="valid ? expanded : '#ffffff'" @input="$emit('update:modelValue', $event.target.value)" />
      <v-icon v-if="!valid" icon="mdi-palette-outline" size="18" color="medium-emphasis" />
    </label>
    <v-text-field
      :model-value="modelValue"
      :placeholder="placeholder"
      :error-messages="error"
      density="compact"
      variant="outlined"
      :hide-details="!error"
      clearable
      @update:model-value="$emit('update:modelValue', ($event || '').trim())"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';

defineOptions({ name: 'ShColorField' });

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
});
defineEmits(['update:modelValue']);

const { t } = useSiteHeaderStore();
const valid = computed(() => /^#([0-9a-f]{3}){1,2}$/i.test(props.modelValue || ''));
const expanded = computed(() => {
  const v = props.modelValue || '';
  return v.length === 4 ? `#${v[1]}${v[1]}${v[2]}${v[2]}${v[3]}${v[3]}` : v;
});
</script>

<style scoped>
.sh-color-field {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}
.sh-color-field__swatch {
  position: relative;
  flex: none;
  width: 40px;
  height: 40px;
  border-radius: 6px;
  border: 1px solid rgba(var(--v-border-color), 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  overflow: hidden;
}
.sh-color-field__swatch input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}
</style>
