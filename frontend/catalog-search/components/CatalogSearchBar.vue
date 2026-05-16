<template>
  <div class="catalog-search-bar">
    <v-text-field
      v-model="localValue"
      :placeholder="t('search_by_keyword')"
      density="comfortable"
      variant="solo"
      flat
      hide-details
      clearable
      class="search-input"
      bg-color="white"
      @keyup.enter="emit('search')"
      @click:clear="onClear"
    >
      <template #prepend-inner>
        <v-icon color="medium-emphasis" class="me-1">mdi-magnify</v-icon>
      </template>
      <template #append-inner>
        <v-btn
          color="primary"
          variant="flat"
          size="small"
          :loading="loading"
          class="search-btn"
          @click="emit('search')"
        >
          {{ t('search') }}
        </v-btn>
      </template>
    </v-text-field>

    <div v-if="resultCount != null" class="text-caption text-medium-emphasis mt-2 ms-1">
      <template v-if="resultCount > 0">
        {{ resultCount.toLocaleString() }} {{ resultCount === 1 ? t('dataset') : t('datasets') }} {{ t('found') }}
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogSearchBar' });

const props = defineProps({
  modelValue: { type: String, default: '' },
  loading:     { type: Boolean, default: false },
  resultCount: { type: Number, default: null },
});
const emit = defineEmits(['update:modelValue', 'search']);

const { t } = useI18n();

const localValue = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v ?? ''),
});

function onClear() {
  emit('update:modelValue', '');
  emit('search');
}
</script>

<style scoped>
.catalog-search-bar {
  max-width: 720px;
}

.search-input :deep(.v-field) {
  border: 1.5px solid rgba(25, 118, 210, 0.35);
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.search-input :deep(.v-field:hover) {
  border-color: rgba(25, 118, 210, 0.6);
}

.search-input :deep(.v-field--focused) {
  border-color: #1976d2;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.12);
}

.search-btn {
  border-radius: 6px;
  letter-spacing: 0.02em;
}
</style>
