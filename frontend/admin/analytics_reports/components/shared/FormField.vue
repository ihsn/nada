<template>
  <div class="analytics-field-group">
    <label v-if="label" class="analytics-field-label" :for="fieldId">{{ label }}</label>
    <slot :field-id="fieldId" />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, default: '' },
  id: { type: String, default: '' },
});

defineOptions({ name: 'FormField' });

const fieldId = computed(() => props.id || undefined);
</script>
