<template>
  <component :is="activeTabComponent" />
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'CatalogIndicatorDataPage' });

const { config } = useAppConfig();

/** Matches PHP `indicator_main_view` (chart | table | observations → data API tab | structure). */
function resolveMainView(cfg) {
  const m = String(cfg?.indicatorMainView ?? '').trim().toLowerCase();
  if (m === 'observations') return 'dataApi';
  if (m === 'structure') return 'structure';
  if (m === 'table') return 'table';
  return 'chart';
}

const activeMainView = computed(() => resolveMainView(config.value || {}));

const ChartTab = defineAsyncComponent(() => import('../tabs/IndicatorChartTab.vue'));
const TableTab = defineAsyncComponent(() => import('../tabs/IndicatorTableTab.vue'));
const DataApiTab = defineAsyncComponent(() => import('../tabs/IndicatorDataApiTab.vue'));
const StructureTab = defineAsyncComponent(() => import('../tabs/IndicatorStructureTab.vue'));

const activeTabComponent = computed(() => {
  const v = activeMainView.value;
  if (v === 'dataApi') return DataApiTab;
  if (v === 'structure') return StructureTab;
  if (v === 'table') return TableTab;
  return ChartTab;
});
</script>
