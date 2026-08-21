<template>
  <div class="catalog-image-view-toggle">
    <v-btn-toggle
      :model-value="modelValue"
      density="compact"
      variant="outlined"
      divided
      mandatory
      color="primary"
      class="image-view-toggle"
      @update:model-value="emit('update:modelValue', $event)"
    >
      <v-btn value="details" size="small" class="text-none">
        <v-icon start size="16">$mdi-format-list-bulleted</v-icon>
        {{ t('image_details_view', 'Details view') }}
      </v-btn>
      <v-btn value="gallery" size="small" class="text-none">
        <v-icon start size="16">$mdi-view-grid</v-icon>
        {{ t('image_gallery_view', 'Gallery view') }}
      </v-btn>
    </v-btn-toggle>
  </div>
</template>

<script setup>
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogImageViewToggle' });

defineProps({
  modelValue: { type: String, required: true },
});
const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
</script>

<style scoped>
.image-view-toggle :deep(.v-btn) {
  font-size: 0.8125rem;
  letter-spacing: 0;
  text-transform: none;
}
</style>
