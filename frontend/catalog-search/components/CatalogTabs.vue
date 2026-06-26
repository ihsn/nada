<template>
  <div v-if="typeList.length" ref="outerRef" class="catalog-tabs-outer">
    <nav
      class="catalog-tabs-wrap"
      :aria-label="t('filter_by_type')"
    >
      <button
        type="button"
        class="catalog-tab"
        :class="{ 'catalog-tab--active': modelValue === '' }"
        @click="onTab('')"
      >
        {{ t('any') }}
        <span v-if="grandTotal !== null" class="tab-count">{{ grandTotal.toLocaleString() }}</span>
      </button>

      <button
        v-for="type in visibleTypes"
        :key="type.code"
        type="button"
        class="catalog-tab"
        :class="{ 'catalog-tab--active': modelValue === type.code }"
        @click="onTab(type.code)"
      >
        {{ type.label }}
        <span v-if="type.found != null" class="tab-count">
          {{ Number(type.found).toLocaleString() }}
        </span>
      </button>

      <v-menu
        v-if="overflowTypes.length"
        location="bottom start"
        transition="fade-transition"
      >
        <template #activator="{ props: menuProps }">
          <button
            v-bind="menuProps"
            type="button"
            class="catalog-tab catalog-tab--more"
            :class="{ 'catalog-tab--active': activeInOverflow }"
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
              <v-icon size="16" class="catalog-tab__chevron">mdi-chevron-down</v-icon>
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
    </nav>

    <!-- Off-screen sizing for responsive fold into "More". -->
    <div class="catalog-tabs-measure" aria-hidden="true">
      <button ref="measureAllRef" type="button" class="catalog-tab" tabindex="-1">
        {{ t('any') }}
        <span v-if="grandTotal !== null" class="tab-count">{{ grandTotal.toLocaleString() }}</span>
      </button>
      <button
        v-for="(type, index) in typeList"
        :key="'measure-' + type.code"
        :ref="(el) => setMeasureTypeRef(el, index)"
        type="button"
        class="catalog-tab"
        tabindex="-1"
      >
        {{ type.label }}
        <span v-if="type.found != null" class="tab-count">
          {{ Number(type.found).toLocaleString() }}
        </span>
      </button>
      <button ref="measureMoreRef" type="button" class="catalog-tab catalog-tab--more" tabindex="-1">
        {{ t('tab_more_types', 'More') }}
        <v-icon size="16" class="catalog-tab__chevron">mdi-chevron-down</v-icon>
      </button>
      <button
        v-if="widestOverflowType"
        ref="measureActiveMoreRef"
        type="button"
        class="catalog-tab catalog-tab--more"
        tabindex="-1"
      >
        {{ widestOverflowType.label }}
        <span
          v-if="widestOverflowType.found != null"
          class="tab-count"
        >
          {{ Number(widestOverflowType.found).toLocaleString() }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { catalogDatasetTypeLabel } from '../catalogDatasetTypeLabel';

defineOptions({ name: 'CatalogTabs' });

const props = defineProps({
  modelValue: { type: String, default: '' },
  tabs: { type: Object, default: null },
});
const emit = defineEmits(['update:modelValue', 'change']);

const { t } = useI18n();

const outerRef = ref(null);
const measureAllRef = ref(null);
const measureMoreRef = ref(null);
const measureActiveMoreRef = ref(null);
const measureTypeRefs = ref([]);

/** How many type tabs fit beside "All" and optional "More". Infinity until first layout pass. */
const visibleTypeCount = ref(Infinity);

const typeList = computed(() => {
  if (!props.tabs?.types) return [];
  const counts = props.tabs?.search_counts_by_type ?? null;
  return Object.entries(props.tabs.types).map(([code, item]) => {
    const catalogTotal = typeof item === 'object' ? item.found : null;
    const searchCount = counts && Object.prototype.hasOwnProperty.call(counts, code)
      ? counts[code]
      : null;
    return {
      code,
      label: catalogDatasetTypeLabel(
        t,
        code,
        typeof item === 'object' ? item.title : item
      ),
      found: searchCount != null ? searchCount : catalogTotal,
    };
  });
});

const visibleTypes = computed(() => {
  const list = typeList.value;
  if (!list.length) return [];
  if (visibleTypeCount.value >= list.length) return list;
  return list.slice(0, Math.max(0, visibleTypeCount.value));
});

const overflowTypes = computed(() => {
  const list = typeList.value;
  if (visibleTypeCount.value >= list.length) return [];
  return list.slice(Math.max(0, visibleTypeCount.value));
});

const activeInOverflow = computed(() =>
  overflowTypes.value.some((x) => x.code === props.modelValue)
);

const activeOverflowType = computed(() =>
  overflowTypes.value.find((x) => x.code === props.modelValue) ?? null
);

const widestOverflowType = computed(() => {
  const list = typeList.value;
  if (!list.length) return null;
  return list.reduce((widest, type) => {
    if (!widest) return type;
    const labelLen = String(type.label ?? '').length;
    const widestLen = String(widest.label ?? '').length;
    return labelLen > widestLen ? type : widest;
  }, null);
});

const grandTotal = computed(() => {
  const counts = props.tabs?.search_counts_by_type;
  if (counts && typeof counts === 'object') {
    return Object.values(counts).reduce((sum, n) => sum + (Number(n) || 0), 0);
  }
  const types = props.tabs?.types;
  if (!types) return null;
  return Object.values(types).reduce((sum, item) => {
    const n = typeof item === 'object' ? Number(item.found) : 0;
    return sum + (isNaN(n) ? 0 : n);
  }, 0);
});

let resizeObserver = null;

function setMeasureTypeRef(el, index) {
  if (el) {
    measureTypeRefs.value[index] = el;
  }
}

function layoutTabs() {
  const outer = outerRef.value;
  const allEl = measureAllRef.value;
  const moreEl = measureMoreRef.value;
  if (!outer || !allEl || !moreEl) return;

  const containerWidth = outer.clientWidth;
  if (containerWidth <= 0) return;

  const allWidth = allEl.offsetWidth;
  const moreWidth = Math.max(
    moreEl.offsetWidth,
    measureActiveMoreRef.value?.offsetWidth ?? 0
  );

  const typeWidths = typeList.value.map((_, index) =>
    measureTypeRefs.value[index]?.offsetWidth ?? 0
  );
  const total = typeWidths.length;

  let visible = 0;
  let used = allWidth;

  for (let i = 0; i < total; i++) {
    const tabWidth = typeWidths[i];
    const hiddenAfter = total - (i + 1);
    const reserveMore = hiddenAfter > 0 ? moreWidth : 0;

    if (used + tabWidth + reserveMore <= containerWidth) {
      used += tabWidth;
      visible++;
    } else {
      break;
    }
  }

  visibleTypeCount.value = visible;
}

function scheduleLayout() {
  nextTick(() => layoutTabs());
}

onMounted(() => {
  scheduleLayout();
  resizeObserver = new ResizeObserver(() => layoutTabs());
  if (outerRef.value) {
    resizeObserver.observe(outerRef.value);
  }
});

onUnmounted(() => {
  resizeObserver?.disconnect();
});

watch(typeList, () => {
  measureTypeRefs.value = [];
  scheduleLayout();
});

watch(grandTotal, scheduleLayout);

function onTab(value) {
  emit('update:modelValue', value ?? '');
  emit('change');
}
</script>

<style scoped>
.catalog-tabs-outer {
  position: relative;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  border-bottom: 1px solid var(--catalog-border, rgba(15, 23, 42, 0.16));
  background: var(--catalog-surface-subtle, #f3f5f8);
  border-radius: 8px 8px 0 0;
}

.catalog-tabs-wrap {
  display: flex;
  flex-wrap: nowrap;
  align-items: flex-end;
  width: 100%;
  overflow: hidden;
}

.catalog-tabs-measure {
  position: absolute;
  left: 0;
  top: 0;
  display: flex;
  flex-wrap: nowrap;
  align-items: flex-end;
  visibility: hidden;
  pointer-events: none;
  height: 0;
  overflow: hidden;
  white-space: nowrap;
}

.catalog-tab {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  min-height: 48px;
  padding: 0 16px;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  background: transparent;
  font-size: 0.8125rem;
  font-weight: 500;
  font-family: inherit;
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
  cursor: pointer;
  border-radius: 0;
  text-transform: none;
  letter-spacing: 0.01em;
  white-space: nowrap;
}

.catalog-tab:hover {
  color: #1976d2;
}

.catalog-tab--active {
  color: #1976d2;
  font-weight: 600;
  border-bottom-color: #1976d2;
}

.catalog-tab--more .catalog-tab__chevron {
  margin-left: 4px;
  opacity: 0.7;
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

.catalog-tab--active .tab-count {
  background: rgba(25, 118, 210, 0.18);
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
