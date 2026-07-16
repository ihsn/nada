<template>
  <div>
    <div
      v-if="timeFacetShowSummary"
      class="facet-filter-subheader d-flex align-center flex-wrap gap-1"
    >
      <span class="facet-filter-subheader__count">{{ timeFacetSummaryCount }}</span>
      <span class="facet-filter-subheader__suffix">
        {{ subPeriodMode ? 'periods set' : canUseYearSlider ? 'years in range' : 'fields set' }}
      </span>
      <v-spacer />
      <v-btn
        variant="text"
        density="compact"
        size="x-small"
        class="text-none facet-filter-subheader__clear"
        prepend-icon="mdi-close"
        aria-label="Clear time filter"
        @click="clearFacetTime"
      >
        Clear
      </v-btn>
    </div>
    <div class="filter-expansion-time-body">
      <template v-if="subPeriodMode">
        <div class="sub-period-row mb-2">
          <span class="sub-period-label">From</span>
          <v-select
            v-model="subPeriodDraft.fromYear"
            :items="subPeriodYearOptions"
            density="compact"
            variant="solo-filled"
            flat
            hide-details
            class="sub-period-select sub-period-select--year"
          />
          <v-select
            v-model="subPeriodDraft.fromSub"
            :items="subPeriodMode === 'quarterly' ? quarterOptions : monthOptions"
            density="compact"
            variant="solo-filled"
            flat
            hide-details
            class="sub-period-select sub-period-select--sub"
          />
        </div>
        <div class="sub-period-row">
          <span class="sub-period-label">To</span>
          <v-select
            v-model="subPeriodDraft.toYear"
            :items="subPeriodYearOptions"
            density="compact"
            variant="solo-filled"
            flat
            hide-details
            class="sub-period-select sub-period-select--year"
          />
          <v-select
            v-model="subPeriodDraft.toSub"
            :items="subPeriodMode === 'quarterly' ? quarterOptions : monthOptions"
            density="compact"
            variant="solo-filled"
            flat
            hide-details
            class="sub-period-select sub-period-select--sub"
          />
        </div>
      </template>
      <template v-else-if="canUseYearSlider">
        <v-chip variant="tonal" color="primary" size="small" class="year-chip mb-2 font-weight-medium">
          {{ yearSliderLocal[0] }} – {{ yearSliderLocal[1] }}
        </v-chip>
        <v-range-slider
          :model-value="yearSliderLocal"
          :min="timeBoundsYears.min"
          :max="timeBoundsYears.max"
          :step="1"
          color="primary"
          density="compact"
          track-size="2"
          hide-details
          @update:model-value="onYearSliderInput"
        />
      </template>
      <template v-else>
        <v-text-field
          v-model="filterDraft.from"
          label="From"
          density="compact"
          variant="solo-filled"
          flat
          hide-details
          class="mb-2 rounded-lg"
        />
        <v-text-field
          v-model="filterDraft.to"
          label="To"
          density="compact"
          variant="solo-filled"
          flat
          hide-details
          class="rounded-lg"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { toRef } from 'vue';
import { useIndicatorTimeFilter } from '../composables/useIndicatorTimeFilter.js';

defineOptions({ name: 'IndicatorTimeFilterPanel' });

const props = defineProps({
  filterDraft: {
    type: Object,
    required: true,
  },
  schema: {
    type: Object,
    default: null,
  },
  timeBounds: {
    type: Object,
    default: null,
  },
});

const {
  yearSliderLocal,
  subPeriodDraft,
  timeBoundsYears,
  canUseYearSlider,
  subPeriodMode,
  subPeriodYearOptions,
  quarterOptions,
  monthOptions,
  timeFacetShowSummary,
  timeFacetSummaryCount,
  onYearSliderInput,
  clearFacetTime,
  syncTimeFilterUi,
} = useIndicatorTimeFilter({
  filterDraft: props.filterDraft,
  schema: toRef(props, 'schema'),
  timeBounds: toRef(props, 'timeBounds'),
});

defineExpose({ syncTimeFilterUi });
</script>

<style scoped>
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

.filter-expansion-time-body {
  padding: 0.5rem 0.65rem 0.75rem;
}

.year-chip {
  border-radius: 999px !important;
}

.sub-period-row {
  display: flex;
  align-items: center;
  gap: 4px;
}

.sub-period-label {
  flex: 0 0 2rem;
  font-size: 0.6875rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.65);
}

.sub-period-select--year {
  flex: 1 1 4.5rem;
  min-width: 0;
}

.sub-period-select--sub {
  flex: 1 1 3.5rem;
  min-width: 0;
}
</style>
