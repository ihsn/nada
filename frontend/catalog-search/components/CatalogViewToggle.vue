<template>
  <div class="catalog-view-toggle">
    <v-btn-toggle
      :model-value="modelValue"
      density="compact"
      variant="outlined"
      divided
      mandatory
      color="primary"
      class="view-toggle"
      @update:model-value="emit('update:modelValue', $event)"
    >
      <v-btn value="study" size="small" class="text-none">
        {{ t('switch_to_study_view') }}
      </v-btn>
      <v-btn value="variable" size="small" class="text-none">
        {{ t('switch_to_variable_view') }}
      </v-btn>
    </v-btn-toggle>
  </div>
</template>

<script setup>
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogViewToggle' });

defineProps({
  modelValue: { type: String, required: true },
});
const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
</script>

<style scoped>
.view-toggle :deep(.v-btn) {
  font-size: var(--catalog-font-ui, 0.875rem);
  letter-spacing: 0;
  text-transform: none;
}
</style>
