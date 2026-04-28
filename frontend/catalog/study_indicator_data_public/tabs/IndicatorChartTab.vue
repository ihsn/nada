<template>
  <div class="catalog-indicator-page">
    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-6 rounded-s" height="3" />

    <v-alert v-if="fatalError" type="error" variant="tonal" class="mb-6" rounded="lg" prominent density="comfortable">
      {{ fatalError }}
    </v-alert>

    <template v-else-if="schema">
      <v-row dense class="main-layout">
        <v-col cols="12" class="d-flex flex-column">
          <v-card class="main-panel flex-grow-1 d-flex flex-column" rounded="0" flat>
            <v-card-text class="pa-0 flex-grow-1">
                  <v-row dense class="chart-tab-layout align-start">
                    <v-col cols="12" lg="3" class="d-flex mb-4 mb-lg-0 pr-lg-3">
                      <v-card class="filter-panel filter-panel--dense flex-grow-1" rounded="0" flat>
                        <v-card-text class="pa-0">
                          <v-expansion-panels
                            v-model="chartFilterExpandedPanels"
                            multiple
                            flat
                            class="filter-expansion-panels"
                          >
                            <v-expansion-panel v-if="geographyFilterVisible" value="geo">
                              <v-expansion-panel-title class="filter-expansion-panel-title">
                                {{ facetLabel(geographyComponent) }}
                              </v-expansion-panel-title>
                              <v-expansion-panel-text class="filter-expansion-panel-text">
                                <div
                                  v-if="filterDraft.dGeography.length > 0"
                                  class="facet-filter-subheader d-flex align-center flex-wrap gap-1"
                                >
                                  <span class="facet-filter-subheader__count">{{ filterDraft.dGeography.length }}</span>
                                  <span class="facet-filter-subheader__suffix">selected</span>
                                  <v-spacer />
                                  <v-btn
                                    variant="text"
                                    density="compact"
                                    size="x-small"
                                    class="text-none facet-filter-subheader__clear"
                                    prepend-icon="mdi-close"
                                    aria-label="Clear geography selection"
                                    @click="clearFacetGeography"
                                  >
                                    Clear
                                  </v-btn>
                                </div>
                                <div v-if="codelistSelectItems[geographyComponent.name]?.length" class="facet-checkbox-panel">
                                  <div
                                    v-if="facetListNeedsSearch(codelistSelectItems[geographyComponent.name])"
                                    class="facet-checkbox-search-wrap"
                                  >
                                    <v-text-field
                                      v-model="facetListSearch[geographyComponent.name]"
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
                                        v-for="opt in facetListItemsFiltered(
                                          geographyComponent.name,
                                          codelistSelectItems[geographyComponent.name]
                                        )"
                                        :key="geographyComponent.name + '-' + opt.value"
                                        class="facet-checkbox-row py-0"
                                        @click="facetToggleCode(filterDraft.dGeography, opt.value)"
                                      >
                                        <template #prepend>
                                          <v-checkbox
                                            :model-value="facetCodesInclude(filterDraft.dGeography, opt.value)"
                                            hide-details
                                            density="compact"
                                            class="facet-checkbox-control"
                                            @click.stop
                                            @update:model-value="(on) => facetSetCodeSelected(filterDraft.dGeography, opt.value, on)"
                                          />
                                        </template>
                                        <v-list-item-title class="facet-checkbox-item-title text-wrap">
                                          {{ opt.title }}
                                        </v-list-item-title>
                                      </v-list-item>
                                    </v-list>
                                    <p
                                      v-if="
                                        !facetListItemsFiltered(
                                          geographyComponent.name,
                                          codelistSelectItems[geographyComponent.name]
                                        ).length
                                      "
                                      class="facet-checkbox-empty text-medium-emphasis mb-0"
                                    >
                                      No matching codes.
                                    </p>
                                  </div>
                                </div>
                                <div v-else class="px-2 pb-2">
                                  <v-combobox
                                    v-model="filterDraft.dGeography"
                                    multiple
                                    chips
                                    closable-chips
                                    density="compact"
                                    variant="solo-filled"
                                    flat
                                    hide-details
                                    placeholder="Codes (comma-separated chips)"
                                    class="rounded-lg"
                                  />
                                </div>
                              </v-expansion-panel-text>
                            </v-expansion-panel>

                            <v-expansion-panel value="time">
                              <v-expansion-panel-title class="filter-expansion-panel-title">
                                {{ timePeriodFilterSectionLabel }}
                              </v-expansion-panel-title>
                              <v-expansion-panel-text class="filter-expansion-panel-text">
                                <div
                                  v-if="timeFacetShowSummary"
                                  class="facet-filter-subheader d-flex align-center flex-wrap gap-1"
                                >
                                  <span class="facet-filter-subheader__count">{{ timeFacetSummaryCount }}</span>
                                  <span class="facet-filter-subheader__suffix">
                                    {{ canUseYearSlider ? 'years in range' : 'fields set' }}
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
                                  <template v-if="canUseYearSlider">
                                    <v-chip variant="tonal" color="primary" size="small" class="year-chip mb-2 font-weight-medium">
                                      {{ yearSliderLocal[0] }} – {{ yearSliderLocal[1] }}
                                    </v-chip>
                                    <v-range-slider
                                      :model-value="yearSliderLocal"
                                      :min="chartTimeBoundsYears.min"
                                      :max="chartTimeBoundsYears.max"
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
                              </v-expansion-panel-text>
                            </v-expansion-panel>

                            <v-expansion-panel v-if="periodicityFilterVisible" value="period">
                              <v-expansion-panel-title class="filter-expansion-panel-title">
                                {{ facetLabel(periodicityComponent) }}
                              </v-expansion-panel-title>
                              <v-expansion-panel-text class="filter-expansion-panel-text">
                                <div
                                  v-if="filterDraft.dPeriodicity.length > 0"
                                  class="facet-filter-subheader d-flex align-center flex-wrap gap-1"
                                >
                                  <span class="facet-filter-subheader__count">{{ filterDraft.dPeriodicity.length }}</span>
                                  <span class="facet-filter-subheader__suffix">selected</span>
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
                                <div v-if="codelistSelectItems[periodicityComponent.name]?.length" class="facet-checkbox-panel">
                                  <div
                                    v-if="facetListNeedsSearch(codelistSelectItems[periodicityComponent.name])"
                                    class="facet-checkbox-search-wrap"
                                  >
                                    <v-text-field
                                      v-model="facetListSearch[periodicityComponent.name]"
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
                                        v-for="opt in facetListItemsFiltered(
                                          periodicityComponent.name,
                                          codelistSelectItems[periodicityComponent.name]
                                        )"
                                        :key="periodicityComponent.name + '-' + opt.value"
                                        class="facet-checkbox-row py-0"
                                        @click="facetToggleCode(filterDraft.dPeriodicity, opt.value)"
                                      >
                                        <template #prepend>
                                          <v-checkbox
                                            :model-value="facetCodesInclude(filterDraft.dPeriodicity, opt.value)"
                                            hide-details
                                            density="compact"
                                            class="facet-checkbox-control"
                                            @click.stop
                                            @update:model-value="(on) => facetSetCodeSelected(filterDraft.dPeriodicity, opt.value, on)"
                                          />
                                        </template>
                                        <v-list-item-title class="facet-checkbox-item-title text-wrap">
                                          {{ opt.title }}
                                        </v-list-item-title>
                                      </v-list-item>
                                    </v-list>
                                    <p
                                      v-if="
                                        !facetListItemsFiltered(
                                          periodicityComponent.name,
                                          codelistSelectItems[periodicityComponent.name]
                                        ).length
                                      "
                                      class="facet-checkbox-empty text-medium-emphasis mb-0"
                                    >
                                      No matching codes.
                                    </p>
                                  </div>
                                </div>
                                <div v-else class="px-2 pb-2">
                                  <v-combobox
                                    v-model="filterDraft.dPeriodicity"
                                    multiple
                                    chips
                                    closable-chips
                                    density="compact"
                                    variant="solo-filled"
                                    flat
                                    hide-details
                                    class="rounded-lg"
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
                                {{ facetLabel(col) }}
                              </v-expansion-panel-title>
                              <v-expansion-panel-text class="filter-expansion-panel-text">
                                <div
                                  v-if="filterDraft.c[col.name]?.length > 0"
                                  class="facet-filter-subheader d-flex align-center flex-wrap gap-1"
                                >
                                  <span class="facet-filter-subheader__count">{{ filterDraft.c[col.name].length }}</span>
                                  <span class="facet-filter-subheader__suffix">selected</span>
                                  <v-spacer />
                                  <v-btn
                                    variant="text"
                                    density="compact"
                                    size="x-small"
                                    class="text-none facet-filter-subheader__clear"
                                    prepend-icon="mdi-close"
                                    :aria-label="'Clear ' + facetLabel(col)"
                                    @click="clearFacetDimension(col.name)"
                                  >
                                    Clear
                                  </v-btn>
                                </div>
                                <div v-if="codelistSelectItems[col.name]?.length" class="facet-checkbox-panel">
                                  <div
                                    v-if="facetListNeedsSearch(codelistSelectItems[col.name])"
                                    class="facet-checkbox-search-wrap"
                                  >
                                    <v-text-field
                                      v-model="facetListSearch[col.name]"
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
                                        v-for="opt in facetListItemsFiltered(col.name, codelistSelectItems[col.name])"
                                        :key="col.name + '-' + opt.value"
                                        class="facet-checkbox-row py-0"
                                        @click="facetToggleCode(filterDraft.c[col.name], opt.value)"
                                      >
                                        <template #prepend>
                                          <v-checkbox
                                            :model-value="facetCodesInclude(filterDraft.c[col.name], opt.value)"
                                            hide-details
                                            density="compact"
                                            class="facet-checkbox-control"
                                            @click.stop
                                            @update:model-value="(on) => facetSetCodeSelected(filterDraft.c[col.name], opt.value, on)"
                                          />
                                        </template>
                                        <v-list-item-title class="facet-checkbox-item-title text-wrap">
                                          {{ opt.title }}
                                        </v-list-item-title>
                                      </v-list-item>
                                    </v-list>
                                    <p
                                      v-if="!facetListItemsFiltered(col.name, codelistSelectItems[col.name]).length"
                                      class="facet-checkbox-empty text-medium-emphasis mb-0"
                                    >
                                      No matching codes.
                                    </p>
                                  </div>
                                </div>
                                <div v-else class="px-2 pb-2">
                                  <v-combobox
                                    v-model="filterDraft.c[col.name]"
                                    multiple
                                    chips
                                    closable-chips
                                    density="compact"
                                    variant="solo-filled"
                                    flat
                                    hide-details
                                    class="rounded-lg"
                                  />
                                </div>
                              </v-expansion-panel-text>
                            </v-expansion-panel>
                          </v-expansion-panels>

                          <div class="filter-sidebar-apply pt-2 pb-2">
                            <v-btn
                              color="primary"
                              variant="flat"
                              size="small"
                              rounded="lg"
                              block
                              class="text-none"
                              :loading="applyLoading"
                              :disabled="applyLoading || (catalogSliceRequired && !catalogSliceSelectionComplete)"
                              prepend-icon="mdi-check"
                              @click="onApplyFilters"
                            >
                              Apply
                            </v-btn>
                          </div>
                        </v-card-text>
                      </v-card>
                    </v-col>
                    <v-col cols="12" lg="9">
                  <template v-if="dataLoadCommitted || !catalogSliceRequired">
                  <div class="section-head d-flex flex-wrap align-center gap-3 mb-4">
                    <div class="section-title">Chart</div>
                    <v-spacer />
                    <v-btn-toggle
                      v-if="chartModeToggleVisible"
                      v-model="chartType"
                      mandatory
                      density="compact"
                      variant="outlined"
                      divided
                      rounded="md"
                      class="chart-type-toggle"
                    >
                      <v-btn value="line" size="x-small" class="text-none">Line</v-btn>
                      <v-btn value="column" size="x-small" class="text-none">Columns</v-btn>
                    </v-btn-toggle>
                    <v-menu
                      v-if="dataLoadCommitted"
                      location="bottom end"
                      transition="scale-transition"
                    >
                      <template #activator="{ props: chartMenuProps }">
                        <v-btn
                          v-bind="chartMenuProps"
                          icon
                          variant="text"
                          size="small"
                          density="comfortable"
                          class="chart-toolbar-settings"
                          :disabled="chartLoading"
                          title="Chart export"
                          aria-label="Chart export"
                        >
                          <v-icon class="chart-toolbar-settings__icon" size="18">mdi-cog-outline</v-icon>
                        </v-btn>
                      </template>
                      <v-list density="compact" class="chart-export-list">
                        <v-list-item
                          prepend-icon="mdi-file-image-outline"
                          title="Download chart as PNG"
                          :disabled="!chartExportPngAvailable"
                          @click="exportChartPng"
                        />
                      </v-list>
                    </v-menu>
                  </div>
                  <v-alert
                    v-if="dataLoadCommitted && chartScanTruncated"
                    type="warning"
                    variant="tonal"
                    density="compact"
                    rounded="lg"
                    class="mb-3 text-body-2"
                  >
                    Chart used the first {{ chartMetadata.source_rows_scanned }} observation rows (limit
                    {{ chartMetadata.raw_row_limit }}). Narrow filters if the series looks incomplete.
                  </v-alert>
                  <v-sheet
                    v-if="
                      dataLoadCommitted &&
                      ((chartModel?.useNumericTimeX && chartModel?.timeXKind !== 'year') ||
                        (chartModel?.labels?.length && !chartModel?.useNumericTimeX))
                    "
                    rounded="lg"
                    class="chart-hint pa-3 mb-3 text-body-2 text-medium-emphasis"
                  >
                    <template v-if="chartModel?.useNumericTimeX">
                      <span>Time axis uses a continuous date scale (missing periods appear as wider gaps).</span>
                    </template>
                    <span v-else>Time axis uses discrete categories (equal spacing between labels).</span>
                  </v-sheet>
                  <v-alert
                    v-if="dataLoadCommitted && seriesTruncated"
                    type="info"
                    variant="tonal"
                    density="compact"
                    rounded="lg"
                    class="mb-3 text-body-2"
                  >
                    Showing {{ chartModel?.datasets?.length ?? 0 }} series ({{ seriesDropped }} additional series hidden).
                  </v-alert>
                  <v-progress-linear
                    v-if="dataLoadCommitted && chartLoading"
                    indeterminate
                    color="primary"
                    class="mb-4 rounded-s"
                    height="3"
                  />

                  <div class="chart-canvas-wrap chart-shell" role="img" :aria-label="chartAriaLabel">
                    <canvas
                      v-show="!chartLoading && chartModel && chartModel.datasets.length"
                      ref="chartCanvasRef"
                      class="chart-canvas"
                    ></canvas>
                  </div>

                  <p
                    v-if="dataLoadCommitted && !chartLoading && filteredObservationCount > 0 && !chartModel?.datasets?.length"
                    class="text-body-2 text-medium-emphasis mb-4"
                  >
                    No numeric chart points returned. The data structure needs an <code>observation_value</code> component with
                    numeric values, and <code>reporting_year</code> (from <code>_ts_year</code>) or a time period / period start
                    field.
                  </p>
                  <p
                    v-else-if="dataLoadCommitted && !chartLoading && filteredObservationCount === 0"
                    class="text-body-2 text-medium-emphasis mb-4"
                  >
                    No observations match the current visualization filters.
                  </p>
                  </template>
                  <template v-else>
                    <div
                      class="chart-empty-state chart-shell d-flex flex-column align-center justify-center text-center pa-8"
                      role="status"
                      aria-live="polite"
                    >
                      <v-icon size="112" color="primary" class="chart-empty-state__icon mb-5">
                        mdi-chart-timeline-variant
                      </v-icon>
                      <div class="text-h5 font-weight-semibold mb-2">No chart yet</div>
                      <p class="text-body-1 text-medium-emphasis mb-0" style="max-width: 28rem">
                        Choose values in the filter panel on the left, then confirm to load the chart and tables.
                      </p>
                    </div>
                  </template>

                  <v-divider v-if="dataLoadCommitted" class="my-6 catalog-divider" />

                  <div v-if="dataLoadCommitted" class="section-head d-flex flex-wrap align-center gap-2 mb-3">
                    <div class="section-title">Chart data</div>
                    <v-spacer />
                    <v-chip size="small" variant="tonal" color="primary" class="font-weight-medium">{{ chartRecords.length }} rows</v-chip>
                    <v-menu location="bottom end" transition="scale-transition">
                      <template #activator="{ props: chartDataMenuProps }">
                        <v-btn
                          v-bind="chartDataMenuProps"
                          icon
                          variant="text"
                          size="small"
                          density="comfortable"
                          class="chart-toolbar-settings"
                          :disabled="chartLoading || chartRecords.length === 0"
                          title="Chart data export"
                          aria-label="Chart data export"
                        >
                          <v-icon class="chart-toolbar-settings__icon" size="18">mdi-cog-outline</v-icon>
                        </v-btn>
                      </template>
                      <v-list density="compact" class="chart-export-list">
                        <v-list-item
                          prepend-icon="mdi-code-json"
                          title="JSON"
                          :disabled="!chartExportDataAvailable"
                          @click="exportChartDataJson"
                        />
                        <v-list-item
                          prepend-icon="mdi-file-delimited-outline"
                          title="CSV"
                          :disabled="!chartExportDataAvailable"
                          @click="exportChartDataCsv"
                        />
                      </v-list>
                    </v-menu>
                  </div>
                  <v-sheet v-if="dataLoadCommitted" rounded="lg" border class="data-shell overflow-hidden">
                    <v-data-table
                      :headers="chartDataTableHeaders"
                      :items="chartRecordsDisplay"
                      :items-per-page="CHART_TABLE_ROWS_PER_PAGE"
                      :loading="chartLoading"
                      density="comfortable"
                      class="elevation-0 chart-data-table"
                      fixed-header
                      hover
                    />
                  </v-sheet>
                    </v-col>
                  </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script setup>
import { ref, shallowRef, reactive, computed, inject, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import debounce from 'lodash/debounce';
import Chart from 'chart.js/auto';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { usePublicTimeseriesApi } from '../composables/usePublicTimeseriesApi';

defineOptions({ name: 'IndicatorChartTab' });

const setMessage = inject('setMessage', () => {});

const { config } = useAppConfig();
const studyIdno = computed(() => String(config.value?.studyIdno ?? '').trim());
const studySid = computed(() => config.value?.studySid ?? '');
const { fetchSchema, fetchFilterOptions, fetchObservationCount, fetchChartData } = usePublicTimeseriesApi(studyIdno);

const MAX_SERIES = 16;
const CHART_API_LIMIT = 5000;
const CHART_TABLE_ROWS_PER_PAGE = 100;

/** Chart.js canvas text + chart data table body (see `v-bind` in scoped style). */
const CHART_DATA_FONT_FAMILY =
  "ui-monospace, 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', monospace";

const SERIES_COLORS = [
  '#1976D2',
  '#4CAF50',
  '#FF9800',
  '#9C27B0',
  '#F44336',
  '#00BCD4',
  '#FFC107',
  '#795548',
  '#3F51B5',
  '#009688',
  '#E91E63',
  '#673AB7',
  '#8BC34A',
  '#FF5722',
  '#607D8B',
  '#CDDC39',
];

const pageLoading = ref(true);
const applyLoading = ref(false);
const chartLoading = ref(false);
const fatalError = ref('');
const schema = ref(null);
/** Rows matching current visualization filters; used for chart load / empty states. */
const filteredObservationCount = ref(0);
/** Chart-ready rows from GET …/data/{idno}/chart (server aggregation). */
const chartRecords = ref([]);
const chartMetadata = ref({});
/** [lowYear, highYear] for v-range-slider; independent until the user moves the slider or edits From/To. */
const yearSliderLocal = ref([2000, 2020]);
const chartType = ref('line');

/** When true, skip syncing chart filter state to the URL (initial load / applying query from URL). */
const urlSyncSuspended = ref(true);

/** API filter payload last applied (from / to / d / c) */
const activeFilters = ref(null);

const filterDraft = reactive({
  from: '',
  to: '',
  dGeography: [],
  dPeriodicity: [],
  c: {},
});

const codelistSelectItems = ref({});

/** Client-side filter query per facet column (component `name`). */
const facetListSearch = reactive({});

const FACET_LIST_SEARCH_THRESHOLD = 20;

function facetListNeedsSearch(items) {
  return Array.isArray(items) && items.length > FACET_LIST_SEARCH_THRESHOLD;
}

function facetListItemsFiltered(componentName, items) {
  const list = Array.isArray(items) ? items : [];
  const q = String(facetListSearch[componentName] ?? '').trim().toLowerCase();
  if (!q) return list;
  return list.filter((opt) => {
    const t = String(opt?.title ?? '').toLowerCase();
    const v = String(opt?.value ?? '').toLowerCase();
    return t.includes(q) || v.includes(q);
  });
}

function facetCodesInclude(arr, code) {
  if (!Array.isArray(arr)) return false;
  const s = String(code ?? '');
  return arr.some((x) => String(x ?? '') === s);
}

function facetSetCodeSelected(arr, code, on) {
  if (!Array.isArray(arr)) return;
  const s = String(code ?? '');
  const idx = arr.findIndex((x) => String(x ?? '') === s);
  if (on && idx < 0) {
    arr.push(s);
  }
  if (!on && idx >= 0) {
    arr.splice(idx, 1);
  }
}

function facetToggleCode(arr, code) {
  facetSetCodeSelected(arr, code, !facetCodesInclude(arr, code));
}

function clearFacetGeography() {
  filterDraft.dGeography.splice(0, filterDraft.dGeography.length);
}

function clearFacetPeriodicity() {
  filterDraft.dPeriodicity.splice(0, filterDraft.dPeriodicity.length);
}

function clearFacetDimension(colName) {
  const arr = filterDraft.c[colName];
  if (Array.isArray(arr)) {
    arr.splice(0, arr.length);
  }
}

function clearFacetTime() {
  const b = chartTimeBoundsYears.value;
  if (canUseYearSlider.value && b) {
    yearSliderLocal.value = [b.min, b.max];
    filterDraft.from = String(b.min);
    filterDraft.to = String(b.max);
  } else {
    filterDraft.from = '';
    filterDraft.to = '';
  }
}

const componentsSorted = computed(() => {
  const list = schema.value?.components;
  if (!Array.isArray(list)) return [];
  return [...list].sort((a, b) => {
    const oa = Number(a.sort_order) || 0;
    const ob = Number(b.sort_order) || 0;
    if (oa !== ob) return oa - ob;
    return Number(a.id) - Number(b.id);
  });
});

const observationValueKey = computed(() => schema.value?.observation_value_component || '');
const timePeriodKey = computed(() => schema.value?.time_period_component || '');

const geographyComponent = computed(() => componentsSorted.value.find((c) => c?.column_type === 'geography') || null);
const periodicityComponent = computed(() => componentsSorted.value.find((c) => c?.column_type === 'periodicity') || null);
const timePeriodComponentForFilter = computed(
  () => componentsSorted.value.find((c) => c?.column_type === 'time_period') || null
);
/** Dimension columns only (c[…] in API); geography / periodicity use d[…]; time range uses from/to only (metadata editor style). */
const facetComponentsForC = computed(() => {
  const ov = observationValueKey.value;
  const tp = timePeriodKey.value;
  return componentsSorted.value.filter((c) => {
    if (!c?.name) return false;
    if (c.name === ov || c.name === tp) return false;
    return c.column_type === 'dimension';
  });
});

/**
 * True when the facet should appear in the sidebar (more than one observed code).
 * If filter options for that column are not loaded yet (`undefined`), keep showing the facet until counts are known.
 */
function facetShowsFilter(componentName) {
  const key = String(componentName || '');
  const items = codelistSelectItems.value[key];
  if (items === undefined) return true;
  return items.length > 1;
}

const geographyFilterVisible = computed(() => {
  const g = geographyComponent.value;
  return Boolean(g && facetShowsFilter(g.name));
});
const periodicityFilterVisible = computed(() => {
  const p = periodicityComponent.value;
  return Boolean(p && facetShowsFilter(p.name));
});
const facetComponentsForCVisible = computed(() => facetComponentsForC.value.filter((c) => facetShowsFilter(c.name)));

/** Slice facets exist — require explicit picks only for facets that offer a real choice (>1 observed code). */
const catalogSliceRequired = computed(() => {
  if (!schema.value) return false;
  const geoNeed = Boolean(geographyComponent.value && facetShowsFilter(geographyComponent.value.name));
  const dimNeed = facetComponentsForC.value.some((c) => facetShowsFilter(c.name));
  return geoNeed || dimNeed;
});

const catalogSliceSelectionComplete = computed(() => {
  if (!catalogSliceRequired.value) return true;
  const geo = geographyComponent.value;
  if (geo && facetShowsFilter(geo.name) && (!filterDraft.dGeography || filterDraft.dGeography.length === 0)) {
    return false;
  }
  for (const col of facetComponentsForC.value) {
    if (!facetShowsFilter(col.name)) continue;
    const arr = filterDraft.c[col.name];
    if (!arr || arr.length === 0) return false;
  }
  return true;
});

/** Expansion panel `value`s currently open (sidebar); reset when study/schema loads. */
const chartFilterExpandedPanels = ref([]);

function syncChartFilterExpandedPanelsOpen() {
  if (!schema.value) {
    chartFilterExpandedPanels.value = [];
    return;
  }
  const keys = [];
  if (geographyFilterVisible.value) keys.push('geo');
  keys.push('time');
  if (periodicityFilterVisible.value) keys.push('period');
  for (const col of facetComponentsForCVisible.value) {
    keys.push(`dim:${col.name}`);
  }
  chartFilterExpandedPanels.value = keys;
}

/** User has applied filters at least once (payload sent to APIs). */
const dataLoadCommitted = computed(() => activeFilters.value != null);

// --- URL query sync (tab + chart filter draft) ---------------------------------

const Q_CHART = 'chart';
const Q_FROM = 'from';
const Q_TO = 'to';
const Q_GEO = 'geo';
const Q_PERIOD = 'period';

/** Query key for a dimension column, e.g. `c[Sex_Code]`. */
function dimQueryKey(colName) {
  return `c[${String(colName || '')}]`;
}

/** Split one query param value into codes (comma-separated, each segment URI-encoded). */
function splitCommaEncodedDimValues(raw) {
  if (raw == null || String(raw).trim() === '') return [];
  return String(raw)
    .split(',')
    .map((x) => x.trim())
    .filter(Boolean)
    .map((x) => {
      try {
        return decodeURIComponent(x.replace(/\+/g, ' '));
      } catch {
        return x;
      }
    });
}

/** Join selected codes for a single dimension query param. */
function joinCommaEncodedDimValues(values) {
  return values
    .map((v) => String(v ?? '').trim())
    .filter(Boolean)
    .map((v) => encodeURIComponent(v))
    .join(',');
}

/** Unique codes from `key` (comma-separated values and/or repeated `key=` entries, for older URLs). */
function readCodesFromQueryParam(q, key) {
  if (!q.has(key)) return [];
  const seen = new Set();
  const out = [];
  for (const segment of q.getAll(key)) {
    for (const code of splitCommaEncodedDimValues(segment)) {
      const s = String(code ?? '').trim();
      if (!s || seen.has(s)) continue;
      seen.add(s);
      out.push(s);
    }
  }
  return out;
}

function readChartTypeFromQuery(q) {
  if (q.has(Q_CHART)) {
    const ct = String(q.get(Q_CHART) || '')
      .trim()
      .toLowerCase();
    if (ct === 'column' || ct === 'bar' || ct === 'columns') chartType.value = 'column';
    else chartType.value = 'line';
  }
}

function readChartFiltersFromQuery(q) {
  if (q.has(Q_FROM)) {
    filterDraft.from = String(q.get(Q_FROM) ?? '').trim();
  }
  if (q.has(Q_TO)) {
    filterDraft.to = String(q.get(Q_TO) ?? '').trim();
  }
  if (q.has(Q_GEO) && geographyComponent.value) {
    filterDraft.dGeography = readCodesFromQueryParam(q, Q_GEO);
  }
  if (q.has(Q_PERIOD) && periodicityComponent.value) {
    filterDraft.dPeriodicity = readCodesFromQueryParam(q, Q_PERIOD);
  }
  for (const col of facetComponentsForC.value) {
    const k = dimQueryKey(col.name);
    if (q.has(k) && col.name in filterDraft.c) {
      filterDraft.c[col.name] = splitCommaEncodedDimValues(q.get(k));
    }
  }
}

function applyCatalogQueryFromUrl() {
  if (typeof window === 'undefined') return;
  const q = new URLSearchParams(window.location.search || '');
  readChartTypeFromQuery(q);
  readChartFiltersFromQuery(q);
}

/** True if the URL already specifies any chart filter (do not overwrite with defaults). */
function urlHasChartFilterParams() {
  if (typeof window === 'undefined') return false;
  const q = new URLSearchParams(window.location.search || '');
  if (q.has(Q_FROM) && String(q.get(Q_FROM) ?? '').trim() !== '') return true;
  if (q.has(Q_TO) && String(q.get(Q_TO) ?? '').trim() !== '') return true;
  if (readCodesFromQueryParam(q, Q_GEO).length) return true;
  if (readCodesFromQueryParam(q, Q_PERIOD).length) return true;
  for (const key of q.keys()) {
    if (/^c\[[^\]]+\]$/.test(key) && splitCommaEncodedDimValues(q.get(key)).length) return true;
  }
  return false;
}

function draftHasAnyChartFilterSelection() {
  if (filterDraft.dGeography.length) return true;
  if (filterDraft.dPeriodicity.length) return true;
  for (const col of facetComponentsForC.value) {
    if (filterDraft.c[col.name]?.length) return true;
  }
  if (String(filterDraft.from ?? '').trim()) return true;
  if (String(filterDraft.to ?? '').trim()) return true;
  return false;
}

/**
 * First visit with no query filters: pick the first codelist code for geography, periodicity,
 * and each dimension facet, and use the full schema reporting-year span when the year slider applies.
 * Skips when the URL already carried filter params or the draft is non-empty.
 */
function applyDefaultChartFiltersIfBlank() {
  if (urlHasChartFilterParams() || draftHasAnyChartFilterSelection()) return;

  const geo = geographyComponent.value;
  if (geo?.name) {
    const items = codelistSelectItems.value[geo.name] || [];
    if (items.length && !filterDraft.dGeography.length) {
      filterDraft.dGeography.push(String(items[0].value));
    }
  }
  const per = periodicityComponent.value;
  if (per?.name) {
    const items = codelistSelectItems.value[per.name] || [];
    if (items.length && !filterDraft.dPeriodicity.length) {
      filterDraft.dPeriodicity.push(String(items[0].value));
    }
  }
  for (const col of facetComponentsForC.value) {
    const items = codelistSelectItems.value[col.name] || [];
    if (items.length && !(filterDraft.c[col.name]?.length)) {
      filterDraft.c[col.name].push(String(items[0].value));
    }
  }

  const b = chartTimeBoundsYears.value;
  if (b && canUseYearSlider.value) {
    yearSliderLocal.value = [b.min, b.max];
    filterDraft.from = String(b.min);
    filterDraft.to = String(b.max);
  }
}

/**
 * After filter-options load, pin draft to the only observed code for facets with a single value.
 * Keeps API filters correct when those facets are omitted from the URL (no user choice).
 */
function ensureSingleOptionFacetDrafts() {
  const geo = geographyComponent.value;
  if (geo?.name) {
    const items = codelistSelectItems.value[geo.name];
    if (Array.isArray(items) && items.length === 1) {
      filterDraft.dGeography = [String(items[0].value)];
    }
  }
  const per = periodicityComponent.value;
  if (per?.name) {
    const items = codelistSelectItems.value[per.name];
    if (Array.isArray(items) && items.length === 1) {
      filterDraft.dPeriodicity = [String(items[0].value)];
    }
  }
  for (const col of facetComponentsForC.value) {
    if (!(col.name in filterDraft.c)) continue;
    const items = codelistSelectItems.value[col.name];
    if (Array.isArray(items) && items.length === 1) {
      filterDraft.c[col.name] = [String(items[0].value)];
    }
  }
}

function stripCatalogQueryKeys(sp) {
  for (const key of [...sp.keys()]) {
    if (
      key === Q_CHART ||
      key === Q_FROM ||
      key === Q_TO ||
      key === Q_GEO ||
      key === Q_PERIOD ||
      key.startsWith('c_') ||
      /^c\[[^\]]+\]$/.test(key)
    ) {
      sp.delete(key);
    }
  }
}

function buildCatalogQueryParams() {
  const p = new URLSearchParams();
  p.set(Q_CHART, chartType.value === 'column' ? 'column' : 'line');
  const fr = String(filterDraft.from || '').trim();
  const to = String(filterDraft.to || '').trim();
  if (fr) p.set(Q_FROM, fr);
  if (to) p.set(Q_TO, to);
  const geo = geographyComponent.value;
  if (geo && facetShowsFilter(geo.name)) {
    const geoCodes = [...new Set(filterDraft.dGeography.map((v) => String(v ?? '').trim()).filter(Boolean))];
    const geoJoined = joinCommaEncodedDimValues(geoCodes);
    if (geoJoined) p.set(Q_GEO, geoJoined);
  }
  const per = periodicityComponent.value;
  if (per && facetShowsFilter(per.name)) {
    const periodCodes = [...new Set(filterDraft.dPeriodicity.map((v) => String(v ?? '').trim()).filter(Boolean))];
    const periodJoined = joinCommaEncodedDimValues(periodCodes);
    if (periodJoined) p.set(Q_PERIOD, periodJoined);
  }
  for (const col of facetComponentsForC.value) {
    if (!facetShowsFilter(col.name)) continue;
    const arr = filterDraft.c[col.name];
    if (!Array.isArray(arr) || !arr.length) continue;
    const k = dimQueryKey(col.name);
    const joined = joinCommaEncodedDimValues(arr);
    if (joined) p.set(k, joined);
  }
  return p;
}

function writeCatalogQueryToUrl() {
  if (typeof window === 'undefined' || urlSyncSuspended.value) return;
  if (!schema.value) return;
  const url = new URL(window.location.href);
  const sp = new URLSearchParams(url.search);
  stripCatalogQueryKeys(sp);
  const add = buildCatalogQueryParams();
  for (const key of [...add.keys()]) {
    for (const v of add.getAll(key)) {
      sp.append(key, v);
    }
  }
  url.search = sp.toString();
  window.history.replaceState(window.history.state, '', url.toString());
}

const debouncedWriteCatalogQueryToUrl = debounce(writeCatalogQueryToUrl, 260);

watch(chartType, () => {
  debouncedWriteCatalogQueryToUrl();
});

watch(
  () => ({
    from: filterDraft.from,
    to: filterDraft.to,
    geo: filterDraft.dGeography.slice(),
    period: filterDraft.dPeriodicity.slice(),
    c: JSON.stringify(filterDraft.c),
  }),
  () => {
    debouncedWriteCatalogQueryToUrl();
  }
);

const chartScanTruncated = computed(() => chartMetadata.value?.truncated === true);

/** Rows returned for the last successful chart request (same as table / API). */
const chartExportDataAvailable = computed(
  () => dataLoadCommitted.value && !chartLoading.value && chartRecords.value.length > 0
);
/** Canvas chart rendered (line/bar). */
const chartExportPngAvailable = computed(
  () =>
    chartExportDataAvailable.value &&
    (chartModel.value?.datasets?.length ?? 0) > 0 &&
    chartInstance.value != null
);

/** Read string prop with case-insensitive key (SQL drivers / payloads vary). */
function componentPropString(obj, key) {
  if (!obj || typeof obj !== 'object') return '';
  if (obj[key] != null && String(obj[key]).trim() !== '') {
    return String(obj[key]).trim();
  }
  const want = String(key).toLowerCase();
  for (const k of Object.keys(obj)) {
    if (String(k).toLowerCase() === want) {
      const v = obj[k];
      if (v != null && String(v).trim() !== '') {
        return String(v).trim();
      }
    }
  }
  return '';
}

function parseComponentMetadata(metadata) {
  if (metadata == null || metadata === '') return null;
  if (typeof metadata === 'object' && !Array.isArray(metadata)) {
    return metadata;
  }
  if (typeof metadata === 'string') {
    try {
      const o = JSON.parse(metadata);
      return typeof o === 'object' && o !== null && !Array.isArray(o) ? o : null;
    } catch {
      return null;
    }
  }
  return null;
}

/** Human-facing name from DSD component (DB label/title, metadata, then field name). */
function facetLabel(c) {
  if (!c) return '';
  const directKeys = ['label', 'title', 'field_label', 'field_title', 'display_name', 'displayName', 'caption'];
  for (const k of directKeys) {
    const v = componentPropString(c, k);
    if (v) return v;
  }
  const meta = parseComponentMetadata(c.metadata);
  if (meta) {
    const metaKeys = [
      'label',
      'title',
      'field_title',
      'field_label',
      'display_name',
      'displayName',
      'concept_title',
      'conceptTitle',
    ];
    for (const k of metaKeys) {
      const v = componentPropString(meta, k);
      if (v) return v;
    }
  }
  return componentPropString(c, 'name');
}

const timePeriodFilterSectionLabel = computed(() => {
  const s = facetLabel(timePeriodComponentForFilter.value);
  return s !== '' ? s : 'Time period';
});

function formatCell(val) {
  if (val === null || val === undefined) return '—';
  if (typeof val === 'object') {
    if (val.$date != null) return String(val.$date);
    const s = JSON.stringify(val);
    return s.length > 80 ? `${s.slice(0, 77)}…` : s;
  }
  return String(val);
}

/** Clears visualization (chart) state only; observations tab stays unfiltered. */
function clearCatalogData() {
  filteredObservationCount.value = 0;
  chartRecords.value = [];
  chartMetadata.value = {};
}

function syncFilterDraftCShape() {
  const keep = new Set(facetComponentsForC.value.map((c) => c.name));
  for (const k of Object.keys(filterDraft.c)) {
    if (!keep.has(k)) {
      delete filterDraft.c[k];
    }
  }
  for (const c of facetComponentsForC.value) {
    if (!(c.name in filterDraft.c)) {
      filterDraft.c[c.name] = [];
    }
  }
}

function buildApiFiltersFromDraft() {
  const d = {};
  if (filterDraft.dGeography.length) d.geography = filterDraft.dGeography.map(String);
  if (filterDraft.dPeriodicity.length) d.periodicity = filterDraft.dPeriodicity.map(String);
  const c = {};
  for (const col of facetComponentsForC.value) {
    const arr = filterDraft.c[col.name];
    if (arr && arr.length) {
      c[col.name] = arr.map(String);
    }
  }
  const from = filterDraft.from.trim();
  const to = filterDraft.to.trim();
  return {
    ...(from ? { from } : {}),
    ...(to ? { to } : {}),
    ...(Object.keys(d).length ? { d } : {}),
    ...(Object.keys(c).length ? { c } : {}),
  };
}

/** Turn `col=code` segments into readable labels using loaded codelists (server `series_key`). */
function seriesLabelForId(id) {
  if (!id || id === 'Series') return id;
  const segments = String(id).split(' | ');
  const labels = segments.map((seg) => {
    const eq = seg.indexOf('=');
    if (eq <= 0) return seg;
    const colName = seg.slice(0, eq);
    const code = seg.slice(eq + 1);
    const items = codelistSelectItems.value[colName] || [];
    const hit = items.find((it) => it.value === code);
    if (hit?.title) return hit.title;
    return seg;
  });
  return labels.join(' · ');
}

/** Table rows for chart tab: same data as `chartRecords`, codes resolved where possible. */
const chartRecordsDisplay = computed(() =>
  chartRecords.value.map((row) => {
    const sk = row.series_key != null && String(row.series_key) !== '' ? String(row.series_key) : 'Series';
    return {
      time_period: formatCell(row.time_period),
      observation_value: formatCell(row.observation_value),
      series_key: formatCell(row.series_key),
      series_label: seriesLabelForId(sk),
    };
  })
);

const valueAxisLabel = computed(() => {
  const k = observationValueKey.value;
  return k ? `Value (${k})` : 'Value';
});

const xAxisLabel = computed(() => timePeriodFilterSectionLabel.value);

const chartDataTableHeaders = computed(() => [
  { title: xAxisLabel.value || 'Time period', key: 'time_period', sortable: false },
  { title: 'Value', key: 'observation_value', sortable: false },
  { title: 'Series key', key: 'series_key', sortable: false },
  { title: 'Series', key: 'series_label', sortable: false },
]);

function sanitizeChartExportFilenamePart(s) {
  const t = String(s ?? '')
    .trim()
    .replace(/[^\w.-]+/g, '-')
    .replace(/^-+|-+$/g, '');
  return t || 'study';
}

function chartExportFileStamp() {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}${pad(d.getMonth() + 1)}${pad(d.getDate())}-${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}`;
}

function triggerBrowserDownload({ href, download, revokeObjectUrl }) {
  const a = document.createElement('a');
  a.href = href;
  a.download = download;
  a.rel = 'noopener';
  document.body.appendChild(a);
  a.click();
  a.remove();
  if (revokeObjectUrl) {
    URL.revokeObjectURL(href);
  }
}

function downloadTextAsFile(filename, text, mime = 'text/plain;charset=utf-8') {
  const blob = new Blob([text], { type: mime });
  const href = URL.createObjectURL(blob);
  triggerBrowserDownload({ href, download: filename, revokeObjectUrl: true });
}

function escapeCsvField(val) {
  if (val === null || val === undefined) return '';
  const s = typeof val === 'object' ? JSON.stringify(val) : String(val);
  if (/[",\n\r]/.test(s)) {
    return `"${s.replace(/"/g, '""')}"`;
  }
  return s;
}

function buildChartExportRowsForFile() {
  return chartRecords.value.map((row) => {
    const sk = row.series_key != null && String(row.series_key) !== '' ? String(row.series_key) : 'Series';
    const base = row && typeof row === 'object' ? { ...row } : {};
    return {
      ...base,
      series_label: seriesLabelForId(sk),
    };
  });
}

function buildChartExportPayload() {
  return {
    exportedAt: new Date().toISOString(),
    studySid: studySid.value,
    studyIdno: studyIdno.value,
    chartType: chartType.value,
    filters: activeFilters.value,
    metadata: chartMetadata.value,
    records: buildChartExportRowsForFile(),
  };
}

function exportChartDataJson() {
  if (!chartExportDataAvailable.value) return;
  const idPart = sanitizeChartExportFilenamePart(studyIdno.value || String(studySid.value));
  const filename = `chart-data-${idPart}-${chartExportFileStamp()}.json`;
  downloadTextAsFile(filename, JSON.stringify(buildChartExportPayload(), null, 2), 'application/json;charset=utf-8');
}

function exportChartDataCsv() {
  if (!chartExportDataAvailable.value) return;
  const rows = buildChartExportRowsForFile();
  const keys = ['time_period', 'observation_value', 'series_key', 'series_key_label', 'series_label'];
  const present = keys.filter((k) => rows.some((r) => Object.prototype.hasOwnProperty.call(r, k)));
  const header = present.join(',');
  const lines = rows.map((r) => present.map((k) => escapeCsvField(r[k])).join(','));
  const csv = [header, ...lines].join('\r\n');
  const idPart = sanitizeChartExportFilenamePart(studyIdno.value || String(studySid.value));
  const filename = `chart-data-${idPart}-${chartExportFileStamp()}.csv`;
  downloadTextAsFile(filename, csv, 'text/csv;charset=utf-8');
}

function exportChartPng() {
  if (!chartExportPngAvailable.value) return;
  const inst = chartInstance.value;
  if (!inst?.toBase64Image) return;
  const dataUrl = inst.toBase64Image('image/png', 1);
  const idPart = sanitizeChartExportFilenamePart(studyIdno.value || String(studySid.value));
  const filename = `chart-${idPart}-${chartExportFileStamp()}.png`;
  triggerBrowserDownload({ href: dataUrl, download: filename, revokeObjectUrl: false });
}

function hexToRgba(hex, alpha) {
  const m = /^#?([\da-f]{2})([\da-f]{2})([\da-f]{2})$/i.exec(hex);
  if (!m) {
    return `rgba(25, 118, 210, ${alpha})`;
  }
  return `rgba(${parseInt(m[1], 16)}, ${parseInt(m[2], 16)}, ${parseInt(m[3], 16)}, ${alpha})`;
}

function clamp(n, lo, hi) {
  return Math.min(Math.max(n, lo), hi);
}

/** First calendar year in a filter or bound string (ISO or leading digits). */
function parseLeadingCalendarYear(s) {
  if (s == null || String(s).trim() === '') return null;
  const m = String(s).trim().match(/^(\d{4})\b/);
  if (!m) return null;
  const y = Number(m[1]);
  return Number.isFinite(y) ? y : null;
}

/**
 * Choose Chart.js x scale: category (even spacing) vs linear (calendar distance).
 * @param {string[]} sortedTimes
 */
function classifyTimeKeys(sortedTimes) {
  if (!sortedTimes.length) return { useNumeric: false, kind: null, parse: null };
  const trimmed = sortedTimes.map((t) => String(t).trim());
  const allYearCodes = trimmed.every((t) => /^\d{4}$/.test(t));
  if (allYearCodes) {
    return {
      useNumeric: true,
      kind: 'year',
      parse: (t) => Number(String(t).trim()),
    };
  }
  const isoDay = /^(\d{4})-(\d{2})-(\d{2})(?:[Tt ].*)?$/;
  const allIsoDays = trimmed.every((t) => isoDay.test(t));
  if (allIsoDays) {
    return {
      useNumeric: true,
      kind: 'day',
      parse: (t) => {
        const ms = Date.parse(String(t).trim());
        return Number.isFinite(ms) ? ms : null;
      },
    };
  }
  return { useNumeric: false, kind: null, parse: null };
}

/** Min/max `_ts_year` from schema (Mongo aggregate); chart `time_bounds` once a chart exists. */
function reportingYearBoundsFromSchema(sch) {
  const rb = sch?.reporting_year_bounds;
  if (!rb || typeof rb !== 'object') return null;
  if (rb.min == null || rb.max == null) return null;
  const mn = typeof rb.min === 'string' && /^\d+$/.test(rb.min.trim()) ? Number(rb.min.trim()) : Number(rb.min);
  const mx = typeof rb.max === 'string' && /^\d+$/.test(rb.max.trim()) ? Number(rb.max.trim()) : Number(rb.max);
  if (!Number.isFinite(mn) || !Number.isFinite(mx) || mn <= 0 || mx <= 0 || mn > mx) return null;
  return { min: Math.trunc(mn), max: Math.trunc(mx) };
}

const chartTimeBoundsYears = computed(() => {
  const tb = chartMetadata.value?.time_bounds;
  let fromChart = null;
  if (tb) {
    const mn = parseLeadingCalendarYear(tb.min);
    const mx = parseLeadingCalendarYear(tb.max);
    if (mn != null && mx != null && mn <= mx) {
      fromChart = { min: mn, max: mx };
    }
  }
  const fromSchema = reportingYearBoundsFromSchema(schema.value);
  if (fromSchema && fromChart) {
    return {
      min: Math.min(fromSchema.min, fromChart.min),
      max: Math.max(fromSchema.max, fromChart.max),
    };
  }
  return fromSchema || fromChart || null;
});

const canUseYearSlider = computed(() => {
  const b = chartTimeBoundsYears.value;
  if (!b) return false;
  const span = b.max - b.min;
  if (!Number.isFinite(span) || span < 0) return false;
  // Wide bounds: schema may span centuries; slider still works (ticks stay light).
  if (b.min < 1 || b.max > 9999 || span > 8000) return false;
  return true;
});

/** Time facet differs from full schema/chart year span (slider) or has From/To text. */
const timeFacetShowSummary = computed(() => {
  if (canUseYearSlider.value) {
    const b = chartTimeBoundsYears.value;
    if (!b) return false;
    const lo = Number(yearSliderLocal.value[0]);
    const hi = Number(yearSliderLocal.value[1]);
    if (!Number.isFinite(lo) || !Number.isFinite(hi)) return false;
    return lo !== b.min || hi !== b.max;
  }
  return !!(String(filterDraft.from ?? '').trim() || String(filterDraft.to ?? '').trim());
});

/** Count: years in selected span (slider) or number of non-empty From/To fields (text). */
const timeFacetSummaryCount = computed(() => {
  if (canUseYearSlider.value) {
    const lo = Number(yearSliderLocal.value[0]);
    const hi = Number(yearSliderLocal.value[1]);
    if (!Number.isFinite(lo) || !Number.isFinite(hi)) return 0;
    return Math.max(0, Math.trunc(hi) - Math.trunc(lo) + 1);
  }
  let n = 0;
  if (String(filterDraft.from ?? '').trim()) n += 1;
  if (String(filterDraft.to ?? '').trim()) n += 1;
  return n;
});

function syncYearSliderLocalFromFilters() {
  const b = chartTimeBoundsYears.value;
  if (!b) return;
  const useSlider = canUseYearSlider.value;
  const fy = parseLeadingCalendarYear(filterDraft.from);
  const ty = parseLeadingCalendarYear(filterDraft.to);
  const fromEmpty = !String(filterDraft.from ?? '').trim();
  const toEmpty = !String(filterDraft.to ?? '').trim();
  if (fy != null && ty != null) {
    const lo = clamp(fy, b.min, b.max);
    const hi = clamp(ty, b.min, b.max);
    yearSliderLocal.value = [lo, hi].sort((x, y) => x - y);
    if (useSlider) {
      filterDraft.from = String(yearSliderLocal.value[0]);
      filterDraft.to = String(yearSliderLocal.value[1]);
    }
    return;
  }
  if (fy != null && toEmpty) {
    yearSliderLocal.value = [clamp(fy, b.min, b.max), b.max].sort((x, y) => x - y);
    if (useSlider) {
      filterDraft.from = String(yearSliderLocal.value[0]);
    }
    return;
  }
  if (ty != null && fromEmpty) {
    yearSliderLocal.value = [b.min, clamp(ty, b.min, b.max)].sort((x, y) => x - y);
    if (useSlider) {
      filterDraft.to = String(yearSliderLocal.value[1]);
    }
    return;
  }
  yearSliderLocal.value = [b.min, b.max];
}

function onYearSliderInput(val) {
  const b = chartTimeBoundsYears.value;
  if (!b || !Array.isArray(val) || val.length < 2) return;
  const lo = clamp(Math.min(Number(val[0]), Number(val[1])), b.min, b.max);
  const hi = clamp(Math.max(Number(val[0]), Number(val[1])), b.min, b.max);
  yearSliderLocal.value = [lo, hi];
  filterDraft.from = String(lo);
  filterDraft.to = String(hi);
}

/** Pivot server chart `records` into Chart.js datasets (same shape as Metadata Editor client transform). */
const chartModel = computed(() => {
  const records = chartRecords.value;
  if (!records?.length) return null;

  const bySeries = new Map();
  for (const row of records) {
    const sid = row.series_key != null && String(row.series_key) !== '' ? String(row.series_key) : 'Series';
    const t = row.time_period != null ? String(row.time_period) : '';
    if (!t) continue;
    const y = Number(row.observation_value);
    if (!Number.isFinite(y)) continue;
    if (!bySeries.has(sid)) bySeries.set(sid, new Map());
    bySeries.get(sid).set(t, y);
  }

  const timeSet = new Set();
  for (const m of bySeries.values()) {
    for (const tk of m.keys()) timeSet.add(tk);
  }
  const sortedTimes = Array.from(timeSet).sort((a, b) => String(a).localeCompare(String(b)));
  if (!sortedTimes.length) return null;

  const scale = classifyTimeKeys(sortedTimes);

  let seriesIds = Array.from(bySeries.keys()).sort((a, b) =>
    String(seriesLabelForId(a)).localeCompare(String(seriesLabelForId(b)), undefined, { sensitivity: 'base' })
  );
  let dropped = 0;
  if (seriesIds.length > MAX_SERIES) {
    dropped = seriesIds.length - MAX_SERIES;
    seriesIds = seriesIds.slice(0, MAX_SERIES);
  }

  const isBar = chartType.value === 'column' && seriesIds.length === 1;
  const sparsePoints = sortedTimes.length > 150;

  let totalPoints = 0;
  const datasets = seriesIds.map((id, idx) => {
    const m = bySeries.get(id);
    let data;

    if (scale.useNumeric && scale.parse) {
      data = [];
      for (const t of sortedTimes) {
        const v = m.get(t);
        if (!Number.isFinite(v)) continue;
        const x = scale.parse(t);
        if (x == null || !Number.isFinite(Number(x))) continue;
        totalPoints++;
        data.push({ x, y: v });
      }
      data.sort((a, b) => a.x - b.x);
    } else {
      data = sortedTimes.map((t) => {
        const v = m.get(t);
        if (Number.isFinite(v)) {
          totalPoints++;
          return v;
        }
        return null;
      });
    }

    const color = SERIES_COLORS[idx % SERIES_COLORS.length];
    return {
      label: seriesLabelForId(id),
      data,
      borderColor: color,
      backgroundColor: isBar ? hexToRgba(color, 0.82) : hexToRgba(color, 0.12),
      tension: 0,
      fill: false,
      spanGaps: false,
      pointRadius: sparsePoints ? 0 : 2,
      pointHitRadius: 6,
      borderWidth: isBar ? 0 : 2,
    };
  });

  if (totalPoints === 0) return null;
  return {
    labels: sortedTimes,
    sortedTimes,
    useNumericTimeX: scale.useNumeric,
    timeXKind: scale.kind,
    datasets,
    dropped,
    totalPoints,
  };
});

const chartAriaLabel = computed(() => {
  const n = chartModel.value?.totalPoints ?? 0;
  return `Time series chart, ${n} points`;
});

const seriesTruncated = computed(() => (chartModel.value?.dropped ?? 0) > 0);
const seriesDropped = computed(() => chartModel.value?.dropped ?? 0);

const chartModeToggleVisible = computed(() => (chartModel.value?.datasets?.length ?? 0) <= 1);

const chartCanvasRef = ref(null);
const chartInstance = shallowRef(null);

function destroyChart() {
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
}

function formatDayTick(ms) {
  if (!Number.isFinite(ms)) return '';
  try {
    return new Date(ms).toISOString().slice(0, 10);
  } catch {
    return String(ms);
  }
}

async function renderOrUpdateChart() {
  await nextTick();
  await new Promise((r) => requestAnimationFrame(r));
  destroyChart();
  if (chartLoading.value) return;
  const model = chartModel.value;
  const canvas = chartCanvasRef.value;
  if (!model?.datasets?.length || !canvas) return;

  const isBar = chartType.value === 'column' && model.datasets.length === 1;
  const numericX = model.useNumericTimeX === true;

  const inst = new Chart(canvas, {
    type: isBar ? 'bar' : 'line',
    data: {
      ...(numericX ? {} : { labels: model.labels }),
      datasets: model.datasets,
    },
    options: {
      font: {
        family: CHART_DATA_FONT_FAMILY,
        size: 11,
      },
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: numericX ? 'nearest' : 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          display: model.datasets.length > 1,
          position: 'top',
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.82)',
          padding: 10,
          callbacks: {
            title(items) {
              if (!items?.length) return '';
              if (numericX) {
                const x = items[0]?.parsed?.x;
                if (model.timeXKind === 'day' && Number.isFinite(x)) {
                  return formatDayTick(x);
                }
                if (Number.isFinite(x)) {
                  return String(Math.round(x));
                }
                return '';
              }
              const i = items[0]?.dataIndex;
              return i >= 0 ? String(model.labels[i] ?? '') : '';
            },
          },
        },
      },
      scales: {
        x: {
          type: numericX ? 'linear' : 'category',
          title: {
            display: true,
            text: xAxisLabel.value,
          },
          ticks: {
            maxRotation: 45,
            minRotation: 0,
            autoSkip: true,
            maxTicksLimit: model.timeXKind === 'day' ? 14 : 28,
            ...(numericX
              ? {
                  callback(val) {
                    if (model.timeXKind === 'day' && Number.isFinite(val)) {
                      return formatDayTick(val);
                    }
                    if (Number.isFinite(val)) {
                      return Number.isInteger(val) ? String(val) : String(Math.round(val));
                    }
                    return String(val);
                  },
                }
              : {}),
          },
        },
        y: {
          title: {
            display: true,
            text: valueAxisLabel.value,
          },
          beginAtZero: false,
          grid: {
            color: 'rgba(0, 0, 0, 0.06)',
          },
        },
      },
    },
  });
  chartInstance.value = inst;
  requestAnimationFrame(() => {
    inst.resize();
  });
}

watch(
  () => chartModel.value?.datasets?.length ?? 0,
  (n) => {
    if (n > 1 && chartType.value === 'column') {
      chartType.value = 'line';
    }
  }
);

watch(
  () => [
    chartMetadata.value?.time_bounds?.min,
    chartMetadata.value?.time_bounds?.max,
    schema.value?.reporting_year_bounds?.min,
    schema.value?.reporting_year_bounds?.max,
    canUseYearSlider.value,
  ],
  () => {
    if (canUseYearSlider.value) {
      syncYearSliderLocalFromFilters();
    }
  }
);

watch(
  () => [filterDraft.from, filterDraft.to],
  () => {
    if (canUseYearSlider.value) {
      syncYearSliderLocalFromFilters();
    }
  }
);

watch(
  () => [chartLoading.value, chartType.value, chartModel.value?.totalPoints ?? 0],
  async () => {
    if (chartLoading.value) {
      destroyChart();
      return;
    }
    await renderOrUpdateChart();
  },
  { deep: true, flush: 'post' }
);

onBeforeUnmount(() => {
  debouncedWriteCatalogQueryToUrl.cancel();
  destroyChart();
});

async function loadCodelistsForSchema() {
  codelistSelectItems.value = {};
  for (const k of Object.keys(facetListSearch)) {
    delete facetListSearch[k];
  }
  const byName = {};
  const observed = await fetchFilterOptions();
  const filters = Array.isArray(observed?.filters) ? observed.filters : [];
  for (const facet of filters) {
    const componentName = String(facet?.component_name ?? '').trim();
    if (!componentName) continue;
    const optionsRaw = Array.isArray(facet?.options) ? facet.options : [];
    const options = optionsRaw
      .map((it) => {
        const code = String(it?.code ?? '').trim();
        if (!code) return null;
        const label = String(it?.label ?? '').trim();
        return { title: label !== '' ? label : code, value: code };
      })
      .filter(Boolean);
    byName[componentName] = options;
  }
  for (const c of componentsSorted.value) {
    if (!c?.name) continue;
    if (!['geography', 'periodicity', 'dimension'].includes(c.column_type)) continue;
    if (!byName[c.name]) {
      byName[c.name] = [];
    }
  }
  codelistSelectItems.value = byName;
}

async function reloadChart() {
  if (!studyIdno.value) return;
  if (!activeFilters.value) {
    chartRecords.value = [];
    chartMetadata.value = {};
    chartLoading.value = false;
    return;
  }
  chartLoading.value = true;
  try {
    if (filteredObservationCount.value <= 0) {
      chartRecords.value = [];
      chartMetadata.value = {};
      return;
    }
    const res = await fetchChartData({
      filters: activeFilters.value,
      limit: CHART_API_LIMIT,
    });
    chartRecords.value = res.records || [];
    chartMetadata.value = res.metadata || {};
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Could not load chart data.', 'warning');
    chartRecords.value = [];
    chartMetadata.value = {};
  } finally {
    chartLoading.value = false;
  }
}

async function refreshCountsAndData() {
  if (catalogSliceRequired.value && !catalogSliceSelectionComplete.value) {
    clearCatalogData();
    return;
  }
  filteredObservationCount.value = await fetchObservationCount(activeFilters.value || undefined);
  await reloadChart();
}

async function onApplyFilters() {
  if (!studyIdno.value) return;
  if (catalogSliceRequired.value && !catalogSliceSelectionComplete.value) {
    setMessage('Choose the required filter values before continuing.', 'warning');
    return;
  }
  applyLoading.value = true;
  try {
    activeFilters.value = buildApiFiltersFromDraft();
    await refreshCountsAndData();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Could not apply filters.', 'error');
  } finally {
    applyLoading.value = false;
  }
}

async function loadAll() {
  if (!studyIdno.value) {
    fatalError.value = 'Missing study IDNO in page configuration.';
    pageLoading.value = false;
    return;
  }
  pageLoading.value = true;
  fatalError.value = '';
  urlSyncSuspended.value = true;
  try {
    const sch = await fetchSchema();
    schema.value = sch;
    syncFilterDraftCShape();
    applyCatalogQueryFromUrl();
    await loadCodelistsForSchema();
    await nextTick();
    applyDefaultChartFiltersIfBlank();
    ensureSingleOptionFacetDrafts();
    syncChartFilterExpandedPanelsOpen();
    if (canUseYearSlider.value) {
      syncYearSliderLocalFromFilters();
    }
    if (catalogSliceRequired.value) {
      if (catalogSliceSelectionComplete.value) {
        activeFilters.value = buildApiFiltersFromDraft();
        await refreshCountsAndData();
      } else {
        activeFilters.value = null;
        clearCatalogData();
      }
    } else {
      activeFilters.value = buildApiFiltersFromDraft();
      await refreshCountsAndData();
    }
  } catch (e) {
    fatalError.value = e?.response?.data?.message || e?.message || 'Could not load indicator data.';
    schema.value = null;
    chartFilterExpandedPanels.value = [];
  } finally {
    pageLoading.value = false;
    nextTick(() => {
      urlSyncSuspended.value = false;
      writeCatalogQueryToUrl();
    });
  }
}

watch(
  () => schema.value?.sid,
  () => {
    if (schema.value) {
      syncFilterDraftCShape();
    }
  }
);

onMounted(() => {
  loadAll();
});
</script>

<style scoped>
.catalog-indicator-page {
  max-width: 1320px;
  margin-inline: auto;
  padding-block: 0.5rem 1.5rem;
}

@media (min-width: 960px) {
  .catalog-indicator-page {
    padding-block: 0.75rem 2rem;
  }
}

.stat-block__code {
  display: inline-block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  padding: 0.1rem 0.35rem;
  border-radius: 6px;
  background: rgba(var(--v-theme-on-surface), 0.06);
}

.main-layout {
  align-items: stretch;
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

.filter-panel--dense .field-label {
  font-size: 0.6875rem;
  margin-bottom: 0.2rem;
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
.facet-filter-subheader__suffix {
  font-weight: 400;
  font-size: inherit;
  color: inherit;
}

.facet-filter-subheader__suffix {
  margin-inline-start: 0.2rem;
}

.facet-filter-subheader__clear {
  font-weight: 400 !important;
  letter-spacing: normal;
  font-size: inherit !important;
  color: rgba(var(--v-theme-on-surface), 0.48) !important;
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

.field-label {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  color: rgba(var(--v-theme-on-surface), 0.65);
  margin-bottom: 0.35rem;
}

.year-chip {
  border-radius: 999px !important;
}

.main-panel {
  min-height: 420px;
}

.catalog-divider {
  opacity: 0.55;
}

.section-kicker {
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.5);
  margin-bottom: 0.15rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.chart-type-toggle :deep(.v-btn) {
  min-width: 0;
  padding-inline: 0.5rem;
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}

.chart-toolbar-settings {
  flex-shrink: 0;
  margin-inline-start: 0.375rem;
}

.chart-toolbar-settings__icon {
  opacity: 0.88;
}

.chart-toolbar-settings :deep(.v-btn__overlay) {
  opacity: 0.06;
}

.chart-hint {
  background: rgba(var(--v-theme-on-surface), 0.04) !important;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.chart-shell {
  border-radius: 0;
  background: rgba(var(--v-theme-on-surface), 0.025);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.facet-checkbox-panel {
  display: flex;
  flex-direction: column;
  gap: 0;
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

.facet-checkbox-search :deep(.v-field__prepend-inner .v-icon) {
  font-size: 1rem;
  opacity: 0.75;
}

.facet-checkbox-search :deep(.v-field__append-inner .v-icon) {
  font-size: 1rem;
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
  padding: 0.35rem 0;
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

.chart-canvas-wrap {
  position: relative;
  width: 100%;
  max-width: 960px;
  margin: 0 auto;
  height: min(420px, 55vh);
  min-height: 280px;
}

.chart-empty-state {
  width: 100%;
  max-width: 960px;
  margin: 0 auto;
  min-height: min(420px, 55vh);
}

.chart-empty-state__icon {
  opacity: 0.38;
}

.chart-canvas {
  width: 100% !important;
  height: 100% !important;
  display: block;
}

.data-shell {
  background: rgb(var(--v-theme-surface));
}

/* Scroll on .v-table__wrapper so fixed-header thead sticks correctly.
   Scoped CSS does not pierce v-data-table’s inner DOM without :deep(). */
.chart-data-table {
  overflow: hidden;
}

.chart-data-table :deep(.v-table__wrapper) {
  max-height: min(720px, 70vh);
  overflow-y: auto;
  overflow-x: auto;
  min-height: 0;
  scrollbar-gutter: stable;
}

.chart-data-table :deep(th) {
  font-weight: 600 !important;
  font-size: 0.75rem !important;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55) !important;
}

/* Body only: distinct data face + aligned digits (headers stay Vuetify UI). */
.chart-data-table :deep(tbody td) {
  font-family: v-bind(CHART_DATA_FONT_FAMILY);
  font-size: 0.8125rem;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.015em;
}

/* Footer: drop “Items per page”, keep range on the left; pagination stays at end when needed */
.chart-data-table :deep(.v-data-table-footer__items-per-page) {
  display: none;
}

.chart-data-table :deep(.v-data-table-footer) {
  justify-content: space-between;
}

.chart-data-table :deep(.v-data-table-footer__info) {
  justify-content: flex-start;
  padding-inline: 12px 16px;
}
</style>
