<template>
  <div class="tables-form-field" :class="{ 'mb-3': !dense, 'mb-2': dense }">
    <label v-if="label" class="tables-field-label">
      {{ label }}<span v-if="required" class="text-error"> *</span>
    </label>
    <p v-if="hint" class="tables-field-hint mb-1">{{ hint }}</p>
    <slot />
  </div>
</template>

<script setup>
defineOptions({ name: 'TablesFormField' });

defineProps({
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  dense: { type: Boolean, default: false },
});
</script>

<style scoped>
.tables-field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.75);
  margin-bottom: 4px;
}

.tables-field-hint {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin: 0;
}
</style>
