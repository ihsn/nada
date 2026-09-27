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
        {{ t('Study view') }}
      </v-btn>
      <v-btn value="variable" size="small" class="text-none">
        {{ t('Variable view') }}
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
.view-toggle {
  border: 1px solid var(--color-button-primary, #0071bc);
  border-radius: 6px;
  height: 30px !important;
}

.view-toggle :deep(.v-btn) {
  font-size: 0.8rem;
  letter-spacing: 0;
  text-transform: none;
  padding: 0 12px;
  border: 0 !important;
  color: var(--color-button-primary, #0071bc) !important;
}

.view-toggle :deep(.v-btn.v-btn--active) {
  background: var(--color-button-primary, #0071bc) !important;
  color: #fff !important;
}

.view-toggle :deep(.v-btn.v-btn--active .v-btn__overlay) {
  opacity: 0;
}
</style>
