<template>
  <div
    class="catalog-indicator-page"
    :class="{ 'catalog-indicator-page--window': tableWindowMode }"
  >
    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-6 rounded-s" height="3" />

    <v-alert v-if="fatalError" type="error" variant="tonal" class="mb-6" rounded="lg" prominent density="comfortable">
      {{ fatalError }}
    </v-alert>

    <template v-else-if="schema">
      <v-row dense class="main-layout">
        <v-col cols="12">
          <v-card class="main-panel table-main-panel" rounded="0" flat>
            <v-card-text class="pa-0">
              <v-row
                dense
                class="table-tab-layout align-start"
                :class="{ 'table-tab-layout--with-filters': filterPanelVisible && !tableWindowMode }"
              >
                <v-col
                  v-if="filterPanelVisible && !tableWindowMode"
                  cols="12"
                  lg="3"
                  class="table-filter-col d-flex mb-4 mb-lg-0 pr-lg-3"
                >
                  <IndicatorFilterSidebar
                    v-model:expanded-panels="filterExpandedPanels"
                    class="flex-grow-1"
                    flush
                    :filter-draft="filterDraft"
                    :codelist-select-items="codelistSelectItems"
                    :geography-component="geographyComponent"
                    :periodicity-component="periodicityComponent"
                    :facet-components-for-c-visible="facetComponentsForCVisible"
                    :geography-filter-visible="geographyFilterVisible"
                    :periodicity-filter-visible="periodicityFilterVisible"
                    :apply-loading="applyLoading"
                    :apply-disabled="applyLoading || !tableSliceSelectionComplete"
                    :dimension-label-fn="facetLabel"
                    @apply="onApplyFilters"
                  >
                    <template #time-title>{{ timePeriodFilterSectionLabel }}</template>
                    <template #time-filter>
                      <IndicatorTimeFilterPanel
                        :filter-draft="filterDraft"
                        :schema="schema"
                        :time-bounds="chartMetadata.time_bounds"
                      />
                    </template>
                  </IndicatorFilterSidebar>
                </v-col>

                <v-col cols="12" :lg="tableMainColLg" class="table-main-col">
              <div
                class="table-workspace"
                :class="{ 'table-workspace--window': tableWindowMode }"
              >
                <div class="table-chrome">
                  <div v-if="!isIndicatorEmbed" class="table-chrome__actions d-flex flex-wrap align-center gap-1">
                    <v-btn
                      v-if="!tableWindowMode"
                      variant="tonal"
                      size="small"
                      rounded="lg"
                      class="text-none"
                      prepend-icon="mdi-filter-outline"
                      :color="filterPanelVisible ? 'primary' : undefined"
                      @click="filterPanelVisible = !filterPanelVisible"
                    >
                      Filters
                    </v-btn>
                    <v-btn
                      v-if="!tableWindowMode"
                      variant="tonal"
                      size="small"
                      rounded="lg"
                      class="text-none"
                      prepend-icon="mdi-table-pivot"
                      :color="layoutPanelVisible ? 'primary' : undefined"
                      @click="onToggleLayoutPanel"
                    >
                      Layout
                    </v-btn>
                    <v-btn
                      variant="text"
                      size="small"
                      rounded="lg"
                      class="text-none table-chrome__window-btn"
                      :prepend-icon="tableWindowMode ? 'mdi-arrow-collapse-all' : 'mdi-arrow-expand-all'"
                      @click="toggleTableWindowMode"
                    >
                      {{ tableWindowMode ? 'Exit full window' : 'Full window' }}
                    </v-btn>
                    <v-spacer class="d-none d-sm-flex" />
                    <v-menu
                      v-if="embedUiAvailable || exportHtmlUrl || exportXlsxUrl"
                      location="bottom end"
                      transition="scale-transition"
                    >
                      <template #activator="{ props: tableMenuProps }">
                        <v-btn
                          v-bind="tableMenuProps"
                          icon
                          variant="text"
                          size="x-small"
                          density="compact"
                          rounded="md"
                          class="table-toolbar-cog-btn"
                          title="Table options"
                          aria-label="Table options"
                        >
                          <v-icon size="16">mdi-cog-outline</v-icon>
                        </v-btn>
                      </template>
                      <v-list density="compact" class="table-export-list">
                        <v-list-item
                          v-if="embedUiAvailable"
                          prepend-icon="mdi-code-tags"
                          title="Embed table"
                          :disabled="!embedTablePageUrl"
                          @click="embedDialogOpen = true"
                        />
                        <v-list-item
                          prepend-icon="mdi-file-code-outline"
                          title="Export HTML"
                          :disabled="!exportHtmlUrl"
                          :href="exportHtmlUrl || undefined"
                          target="_blank"
                        />
                        <v-list-item
                          prepend-icon="mdi-microsoft-excel"
                          title="Export Excel"
                          :disabled="!exportXlsxUrl"
                          :href="exportXlsxUrl || undefined"
                          target="_blank"
                        />
                      </v-list>
                    </v-menu>
                  </div>

                  <div
                    v-if="layoutApplied && (studyTitle || tableFilterContextLine)"
                    class="table-chrome__meta"
                  >
                    <div v-if="studyTitle" class="table-chrome__title text-truncate">
                      {{ studyTitle }}
                    </div>
                    <div
                      v-if="tableFilterContextLine"
                      class="table-chrome__subtitle text-truncate"
                      :title="tableFilterContextLine"
                    >
                      {{ tableFilterContextLine }}
                    </div>
                  </div>
                </div>

                <IndicatorTableLayoutPanel
                  v-show="!isIndicatorEmbed && !tableWindowMode && layoutPanelVisible"
                  ref="layoutPanelRef"
                  class="table-layout-panel-slot mb-3"
                  :eligible-dimensions="layoutEligibleDimensions"
                  :study-singleton-keys="layoutPanelSingletonKeys"
                  :build-default-layout="createDefaultLayout"
                  :applied-layout="appliedLayout"
                  :dimension-label-fn="facetLabel"
                  @apply="onApplyLayout"
                  @cancel="onCancelLayout"
                />

                <v-sheet v-if="!layoutApplied" rounded="lg" border class="table-empty-state pa-10 text-center">
                  <v-icon size="40" color="medium-emphasis" class="mb-3">mdi-table-pivot</v-icon>
                  <div class="text-h6 font-weight-medium mb-2">Configure table layout</div>
                  <p class="text-body-2 text-medium-emphasis mb-0 mx-auto" style="max-width: 28rem">
                    Open <strong>Layout</strong>, adjust dimensions, then click <strong>Apply layout</strong> in the toolbar.
                  </p>
                </v-sheet>

                <template v-else>
                  <v-progress-linear
                    v-if="tableLoading"
                    indeterminate
                    color="primary"
                    class="mb-2 rounded-s"
                    height="3"
                  />

                  <v-alert
                    v-if="chartMetadata.truncated"
                    type="warning"
                    variant="tonal"
                    density="compact"
                    class="mb-2 text-body-2"
                  >
                    Table is based on the first {{ chartMetadata.source_rows_scanned }} observation rows (limit
                    {{ chartMetadata.raw_row_limit }}). Narrow filters if data looks incomplete.
                  </v-alert>

                  <v-sheet
                    v-if="!tableLoading && pivotedModel"
                    rounded="lg"
                    border
                    class="table-viewport-shell data-shell pa-0"
                  >
                    <div class="table-viewport">
                      <IndicatorPivotTable
                        :model="pivotedModel"
                        :corner-header-title="cornerHeaderTitle"
                        sticky
                      />
                    </div>
                  </v-sheet>

                  <div
                    v-if="!tableSliceSelectionComplete || filtersCommitted"
                    class="table-data-footer"
                  >
                    <p v-if="!tableSliceSelectionComplete" class="text-caption text-warning mb-0">
                      Select at least one geography before loading data.
                    </p>
                    <p v-else-if="filtersCommitted" class="text-caption text-medium-emphasis mb-0">
                      {{ filteredObservationCount.toLocaleString() }} observation(s) match.
                    </p>
                  </div>
                </template>
              </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>

    <IndicatorEmbedDialog
      v-if="embedUiAvailable"
      v-model="embedDialogOpen"
      :embed-url="embedTablePageUrl"
      :iframe-title="studyTitle || 'Indicator table'"
      dialog-title="Embed this table"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, inject, onMounted, onUnmounted, watch, nextTick } from 'vue';
import debounce from 'lodash/debounce';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { usePublicTimeseriesApi } from '../composables/usePublicTimeseriesApi';
import {
  facetLabel,
  getLayoutEligibleDimensions,
  createEmptyTableLayout,
  normalizeTableLayout,
  TIME_PERIOD_KEY,
} from '@/shared/timeseries/indicatorSchemaUtils.js';
import {
  applyDefaultTableFilters,
  buildDefaultTableLayout,
} from '@/shared/timeseries/tableDefaultLayout.js';
import { getStudySingletonDimensionKeys, facetShowsFilter } from '@/shared/timeseries/tableLayoutMatrix.js';
import { buildIndicatorFilterContextLine } from '@/shared/timeseries/indicatorFilterContext.js';
import { pivotIndicatorTable } from '@/shared/timeseries/pivotIndicatorTable.js';
import {
  buildIndicatorFilterQueryParams,
  readIndicatorFiltersFromQuery,
  stripIndicatorFilterQueryKeys,
  urlHasIndicatorFilterParams,
} from '@/shared/timeseries/indicatorFilterQueryParams.js';
import {
  buildTableLayoutQueryParams,
  readTableLayoutFromQuery,
  stripChartOnlyQueryKeys,
  stripTableLayoutQueryKeys,
  urlHasTableLayoutParams,
} from '@/shared/timeseries/tableLayoutQueryParams.js';
import IndicatorFilterSidebar from '../components/IndicatorFilterSidebar.vue';
import IndicatorTimeFilterPanel from '../components/IndicatorTimeFilterPanel.vue';
import IndicatorTableLayoutPanel from '../components/IndicatorTableLayoutPanel.vue';
import IndicatorPivotTable from '../components/IndicatorPivotTable.vue';
import IndicatorEmbedDialog from '../components/IndicatorEmbedDialog.vue';

defineOptions({ name: 'IndicatorTableTab' });

const setMessage = inject('setMessage', () => {});

const { config } = useAppConfig();
const studyIdno = computed(() => String(config.value?.studyIdno ?? '').trim());
const studyTitle = computed(() => String(config.value?.studyTitle ?? '').trim());
const studySid = computed(() => config.value?.studySid ?? 0);
const siteUrl = computed(() => String(config.value?.siteUrl || '').trim());
const isIndicatorEmbed = computed(() => !!config.value?.indicatorEmbed);

const { fetchSchema, fetchFilterOptions, fetchObservationCount, fetchChartData, dataPath } = usePublicTimeseriesApi(studyIdno);

const CHART_API_LIMIT = 5000;

const pageLoading = ref(true);
const applyLoading = ref(false);
const tableLoading = ref(false);
const fatalError = ref('');
const schema = ref(null);
const filteredObservationCount = ref(0);
const chartRecords = ref([]);
const chartMetadata = ref({});
const activeFilters = ref(null);
const appliedLayout = ref(null);
const layoutApplied = ref(false);
const filterExpandedPanels = ref([]);

const filterPanelVisible = ref(false);
const tableWindowMode = ref(false);
const layoutPanelVisible = ref(false);
const layoutPanelRef = ref(null);
const embedDialogOpen = ref(false);
const embedUiAvailable = computed(() => !isIndicatorEmbed.value && layoutApplied.value && siteUrl.value !== '');

const urlSyncSuspended = ref(true);


function layoutSnapshot(layout = appliedLayout.value) {
  return JSON.stringify(normalizeTableLayout(layout));
}

const tableMainColLg = computed(() =>
  filterPanelVisible.value && !tableWindowMode.value ? 9 : 12
);

const filterDraft = reactive({
  from: '',
  to: '',
  dGeography: [],
  dPeriodicity: [],
  c: {},
});

const codelistSelectItems = ref({});

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

const timePeriodFilterSectionLabel = computed(() => {
  const s = facetLabel(timePeriodComponentForFilter.value);
  return s !== '' ? s : 'Time period';
});

const facetComponentsForC = computed(() => {
  const ov = observationValueKey.value;
  const tp = timePeriodKey.value;
  return componentsSorted.value.filter((c) => {
    if (!c?.name) return false;
    if (c.name === ov || c.name === tp) return false;
    return c.column_type === 'dimension';
  });
});

function facetShowsFilterForComponent(componentName) {
  return facetShowsFilter(componentName, codelistSelectItems.value);
}

const geographyFilterVisible = computed(() => {
  const g = geographyComponent.value;
  return Boolean(g && facetShowsFilterForComponent(g.name));
});

const periodicityFilterVisible = computed(() => {
  const p = periodicityComponent.value;
  return Boolean(p && facetShowsFilterForComponent(p.name));
});

const facetComponentsForCVisible = computed(() =>
  facetComponentsForC.value.filter((c) => facetShowsFilterForComponent(c.name))
);

/** Table tab: only geography must be explicitly chosen; other facets default to all codes. */
const tableSliceSelectionComplete = computed(() => {
  const geo = geographyComponent.value;
  if (geo && facetShowsFilterForComponent(geo.name) && (!filterDraft.dGeography || filterDraft.dGeography.length === 0)) {
    return false;
  }
  return true;
});

const filtersCommitted = computed(() => activeFilters.value != null);

const layoutEligibleDimensions = computed(() =>
  getLayoutEligibleDimensions(componentsSorted.value, {
    observationValueKey: observationValueKey.value,
    timePeriodKey: timePeriodKey.value,
  })
);

const geographyLayoutKey = computed(() => {
  const geo = geographyComponent.value;
  if (!geo?.name) return '';
  const dim = layoutEligibleDimensions.value.find(
    (d) => d.key === geo.name || d.component?.name === geo.name
  );
  return dim?.key ?? geo.name;
});

const studyGeographyCount = computed(() => {
  const geo = geographyComponent.value;
  if (!geo?.name) return 0;
  return (codelistSelectItems.value[geo.name] || []).length;
});

const studySingletonKeys = computed(() => {
  const set = getStudySingletonDimensionKeys(
    layoutEligibleDimensions.value,
    codelistSelectItems.value,
    { geographyLayoutKey: geographyLayoutKey.value }
  );
  return [...set];
});

/** Layout matrix: hide study singletons and geography when the study has only one. */
const layoutPanelSingletonKeys = computed(() => {
  const keys = new Set(studySingletonKeys.value);
  if (studyGeographyCount.value <= 1 && geographyLayoutKey.value) {
    keys.add(geographyLayoutKey.value);
  }
  return [...keys];
});

const tableFilterContextLine = computed(() =>
  buildIndicatorFilterContextLine({
    activeFilters: activeFilters.value,
    geographyComponent: geographyComponent.value,
    periodicityComponent: periodicityComponent.value,
    facetComponentsForC: facetComponentsForC.value,
    codelistSelectItems: codelistSelectItems.value,
    facetLabel,
    facetShowsFilter: () => true,
    timeBoundsFallback: chartMetadata.value?.time_bounds,
  })
);

function resolveCodeLabel(dimKey, code) {
  const items = codelistSelectItems.value[dimKey] || [];
  const hit = items.find((it) => String(it.value) === String(code));
  return hit?.title || code;
}

function resolvePivotLabel(dimKey, code) {
  if (dimKey === TIME_PERIOD_KEY) {
    return code;
  }
  const dim = layoutEligibleDimensions.value.find((d) => d.key === dimKey);
  const componentName = dim?.component?.name || dimKey;
  return resolveCodeLabel(componentName, code);
}

const pivotedModel = computed(() => {
  if (!layoutApplied.value || !appliedLayout.value) return null;
  return pivotIndicatorTable(chartRecords.value, appliedLayout.value, {
    timePeriodComponentName: timePeriodKey.value,
    resolveLabel: (dimKey, code) => resolvePivotLabel(dimKey, code),
    formatValue: (v) => (v == null || v === '' ? '' : String(v)),
    singleRowLabel: '',
  });
});

function resolveLayoutDimensionLabel(key) {
  const dim = layoutEligibleDimensions.value.find((d) => d.key === key);
  if (dim?.component) return facetLabel(dim.component);
  if (key === TIME_PERIOD_KEY) return 'Time period';
  return key;
}

const cornerHeaderTitle = computed(() => '');

function onToggleLayoutPanel() {
  layoutPanelVisible.value = !layoutPanelVisible.value;
  if (layoutPanelVisible.value) {
    layoutPanelRef.value?.openPanel?.();
  } else {
    layoutPanelRef.value?.closePanel?.();
  }
}


function toggleTableWindowMode() {
  if (!tableWindowMode.value) {
    tableWindowMode.value = true;
    layoutPanelVisible.value = false;
    layoutPanelRef.value?.closePanel?.();
    return;
  }
  tableWindowMode.value = false;
}

function onDocumentKeydown(event) {
  if (event.key === 'Escape' && tableWindowMode.value) {
    tableWindowMode.value = false;
  }
}

function syncFilterExpandedPanelsOpen() {
  const keys = ['time'];
  if (geographyFilterVisible.value) keys.push('geo');
  if (periodicityFilterVisible.value) keys.push('period');
  for (const col of facetComponentsForCVisible.value) {
    keys.push(`dim:${col.name}`);
  }
  filterExpandedPanels.value = keys;
}

function syncFilterDraftCShape() {
  const keep = new Set(facetComponentsForC.value.map((c) => c.name));
  for (const k of Object.keys(filterDraft.c)) {
    if (!keep.has(k)) delete filterDraft.c[k];
  }
  for (const c of facetComponentsForC.value) {
    if (!(c.name in filterDraft.c)) filterDraft.c[c.name] = [];
  }
}

function buildApiFiltersFromDraft() {
  const d = {};
  if (filterDraft.dGeography.length) d.geography = filterDraft.dGeography.map(String);
  if (filterDraft.dPeriodicity.length) d.periodicity = filterDraft.dPeriodicity.map(String);
  const c = {};
  for (const col of facetComponentsForC.value) {
    const arr = filterDraft.c[col.name];
    if (arr?.length) c[col.name] = arr.map(String);
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

function urlHasTableFilterParams() {
  if (typeof window === 'undefined') return false;
  const q = new URLSearchParams(window.location.search || '');
  return urlHasIndicatorFilterParams(q);
}

function applyTableQueryFromUrl() {
  if (typeof window === 'undefined') return;
  const q = new URLSearchParams(window.location.search || '');
  readIndicatorFiltersFromQuery(q, {
    filterDraft,
    geographyComponent: geographyComponent.value,
    periodicityComponent: periodicityComponent.value,
    facetComponentsForC: facetComponentsForC.value,
  });
}

function readLayoutFromUrl() {
  if (typeof window === 'undefined') return null;
  const q = new URLSearchParams(window.location.search || '');
  return readTableLayoutFromQuery(q, {
    eligibleKeys: layoutEligibleDimensions.value.map((d) => d.key).filter(Boolean),
  });
}

function buildTableQueryParams() {
  const params = new URLSearchParams();
  const filterParams = buildIndicatorFilterQueryParams({
    filterDraft,
    geographyComponent: geographyComponent.value,
    periodicityComponent: periodicityComponent.value,
    facetComponentsForC: facetComponentsForC.value,
    facetShowsFilter: facetShowsFilterForComponent,
  });
  const layoutParams = buildTableLayoutQueryParams(layoutApplied.value ? appliedLayout.value : null);

  for (const [key, value] of filterParams.entries()) {
    params.append(key, value);
  }
  for (const [key, value] of layoutParams.entries()) {
    params.append(key, value);
  }

  return params;
}

const embedTablePageUrl = computed(() => {
  const n = Number(studySid.value);
  const base = siteUrl.value.replace(/\/?$/, '/');
  if (!Number.isFinite(n) || n <= 0 || !base) return '';
  const path = `embed/catalog/${n}/table`;
  const qs = buildTableQueryParams().toString();
  return qs ? `${base}${path}?${qs}` : `${base}${path}`;
});

const exportHtmlUrl = computed(() => {
  if (!layoutApplied.value || isIndicatorEmbed.value) return '';
  const n = Number(studySid.value);
  const base = siteUrl.value.replace(/\/?$/, '/');
  if (!Number.isFinite(n) || n <= 0 || !base) return '';
  const qs = buildTableQueryParams();
  qs.set('format', 'html');
  return `${base}catalog/${n}/indicator-table-export?${qs.toString()}`;
});

const exportXlsxUrl = computed(() => {
  if (!layoutApplied.value || isIndicatorEmbed.value) return '';
  const n = Number(studySid.value);
  const base = siteUrl.value.replace(/\/?$/, '/');
  if (!Number.isFinite(n) || n <= 0 || !base) return '';
  const qs = buildTableQueryParams();
  qs.set('format', 'xlsx');
  return `${base}catalog/${n}/indicator-table-export?${qs.toString()}`;
});

function writeTableQueryToUrl() {
  if (typeof window === 'undefined' || urlSyncSuspended.value) return;
  if (!schema.value) return;
  const url = new URL(window.location.href);
  const sp = new URLSearchParams(url.search);
  stripChartOnlyQueryKeys(sp);
  stripIndicatorFilterQueryKeys(sp);
  stripTableLayoutQueryKeys(sp);

  const add = buildTableQueryParams();
  for (const [key, value] of add.entries()) {
    sp.append(key, value);
  }

  url.search = sp.toString();
  window.history.replaceState(window.history.state, '', url.toString());
}

const debouncedWriteTableQueryToUrl = debounce(writeTableQueryToUrl, 260);

function applyDefaultYearRange() {
  const b = schema.value?.reporting_year_bounds;
  if (!b || b.min == null || b.max == null) return;
  if (!String(filterDraft.from ?? '').trim()) filterDraft.from = String(b.min);
  if (!String(filterDraft.to ?? '').trim()) filterDraft.to = String(b.max);
}

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

function createDefaultLayout() {
  return buildDefaultTableLayout(layoutEligibleDimensions.value, {
    geographyKey: geographyLayoutKey.value,
    studyGeographyCount: studyGeographyCount.value,
    studySingletonKeys: new Set(layoutPanelSingletonKeys.value),
  });
}

async function loadCodelistsForSchema() {
  const byName = {};
  const observed = await fetchFilterOptions();
  const filters = Array.isArray(observed?.filters) ? observed.filters : [];
  for (const facet of filters) {
    const componentName = String(facet?.component_name ?? '').trim();
    if (!componentName) continue;
    const optionsRaw = Array.isArray(facet?.options) ? facet.options : [];
    byName[componentName] = optionsRaw
      .map((it) => {
        const code = String(it?.code ?? '').trim();
        if (!code) return null;
        const label = String(it?.label ?? '').trim();
        return { title: label !== '' ? label : code, value: code };
      })
      .filter(Boolean);
  }
  for (const c of componentsSorted.value) {
    if (!c?.name) continue;
    if (!['geography', 'periodicity', 'dimension'].includes(c.column_type)) continue;
    if (!byName[c.name]) byName[c.name] = [];
  }
  codelistSelectItems.value = byName;
}

async function commitFiltersAndMaybeLoadTable({ loadTable = false } = {}) {
  if (!tableSliceSelectionComplete.value) {
    activeFilters.value = null;
    filteredObservationCount.value = 0;
    chartRecords.value = [];
    if (loadTable) layoutApplied.value = false;
    return false;
  }
  activeFilters.value = buildApiFiltersFromDraft();
  filteredObservationCount.value = await fetchObservationCount(activeFilters.value);
  if (loadTable && layoutApplied.value) {
    await reloadTableData();
  }
  return true;
}

async function reloadTableData() {
  if (!activeFilters.value) {
    chartRecords.value = [];
    chartMetadata.value = {};
    return;
  }
  tableLoading.value = true;
  try {
    const res = await fetchChartData({
      filters: activeFilters.value,
      limit: CHART_API_LIMIT,
    });
    chartRecords.value = res.records || [];
    chartMetadata.value = res.metadata || {};
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Could not load table data.', 'warning');
    chartRecords.value = [];
    chartMetadata.value = {};
  } finally {
    tableLoading.value = false;
  }
}

async function onApplyFilters() {
  if (!schema.value) return;
  if (!tableSliceSelectionComplete.value) {
    setMessage('Select at least one geography before applying filters.', 'warning');
    await commitFiltersAndMaybeLoadTable();
    return;
  }
  applyLoading.value = true;
  try {
    await commitFiltersAndMaybeLoadTable({ loadTable: true });
    writeTableQueryToUrl();
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Could not apply filters.', 'warning');
  } finally {
    applyLoading.value = false;
  }
}

async function onApplyLayout(layout) {
  if (!filtersCommitted.value) {
    setMessage('Apply filters before applying a table layout.', 'warning');
    return;
  }
  appliedLayout.value = normalizeTableLayout(layout);
  layoutApplied.value = true;
  await reloadTableData();
  writeTableQueryToUrl();
}

function onCancelLayout() {
  if (!appliedLayout.value) {
    appliedLayout.value = createEmptyTableLayout();
    layoutApplied.value = false;
  }
}

async function bootstrapTableOnLoad() {
  if (!urlHasTableFilterParams()) {
    applyDefaultTableFilters(filterDraft, {
      geographyComponent: geographyComponent.value,
      periodicityComponent: periodicityComponent.value,
      facetComponentsForC: facetComponentsForC.value,
      codelistSelectItems: codelistSelectItems.value,
    });
  }
  ensureSingleOptionFacetDrafts();
  if (!urlHasTableFilterParams()) {
    applyDefaultYearRange();
  }
  syncFilterExpandedPanelsOpen();

  if (!(await commitFiltersAndMaybeLoadTable())) {
    return;
  }

  const layoutFromUrl = readLayoutFromUrl();
  if (layoutFromUrl) {
    appliedLayout.value = layoutFromUrl;
    layoutApplied.value = true;
  } else {
    const defaultLayout = normalizeTableLayout(createDefaultLayout());
    appliedLayout.value = defaultLayout;
    layoutApplied.value = true;
  }
  await reloadTableData();
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
    schema.value = await fetchSchema();
    syncFilterDraftCShape();
    await loadCodelistsForSchema();
    applyTableQueryFromUrl();
    await bootstrapTableOnLoad();
  } catch (e) {
    fatalError.value = e?.response?.data?.message || e?.message || 'Could not load indicator data.';
    schema.value = null;
  } finally {
    pageLoading.value = false;
    await nextTick();
    urlSyncSuspended.value = false;
    writeTableQueryToUrl();
  }
}

watch(
  () => filterDraftSnapshot(),
  () => {
    if (urlSyncSuspended.value || pageLoading.value || !schema.value) return;
    debouncedWriteTableQueryToUrl();
  }
);

watch(
  () => [layoutApplied.value, layoutSnapshot()],
  () => {
    if (urlSyncSuspended.value || pageLoading.value || !schema.value) return;
    debouncedWriteTableQueryToUrl();
  }
);

onMounted(() => {
  document.addEventListener('keydown', onDocumentKeydown);
  loadAll();
});

onUnmounted(() => {
  document.removeEventListener('keydown', onDocumentKeydown);
  debouncedWriteTableQueryToUrl.cancel();
});
</script>

<style scoped>
.catalog-indicator-page {
  max-width: 1320px;
  margin-inline: auto;
  padding-block: 0.5rem 1.5rem;
}

.catalog-indicator-page--window {
  max-width: none;
  padding-inline: 0;
  padding-block: 0;
}

.main-layout {
  align-items: stretch;
}

.table-tab-layout {
  margin: 0;
  align-items: flex-start;
}

.table-filter-col {
  min-width: 0;
  align-self: flex-start;
}

.table-main-col {
  min-width: 0;
  padding: 0.75rem;
  align-self: flex-start;
}

.table-tab-layout--with-filters > .table-main-col {
  padding-top: 0;
  padding-left: 0;
}

.table-workspace {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.table-workspace--window {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgb(var(--v-theme-surface));
  padding: 0.75rem;
  box-sizing: border-box;
}

.table-chrome {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 8px;
  background: rgb(var(--v-theme-surface));
  margin-bottom: 0.75rem;
  overflow: hidden;
}

.table-chrome__actions {
  padding: 0.5rem 0.65rem;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.table-chrome__meta {
  padding: 0.5rem 0.75rem 0.6rem;
  min-width: 0;
}

.table-chrome__title {
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.35;
  letter-spacing: 0.01em;
}

.table-chrome__subtitle {
  font-size: 0.75rem;
  line-height: 1.4;
  color: rgba(var(--v-theme-on-surface), 0.68);
  margin-top: 0.15rem;
}

.table-chrome__window-btn {
  color: rgba(var(--v-theme-on-surface), 0.75);
}

.table-toolbar-cog-btn {
  flex-shrink: 0;
  min-width: 30px !important;
  width: 30px;
}

.table-toolbar-cog-btn :deep(.v-icon) {
  opacity: 0.9;
}

.table-export-list :deep(.v-list-item) {
  --v-list-prepend-gap: 8px;
}

.table-layout-panel-slot {
  flex-shrink: 0;
}

.table-viewport-shell {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

.table-viewport {
  overflow: auto;
  -webkit-overflow-scrolling: touch;
}

.table-data-footer {
  margin-top: 0.5rem;
  padding-inline: 0.15rem;
}

.table-workspace--window .table-chrome {
  flex-shrink: 0;
}

.table-workspace--window .table-viewport-shell {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.table-workspace--window .table-viewport {
  max-height: none;
  flex: 1 1 auto;
  min-height: 0;
}

.table-empty-state {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.table-main-panel {
  min-height: 0;
}
</style>
