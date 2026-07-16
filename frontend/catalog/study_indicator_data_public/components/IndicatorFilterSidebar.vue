<template>
  <div class="filter-panel filter-panel--dense flex-grow-1" :class="{ 'filter-panel--flush': flush }">
    <div v-if="showTitle" class="filter-panel__title">{{ title }}</div>
    <div class="filter-panel__body" :class="cardTextClass">
      <v-expansion-panels v-model="expandedPanelsModel" multiple flat class="filter-expansion-panels">
        <v-expansion-panel value="time">
          <v-expansion-panel-title class="filter-expansion-panel-title">
            <slot name="time-title">{{ timePanelTitle }}</slot>
          </v-expansion-panel-title>
          <v-expansion-panel-text class="filter-expansion-panel-text">
            <slot name="time-filter">
              <div class="filter-expansion-time-body">
                <v-text-field
                  v-model="filterDraft.from"
                  label="From (year)"
                  density="compact"
                  variant="solo-filled"
                  flat
                  hide-details
                  class="mb-2 rounded-lg"
                />
                <v-text-field
                  v-model="filterDraft.to"
                  label="To (year)"
                  density="compact"
                  variant="solo-filled"
                  flat
                  hide-details
                  class="rounded-lg"
                />
              </div>
            </slot>
          </v-expansion-panel-text>
        </v-expansion-panel>

        <v-expansion-panel v-if="geographyFilterVisible && geographyComponent" value="geo">
          <v-expansion-panel-title class="filter-expansion-panel-title">
            {{ labelFor(geographyComponent) }}
          </v-expansion-panel-title>
          <v-expansion-panel-text class="filter-expansion-panel-text">
            <IndicatorFacetCheckboxPanel
              :component-name="geographyComponent.name"
              :selection="filterDraft.dGeography"
              :items="codelistSelectItems[geographyComponent.name] || []"
              :allow-select-all="false"
              :search-text="facetListSearch[geographyComponent.name] || ''"
              clear-aria-label="Clear geography selection"
              @clear="clearFacetGeography"
              @update:search-text="(v) => setFacetSearch(geographyComponent.name, v)"
            />
          </v-expansion-panel-text>
        </v-expansion-panel>

        <v-expansion-panel v-if="periodicityFilterVisible && periodicityComponent" value="period">
          <v-expansion-panel-title class="filter-expansion-panel-title">
            {{ labelFor(periodicityComponent) }}
          </v-expansion-panel-title>
          <v-expansion-panel-text class="filter-expansion-panel-text">
            <div
              v-if="filterDraft.dPeriodicity.length > 0"
              class="facet-filter-subheader d-flex align-center flex-wrap gap-1"
            >
              <span class="facet-filter-subheader__count facet-filter-subheader__suffix">
                {{
                  (codelistSelectItems[periodicityComponent.name] ?? []).find(
                    (o) => o.value === filterDraft.dPeriodicity[0]
                  )?.title ?? filterDraft.dPeriodicity[0]
                }}
              </span>
              <v-spacer />
              <v-btn
                variant="text"
                density="compact"
                size="x-small"
                class="text-none facet-filter-subheader__clear"
                prepend-icon="mdi-close"
                aria-label="Clear periodicity selection"
                @click="clearFacetPeriodicity"
              >
                Clear
              </v-btn>
            </div>
            <div v-if="codelistSelectItems[periodicityComponent.name]?.length" class="px-2 py-1">
              <v-radio-group
                :model-value="filterDraft.dPeriodicity[0] ?? ''"
                density="compact"
                hide-details
                class="freq-radio-group"
                @update:model-value="onPeriodicityRadioChange"
              >
                <v-radio
                  v-for="opt in codelistSelectItems[periodicityComponent.name]"
                  :key="opt.value"
                  :label="opt.title"
                  :value="opt.value"
                  density="compact"
                  class="freq-radio-item"
                />
              </v-radio-group>
            </div>
            <div v-else class="px-2 pb-2">
              <v-text-field
                :model-value="filterDraft.dPeriodicity[0] ?? ''"
                density="compact"
                variant="solo-filled"
                flat
                hide-details
                placeholder="Frequency code"
                class="rounded-lg"
                @update:model-value="onPeriodicityRadioChange"
              />
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>

        <v-expansion-panel
          v-for="col in facetComponentsForCVisible"
          :key="'ep-dim-' + col.name"
          :value="'dim:' + col.name"
        >
          <v-expansion-panel-title class="filter-expansion-panel-title">
            {{ labelFor(col) }}
          </v-expansion-panel-title>
          <v-expansion-panel-text class="filter-expansion-panel-text">
            <IndicatorFacetCheckboxPanel
              :component-name="col.name"
              :selection="filterDraft.c[col.name]"
              :items="codelistSelectItems[col.name] || []"
              :allow-select-all="true"
              :search-text="facetListSearch[col.name] || ''"
              :clear-aria-label="'Clear ' + labelFor(col)"
              @clear="clearFacetDimension(col.name)"
              @update:search-text="(v) => setFacetSearch(col.name, v)"
            />
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <div class="filter-sidebar-apply pt-2 pb-2">
        <v-btn
          :variant="applyVariant"
          :color="applyColor"
          size="small"
          rounded="xl"
          block
          class="text-none filter-apply-outlined"
          :loading="applyLoading"
          :disabled="applyDisabled"
          prepend-icon="mdi-check"
          @click="$emit('apply')"
        >
          {{ applyLabel }}
        </v-btn>
      </div>

      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { facetLabel } from '@/shared/timeseries/indicatorSchemaUtils.js';
import { useIndicatorFacetFilters } from '../composables/useIndicatorFacetFilters.js';
import IndicatorFacetCheckboxPanel from './IndicatorFacetCheckboxPanel.vue';

defineOptions({ name: 'IndicatorFilterSidebar' });

const props = defineProps({
  filterDraft: {
    type: Object,
    required: true,
  },
  codelistSelectItems: {
    type: Object,
    default: () => ({}),
  },
  expandedPanels: {
    type: Array,
    default: () => [],
  },
  geographyComponent: {
    type: Object,
    default: null,
  },
  periodicityComponent: {
    type: Object,
    default: null,
  },
  facetComponentsForCVisible: {
    type: Array,
    default: () => [],
  },
  geographyFilterVisible: {
    type: Boolean,
    default: false,
  },
  periodicityFilterVisible: {
    type: Boolean,
    default: false,
  },
  applyLoading: {
    type: Boolean,
    default: false,
  },
  applyDisabled: {
    type: Boolean,
    default: false,
  },
  applyLabel: {
    type: String,
    default: 'Apply',
  },
  applyVariant: {
    type: String,
    default: 'outlined',
  },
  applyColor: {
    type: String,
    default: undefined,
  },
  timePanelTitle: {
    type: String,
    default: 'Time period',
  },
  title: {
    type: String,
    default: '',
  },
  showTitle: {
    type: Boolean,
    default: false,
  },
  rounded: {
    type: [Boolean, String],
    default: '0',
  },
  border: {
    type: [Boolean, String],
    default: false,
  },
  flush: {
    type: Boolean,
    default: false,
  },
  cardTextClass: {
    type: String,
    default: 'pa-0',
  },
  dimensionLabelFn: {
    type: Function,
    default: null,
  },
});

const emit = defineEmits(['apply', 'update:expandedPanels']);

const {
  facetListSearch,
  clearFacetGeography,
  clearFacetPeriodicity,
  onPeriodicityRadioChange,
  clearFacetDimension,
} = useIndicatorFacetFilters(props.filterDraft);

const expandedPanelsModel = computed({
  get: () => props.expandedPanels,
  set: (val) => emit('update:expandedPanels', val),
});

function labelFor(component) {
  if (props.dimensionLabelFn) return props.dimensionLabelFn(component);
  return facetLabel(component);
}

function setFacetSearch(name, value) {
  facetListSearch[name] = value ?? '';
}
</script>

<style scoped>
.filter-panel__body {
  padding: 0;
}

.filter-panel--flush {
  margin: 0;
}

.filter-panel__title {
  font-size: 1rem;
  font-weight: 600;
  letter-spacing: -0.01em;
  padding: 1rem 1.25rem 0;
}

.filter-panel--dense.filter-panel .filter-panel__title {
  font-size: 0.9375rem;
  padding: 0.75rem 0.75rem 0;
}

.filter-sidebar-apply .filter-apply-outlined.v-btn--variant-outlined {
  border-color: rgba(var(--v-theme-on-surface), 0.12) !important;
  border-radius: 14px !important;
}

.filter-sidebar-apply .filter-apply-outlined.v-btn--variant-outlined:not(.v-btn--disabled):hover {
  border-color: rgba(var(--v-theme-on-surface), 0.22) !important;
}

.filter-expansion-panels {
  gap: 0.35rem;
  display: flex;
  flex-direction: column;
}

.filter-expansion-panels :deep(.v-expansion-panel) {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 4px;
  overflow: hidden;
}

.filter-expansion-panel-title {
  min-height: 40px !important;
  padding: 0.5rem 0.65rem !important;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.01em;
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
.facet-filter-subheader__clear {
  font-weight: 400;
  font-size: inherit;
  color: inherit;
}

.facet-filter-subheader__suffix {
  margin-inline-start: 0.2rem;
}

.facet-filter-subheader__clear {
  letter-spacing: normal;
}

.facet-filter-subheader__clear :deep(.v-icon) {
  opacity: 0.65;
}

.filter-expansion-panel-text {
  padding: 0 !important;
}

.filter-expansion-panels :deep(.v-expansion-panel-text__wrapper) {
  padding: 0 !important;
}

.filter-expansion-time-body {
  padding: 0.5rem 0.65rem 0.75rem;
}

.freq-radio-group :deep(.v-selection-control-group) {
  gap: 0;
}

.freq-radio-item :deep(.v-label) {
  font-size: 0.75rem;
}

.freq-radio-item :deep(.v-selection-control) {
  min-height: 32px;
}
</style>
