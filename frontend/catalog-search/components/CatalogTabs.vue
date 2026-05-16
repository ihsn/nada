<template>
  <div v-if="typeList.length" class="catalog-tabs-wrap">
    <v-tabs
      :model-value="modelValue"
      density="comfortable"
      color="primary"
      class="catalog-tabs flex-grow-1"
      @update:model-value="onTab"
    >
      <v-tab value="">
        {{ t('all') }}
        <span v-if="grandTotal !== null" class="tab-count">{{ grandTotal.toLocaleString() }}</span>
      </v-tab>
      <v-tab
        v-for="type in primaryTypes"
        :key="type.code"
        :value="type.code"
      >
        {{ type.label }}
        <span v-if="type.found != null" class="tab-count">
          {{ Number(type.found).toLocaleString() }}
        </span>
      </v-tab>
    </v-tabs>

    <v-menu
      v-if="overflowTypes.length"
      location="bottom start"
      transition="fade-transition"
    >
      <template #activator="{ props: menuProps }">
        <button
          v-bind="menuProps"
          type="button"
          class="catalog-more-trigger"
          :class="{ 'catalog-more-trigger--active': activeInOverflow }"
        >
          <template v-if="activeInOverflow && activeOverflowType">
            {{ activeOverflowType.label }}
            <span
              v-if="activeOverflowType.found != null"
              class="tab-count"
            >
              {{ Number(activeOverflowType.found).toLocaleString() }}
            </span>
          </template>
          <template v-else>
            {{ t('tab_more_types', 'More') }}
          </template>
        </button>
      </template>
      <v-list density="compact" class="catalog-more-list pa-0">
        <v-list-item
          v-for="type in overflowTypes"
          :key="type.code"
          :active="modelValue === type.code"
          class="catalog-more-item"
          @click="onTab(type.code)"
        >
          <v-list-item-title class="d-flex align-center justify-space-between ga-2">
            <span>{{ type.label }}</span>
            <span
              v-if="type.found != null"
              class="catalog-more-count text-medium-emphasis"
            >
              {{ Number(type.found).toLocaleString() }}
            </span>
          </v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogTabs' });

/** Match PHP search_data_tabs.php folding threshold. */
const MAX_PRIMARY_TYPES = 6;

const props = defineProps({
  modelValue: { type: String, default: '' },
  tabs: { type: Object, default: null },
});
const emit = defineEmits(['update:modelValue', 'change']);

const { t } = useI18n();

const typeList = computed(() => {
  if (!props.tabs?.types) return [];
  return Object.entries(props.tabs.types).map(([code, item]) => ({
    code,
    label: (typeof item === 'object' ? item.title : item) || code,
    found: typeof item === 'object' ? item.found : null,
  }));
});

const primaryTypes = computed(() => {
  if (typeList.value.length <= MAX_PRIMARY_TYPES) return typeList.value;
  return typeList.value.slice(0, MAX_PRIMARY_TYPES);
});

const overflowTypes = computed(() => {
  if (typeList.value.length <= MAX_PRIMARY_TYPES) return [];
  return typeList.value.slice(MAX_PRIMARY_TYPES);
});

const activeInOverflow = computed(() =>
  overflowTypes.value.some((x) => x.code === props.modelValue)
);

const activeOverflowType = computed(() =>
  overflowTypes.value.find((x) => x.code === props.modelValue) ?? null
);

const grandTotal = computed(() => {
  const types = props.tabs?.types;
  if (!types) return null;
  return Object.values(types).reduce((sum, item) => {
    const n = typeof item === 'object' ? Number(item.found) : 0;
    return sum + (isNaN(n) ? 0 : n);
  }, 0);
});

function onTab(value) {
  emit('update:modelValue', value ?? '');
  emit('change');
}
</script>

<style scoped>
.catalog-tabs-wrap {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 0;
  width: 100%;
}

.catalog-tabs :deep(.v-tab) {
  font-size: 0.8125rem;
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0.01em;
  min-width: 72px;
}

.tab-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(25, 118, 210, 0.1);
  color: #1976d2;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 1px 7px;
  margin-left: 6px;
  line-height: 18px;
}

.v-tab--selected .tab-count {
  background: rgba(25, 118, 210, 0.18);
}

.catalog-more-trigger {
  align-self: flex-end;
  display: inline-flex;
  align-items: center;
  min-height: 48px;
  padding: 0 16px;
  margin-left: 4px;
  border: none;
  border-bottom: 2px solid transparent;
  background: transparent;
  font-size: 0.8125rem;
  font-weight: 500;
  font-family: inherit;
  color: rgba(0, 0, 0, 0.6);
  cursor: pointer;
  border-radius: 0;
}

.catalog-more-trigger:hover {
  color: #1976d2;
}

.catalog-more-trigger--active {
  color: #1976d2;
  font-weight: 600;
  border-bottom-color: #1976d2;
}

.catalog-more-list {
  min-width: 220px;
  max-height: 70vh;
  overflow-y: auto;
}

.catalog-more-count {
  font-size: 0.75rem;
}
</style>
