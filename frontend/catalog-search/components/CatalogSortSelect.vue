<template>
  <div class="d-flex align-center flex-shrink-0">
    <v-select
      :model-value="activeValue"
      :items="sortOptions"
      item-title="label"
      item-value="value"
      density="compact"
      variant="outlined"
      hide-details
      style="min-width: 160px;"
      class="sort-select"
      :menu-props="{ contentClass: 'sort-select-menu' }"
      @update:model-value="onSelect"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import {
  buildCatalogSortOptions,
  catalogSortSelectionKey,
  parseCatalogSortValue,
} from '../catalogSortOptions';

defineOptions({ name: 'CatalogSortSelect' });

const props = defineProps({
  query: { type: Object, required: true },
});
const emit = defineEmits(['sort']);

const { t } = useI18n();

const sortOptions = computed(() => buildCatalogSortOptions(t));

const activeValue = computed(() =>
  catalogSortSelectionKey(props.query.sort_by, props.query.sort_order)
);

function onSelect(composite) {
  const { sortBy, sortOrder } = parseCatalogSortValue(composite);
  emit('sort', sortBy, sortOrder);
}
</script>

<style scoped>
.sort-select :deep(.v-field__input) {
  font-size: var(--catalog-font-ui, 0.875rem);
  padding-top: 2px;
  padding-bottom: 2px;
  min-height: unset;
}

.sort-select :deep(.v-select__selection-text) {
  font-size: var(--catalog-font-ui, 0.875rem);
}

.sort-select :deep(.v-field) {
  min-height: 28px;
}
</style>

<style>
.sort-select-menu .v-list-item {
  min-height: 30px !important;
  padding-top: 2px;
  padding-bottom: 2px;
}

.sort-select-menu .v-list-item-title {
  font-size: var(--catalog-font-ui, 0.875rem) !important;
  line-height: 1.25;
}
</style>
