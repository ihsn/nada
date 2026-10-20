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
  /** The variable view's sorts (relevance, study title, variable name). */
  variables: { type: Boolean, default: false },
});
const emit = defineEmits(['sort']);

const { t } = useI18n();

const sortOptions = computed(() => buildCatalogSortOptions(t, { variables: props.variables }));

const activeValue = computed(() => {
  // a variable browse (no keyword) with no sort chosen is sorted by study title, not relevance
  const keywords = props.variables ? (props.query.vk || props.query.sk) : props.query.sk;
  if (props.variables && !props.query.sort_by && !keywords) {
    return catalogSortSelectionKey('title', 'asc');
  }
  return catalogSortSelectionKey(props.query.sort_by, props.query.sort_order);
});

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
