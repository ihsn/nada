<template>
  <div class="facet-checkbox-list">
    <v-text-field
      v-if="showSearch"
      v-model="searchText"
      density="compact"
      variant="outlined"
      hide-details
      clearable
      class="facet-checkbox-list__search mb-2"
      :placeholder="t('apply_filter', 'Filter...')"
      prepend-inner-icon="$mdi-magnify"
    />

    <div class="facet-checkbox-list__items">
      <template v-for="section in visibleSections" :key="section.key">
        <div v-if="section.groupName" class="facet-group-title">
          {{ section.groupName }}
        </div>
        <label
          v-for="item in section.items"
          :key="`${filterKey}-${item.value}`"
          class="facet-checkbox-item"
          :class="{ 'facet-checkbox-item--hidden': !item._visible }"
        >
          <input
            type="checkbox"
            class="facet-checkbox-item__input"
            :checked="isChecked(item.value)"
            @change="onToggle(item.value, $event.target.checked)"
          />
          <span class="facet-checkbox-item__label">
            {{ item.label }}
            <span v-if="item.count != null" class="facet-checkbox-item__count">({{ item.count }})</span>
          </span>
        </label>
      </template>

      <div v-if="!hasVisibleItems" class="text-caption text-medium-emphasis py-2">
        {{ t('no_records_found') }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogFacetCheckboxList' });

const SEARCH_THRESHOLD = 15;

const props = defineProps({
  filterKey: { type: String, required: true },
  items:     { type: Array,  required: true },
  modelValue:{ type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'change']);

const { t } = useI18n();
const searchText = ref('');

const showSearch = computed(() => props.items.length > SEARCH_THRESHOLD);

const selectedSet = computed(() => {
  if (!props.modelValue) return new Set();
  return new Set(
    String(props.modelValue)
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
  );
});

function isChecked(value) {
  return selectedSet.value.has(String(value));
}

function onToggle(value, checked) {
  const next = new Set(selectedSet.value);
  const strVal = String(value);
  if (checked) next.add(strVal);
  else next.delete(strVal);
  const out = [...next].join(',');
  emit('update:modelValue', out);
  emit('change');
}

const filteredItems = computed(() => {
  const q = searchText.value.trim().toLowerCase();
  return props.items.map((item) => ({
    ...item,
    _visible: !q || item.label.toLowerCase().includes(q),
  }));
});

/** Group items like legacy facet.php (ungrouped first, then each group_name). */
const visibleSections = computed(() => {
  const items = filteredItems.value;
  const ungrouped = items.filter((i) => !i.groupName);
  const groupNames = [...new Set(items.map((i) => i.groupName).filter(Boolean))];

  const sections = [];
  if (ungrouped.length) {
    sections.push({ key: 'ungrouped', groupName: '', items: ungrouped });
  }
  for (const groupName of groupNames) {
    sections.push({
      key: groupName,
      groupName,
      items: items.filter((i) => i.groupName === groupName),
    });
  }
  return sections;
});

const hasVisibleItems = computed(() =>
  filteredItems.value.some((i) => i._visible)
);
</script>

<style scoped>
.facet-checkbox-list__search :deep(.v-field) {
  font-size: 0.8125rem;
  background: #fff;
}

.facet-checkbox-list__items {
  max-height: 280px;
  overflow-y: auto;
  padding-right: 2px;
}

.facet-group-title {
  font-size: 0.7rem;
  font-weight: 700;
  color: #545b62;
  margin: 10px 0 6px;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.facet-group-title:first-child {
  margin-top: 0;
}

.facet-checkbox-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 4px 2px;
  cursor: pointer;
  font-size: 0.8125rem;
  line-height: 1.35;
  color: rgba(0, 0, 0, 0.82);
}

.facet-checkbox-item--hidden {
  display: none;
}

.facet-checkbox-item:hover {
  color: #1565c0;
}

.facet-checkbox-item__input {
  margin-top: 3px;
  flex-shrink: 0;
  accent-color: #1976d2;
}

.facet-checkbox-item__count {
  color: rgba(0, 0, 0, 0.45);
  font-size: 0.75rem;
}

.facet-checkbox-list__items::-webkit-scrollbar {
  width: 6px;
}

.facet-checkbox-list__items::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 3px;
}
</style>
