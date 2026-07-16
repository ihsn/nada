<template>
  <div class="facet-checkbox-panel">
    <div
      v-if="showSubheader"
      class="facet-filter-subheader d-flex align-center flex-wrap gap-1"
    >
      <span class="facet-filter-subheader__count">{{ selection.length }}</span>
      <span class="facet-filter-subheader__suffix">selected</span>
      <v-spacer />
      <template v-if="allowSelectAll">
        <v-btn
          variant="text"
          density="compact"
          size="x-small"
          class="text-none facet-filter-subheader__action"
          @click="onToggleSelectAllVisible"
        >
          {{ allVisibleSelected ? 'Deselect all' : 'Select all' }}
        </v-btn>
      </template>
      <v-btn
        variant="text"
        density="compact"
        size="x-small"
        class="text-none facet-filter-subheader__clear"
        prepend-icon="mdi-close"
        :aria-label="clearAriaLabel"
        @click="$emit('clear')"
      >
        Clear
      </v-btn>
    </div>

    <div v-if="items?.length" class="facet-checkbox-panel__body">
      <div v-if="needsSearch" class="facet-checkbox-search-wrap">
        <v-text-field
          v-model="searchModel"
          density="compact"
          variant="solo-filled"
          flat
          hide-details
          prepend-inner-icon="mdi-magnify"
          placeholder="Search codes…"
          clearable
          class="facet-checkbox-search rounded-lg"
        />
      </div>
      <div class="facet-checkbox-scroll">
        <v-list density="compact" class="facet-checkbox-list bg-transparent py-0">
          <v-list-item
            v-for="opt in filteredItems"
            :key="componentName + '-' + opt.value"
            class="facet-checkbox-row py-0"
            @click="onToggle(opt.value)"
          >
            <template #prepend>
              <v-checkbox
                :model-value="isSelected(opt.value)"
                hide-details
                density="compact"
                class="facet-checkbox-control"
                @click.stop
                @update:model-value="(on) => onSetSelected(opt.value, on)"
              />
            </template>
            <v-list-item-title class="facet-checkbox-item-title text-wrap">
              {{ opt.title }}
            </v-list-item-title>
          </v-list-item>
        </v-list>
        <p v-if="!filteredItems.length" class="facet-checkbox-empty text-medium-emphasis mb-0">
          No matching codes.
        </p>
      </div>
    </div>
    <div v-else class="px-2 pb-2">
      <v-combobox
        :model-value="selection"
        multiple
        chips
        closable-chips
        density="compact"
        variant="solo-filled"
        flat
        hide-details
        :placeholder="comboboxPlaceholder"
        class="rounded-lg"
        @update:model-value="onComboboxInput"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  facetListNeedsSearch,
  facetListItemsFiltered,
  facetCodesInclude,
  facetSetCodeSelected,
  facetToggleCode,
  selectAllVisible,
  deselectAllVisible,
} from '../composables/useIndicatorFacetFilters.js';

defineOptions({ name: 'IndicatorFacetCheckboxPanel' });

const props = defineProps({
  componentName: {
    type: String,
    required: true,
  },
  selection: {
    type: Array,
    required: true,
  },
  items: {
    type: Array,
    default: () => [],
  },
  allowSelectAll: {
    type: Boolean,
    default: true,
  },
  clearAriaLabel: {
    type: String,
    default: 'Clear selection',
  },
  comboboxPlaceholder: {
    type: String,
    default: 'Codes (comma-separated chips)',
  },
  searchText: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['clear', 'update:searchText']);

const needsSearch = computed(() => facetListNeedsSearch(props.items));
const filteredItems = computed(() => {
  const map = { [props.componentName]: props.searchText };
  return facetListItemsFiltered(props.componentName, props.items, map);
});
const showSubheader = computed(() => props.selection.length > 0);

const allVisibleSelected = computed(() => {
  const visible = filteredItems.value;
  if (!visible.length) return false;
  return visible.every((opt) => isSelected(opt.value));
});

const searchModel = computed({
  get: () => props.searchText,
  set: (val) => emit('update:searchText', val ?? ''),
});

function isSelected(code) {
  return facetCodesInclude(props.selection, code);
}

function onToggle(code) {
  facetToggleCode(props.selection, code);
}

function onSetSelected(code, on) {
  facetSetCodeSelected(props.selection, code, on);
}

function onSelectAll() {
  const map = { [props.componentName]: props.searchText };
  selectAllVisible(props.selection, props.componentName, props.items, map);
}

function onToggleSelectAllVisible() {
  if (allVisibleSelected.value) {
    onDeselectAll();
  } else {
    onSelectAll();
  }
}

function onDeselectAll() {
  const map = { [props.componentName]: props.searchText };
  deselectAllVisible(props.selection, props.componentName, props.items, map);
}

function onComboboxInput(val) {
  if (!Array.isArray(props.selection)) return;
  props.selection.splice(0, props.selection.length, ...(Array.isArray(val) ? val.map(String) : []));
}
</script>

<style scoped>
.facet-checkbox-panel {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.facet-filter-subheader {
  padding: 0.35rem 0.65rem 0.4rem;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  font-size: 0.6875rem;
  line-height: 1.35;
  font-weight: 400;
  color: rgba(var(--v-theme-on-surface), 0.48);
}

.facet-filter-subheader__count,
.facet-filter-subheader__suffix,
.facet-filter-subheader__action,
.facet-filter-subheader__clear {
  font-weight: 400;
  font-size: inherit;
  color: inherit;
}

.facet-filter-subheader__suffix {
  margin-inline-start: 0.2rem;
}

.facet-filter-subheader__action,
.facet-filter-subheader__clear {
  letter-spacing: normal;
}

.facet-filter-subheader__clear :deep(.v-icon) {
  opacity: 0.65;
}

.facet-checkbox-search-wrap {
  padding: 3px;
  margin-bottom: 4px;
}

.facet-checkbox-search :deep(.v-field) {
  font-size: 0.6875rem;
}

.facet-checkbox-search :deep(.v-field__input) {
  min-height: 28px;
  padding-block: 2px;
  font-size: 0.6875rem;
}

.facet-checkbox-scroll {
  max-height: 300px;
  overflow-y: auto;
  border: none;
  border-radius: 0;
  background: transparent;
  padding: 0;
}

.facet-checkbox-scroll :deep(.v-list) {
  padding: 0;
}

.facet-checkbox-scroll :deep(.facet-checkbox-row) {
  padding-inline: 0 !important;
}

.facet-checkbox-list {
  font-size: 0.75rem;
}

.facet-checkbox-item-title {
  font-size: 0.75rem !important;
  line-height: 1.35 !important;
  font-weight: 400;
}

.facet-checkbox-empty {
  font-size: 0.6875rem;
  padding: 0.35rem 0.65rem;
}

.facet-checkbox-row {
  min-height: 32px;
  cursor: pointer;
}

.facet-checkbox-control {
  flex: none;
}

.facet-checkbox-control :deep(.v-selection-control) {
  min-height: 32px;
}
</style>
