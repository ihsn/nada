<template>
  <div class="catalog-indicator-page">
    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-6 rounded-s" height="3" />

    <v-alert v-if="fatalError" type="error" variant="tonal" class="mb-6" rounded="lg" prominent density="comfortable">
      {{ fatalError }}
    </v-alert>

    <template v-else-if="schema">
      <div class="data-api-main">
        <h1 class="text-h5 font-weight-semibold mb-3">Data API</h1>
        <p v-if="studyAbstract" class="text-body-2 text-medium-emphasis mb-6 study-abstract">{{ studyAbstract }}</p>

        <div class="section-block api-usage-section">
          <IndicatorDataApiReferenceCard
            :data-base-url="publicTimeseriesDataBaseUrl"
            :ui="ui"
            :show-bulk-downloads-api-link="registryFiles.length > 0"
            :bulk-downloads-catalog-url="downloadsCatalogUrl"
            :example-limit="OBSERVATIONS_PAGE_LIMIT"
          />
        </div>

        <div class="section-block">
          <StudyRegistryBulkDownloads
            :title="ui.bulkDownloadsHeading"
            :loading="registryLoading"
            :files="registryFiles"
            :col-file="ui.bulkFileCol"
            :col-date="ui.bulkDateCol"
            :col-actions="ui.bulkActionsCol"
            :download-label="ui.download"
            :link-label="ui.link"
            :loading-label="ui.loading"
          />
        </div>

        <div class="explorer-block section-block">
          <div class="d-flex flex-wrap align-center gap-2 mb-3">
            <h2 class="text-h6 mb-0">{{ ui.dataExplorerHeading }}</h2>
            <v-spacer />
            <v-btn
              icon
              variant="text"
              size="small"
              :title="ui.apiOptionsLabel"
              :aria-label="ui.apiOptionsLabel"
              :aria-expanded="showFiltersPanel"
              @click="showFiltersPanel = !showFiltersPanel"
            >
              <v-icon size="18">{{ showFiltersPanel ? 'mdi-cog' : 'mdi-cog-outline' }}</v-icon>
            </v-btn>
          </div>

          <v-expand-transition>
            <div v-show="showFiltersPanel" class="w-100">
              <v-sheet class="grid-filters-section w-100 pa-4 mb-4" rounded="lg" border>
                <div class="text-subtitle-2 mb-3">{{ ui.filtersHeading }}</div>

              <div v-if="timePeriodFilterVisible" class="explorer-time-block">
                <div class="text-caption text-medium-emphasis mb-1">{{ facetLabel(timePeriodComponent) }}</div>
                <template v-if="timePeriodCodelistItems.length">
                  <v-select
                    v-model="filterDraft.dTimePeriod"
                    class="w-100"
                    :items="timePeriodCodelistItems"
                    item-title="title"
                    item-value="value"
                    multiple
                    chips
                    closable-chips
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </template>
                <template v-else-if="timePeriodUsesReportingYearFields">
                  <p v-if="reportingYearBoundsHintText" class="text-caption text-medium-emphasis mb-2">
                    {{ reportingYearBoundsHintText }}
                  </p>
                  <v-row dense class="explorer-reporting-year-row">
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model="filterDraft.reportingYearFrom"
                        :label="ui.reportingYearFromLabel"
                        type="text"
                        inputmode="numeric"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="12" sm="6">
                      <v-text-field
                        v-model="filterDraft.reportingYearTo"
                        :label="ui.reportingYearToLabel"
                        type="text"
                        inputmode="numeric"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                    </v-col>
                  </v-row>
                </template>
                <p v-else class="text-body-2 text-medium-emphasis mb-0">{{ ui.timePeriodNoFacets }}</p>
              </div>

              <v-row dense class="facet-fields-row">
                <v-col v-if="geographyFilterVisible" cols="12" sm="6" class="facet-field-col">
                  <div class="text-caption text-medium-emphasis mb-1">{{ facetLabel(geographyComponent) }}</div>
                  <v-select
                    v-model="filterDraft.dGeography"
                    class="w-100"
                    :items="selectItemsFor(geographyComponent?.name)"
                    item-title="title"
                    item-value="value"
                    multiple
                    chips
                    closable-chips
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </v-col>

                <v-col v-if="periodicityFilterVisible" cols="12" sm="6" class="facet-field-col">
                  <div class="text-caption text-medium-emphasis mb-1">{{ facetLabel(periodicityComponent) }}</div>
                  <v-select
                    v-model="filterDraft.dPeriodicity"
                    class="w-100"
                    :items="selectItemsFor(periodicityComponent?.name)"
                    item-title="title"
                    item-value="value"
                    multiple
                    chips
                    closable-chips
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </v-col>

                <v-col
                  v-for="col in facetComponentsForC"
                  :key="col.name"
                  cols="12"
                  sm="6"
                  class="facet-field-col"
                >
                  <div class="text-caption text-medium-emphasis mb-1">{{ facetLabel(col) }}</div>
                  <v-select
                    v-model="filterDraft.c[col.name]"
                    class="w-100"
                    :items="selectItemsFor(col.name)"
                    item-title="title"
                    item-value="value"
                    multiple
                    chips
                    closable-chips
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </v-col>
              </v-row>

              <p
                v-if="
                  !geographyFilterVisible &&
                  !periodicityFilterVisible &&
                  !timePeriodFilterVisible &&
                  facetComponentsForC.length === 0
                "
                class="text-body-2 text-medium-emphasis mb-4"
              >
                {{ ui.noFacetFilters }}
              </p>

                <v-divider class="mb-4" />

                <div class="d-flex justify-end">
                  <v-btn
                    class="text-none filter-apply-outlined"
                    variant="outlined"
                    rounded="xl"
                    min-width="12rem"
                    @click="applyExplorerFilters"
                  >
                    {{ ui.applyFilters }}
                  </v-btn>
                </div>
              </v-sheet>

              <v-sheet v-if="explorerObservationsQueryUrl" class="explorer-query-section pa-4 mb-4" rounded="lg" border>
                <div class="text-subtitle-2 mb-3">Data API query</div>
                <div class="explorer-query-line d-flex align-start gap-1">
                  <code class="explorer-query-code text-break">{{ explorerObservationsQueryUrl }}</code>
                  <v-btn
                    icon
                    size="x-small"
                    variant="text"
                    :aria-label="ui.copyUrl"
                    class="align-self-start flex-shrink-0"
                    @click="copyExplorerQueryUrl"
                  >
                    <v-icon size="18">mdi-content-copy</v-icon>
                  </v-btn>
                  <v-btn
                    icon
                    size="x-small"
                    variant="text"
                    :aria-label="ui.openUrl"
                    class="align-self-start flex-shrink-0"
                    :href="explorerObservationsQueryUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    <v-icon size="18">mdi-open-in-new</v-icon>
                  </v-btn>
                </div>
              </v-sheet>
            </div>
          </v-expand-transition>

          <div class="d-flex flex-wrap justify-space-between align-center gap-2 mb-2">
            <span class="text-body-2 text-medium-emphasis">{{ rangeSummary }}</span>
            <span class="text-body-2">
              <span class="font-weight-medium">{{ ui.totalLabel }}:</span> {{ observationListTotal }}
            </span>
          </div>

          <v-sheet rounded="lg" border class="data-shell overflow-hidden w-100">
            <v-data-table
              v-model:sort-by="dataTableSortBy"
              :headers="tableHeaders"
              :items="displayRows"
              :items-per-page="-1"
              :loading="obsLoading"
              :custom-key-sort="obsDataTableCustomSort"
              density="comfortable"
              class="elevation-0 obs-data-table w-100"
              fixed-header
              hover
              hide-default-footer
              must-sort
            >
              <template v-for="col in tableHeaders" :key="'cell-' + col.key" #[`item.${col.key}`]="{ value }">
                <span class="obs-cell-clamp" :title="cellTooltipTitle(value)">{{ value }}</span>
              </template>
            </v-data-table>
          </v-sheet>

          <div class="explorer-pagination-row d-flex align-start justify-space-between gap-3 mt-4 pa-1">
            <span class="explorer-range-summary text-body-2 text-medium-emphasis">{{ rangeSummary }}</span>
            <v-pagination
              class="explorer-pagination"
              :model-value="currentPage"
              :length="totalPages"
              :total-visible="7"
              density="comfortable"
              rounded="circle"
              :disabled="obsLoading || observationListTotal <= OBSERVATIONS_PAGE_LIMIT"
              @update:model-value="goToPage"
            />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, inject, onMounted, watch } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { usePublicTimeseriesApi, mergeObservationFiltersIntoParams } from '../composables/usePublicTimeseriesApi';
import IndicatorDataApiReferenceCard from '../components/IndicatorDataApiReferenceCard.vue';
import StudyRegistryBulkDownloads from '../components/StudyRegistryBulkDownloads.vue';

defineOptions({ name: 'IndicatorDataApiTab' });

/** Fixed page size for GET …/data (no UI control). */
const OBSERVATIONS_PAGE_LIMIT = 25;

const CHART_DATA_FONT_FAMILY =
  "ui-monospace, 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', monospace";

const setMessage = inject('setMessage', () => {});

const { config, siteUrl } = useAppConfig();
const studyIdno = computed(() => String(config.value?.studyIdno ?? '').trim());
const studyTitle = computed(() => String(config.value?.studyTitle ?? '').trim());
const studyAbstract = computed(() => String(config.value?.studyAbstract ?? '').trim());

const ui = computed(() => {
  const raw = config.value?.indicatorDataApiUi;
  return raw && typeof raw === 'object' ? raw : {};
});

const { fetchSchema, fetchObservations, fetchFilterOptions, dataPath } = usePublicTimeseriesApi(studyIdno);

const publicTimeseriesDataBaseUrl = computed(() => dataPath());

const downloadsCatalogUrl = computed(() => {
  const id = studyIdno.value;
  const base = String(siteUrl.value || '').replace(/\/$/, '');
  if (!id || !base) return '';
  return `${base}/api/downloads/${encodeURIComponent(id)}/files?type=data`;
});

const registryFiles = ref([]);
const registryLoading = ref(false);

const showFiltersPanel = ref(false);

const pageLoading = ref(true);
const obsLoading = ref(false);
const fatalError = ref('');
const schema = ref(null);

const codelistSelectItems = ref({});

const filterDraft = reactive({
  dGeography: [],
  dPeriodicity: [],
  dTimePeriod: [],
  /** When the DSD time_period has no observed codelist facets, filter by Mongo `_ts_year` via API `from` / `to`. */
  reportingYearFrom: '',
  reportingYearTo: '',
  c: {},
});

const dataTableSortBy = ref([{ key: 'period_start', order: 'asc' }]);
const offset = ref(0);
const observationListTotal = ref(0);
const observationRows = ref([]);

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
const timePeriodComponent = computed(() => componentsSorted.value.find((c) => c?.column_type === 'time_period') || null);

/** Observed codes from filter-options + counts (often empty for time_period when there is no codelist facet). */
const timePeriodCodelistItems = computed(() => {
  const n = timePeriodComponent.value?.name;
  if (!n) return [];
  return codelistSelectItems.value[n] || [];
});

const reportingYearBoundsNumeric = computed(() => {
  const rb = schema.value?.reporting_year_bounds;
  if (!rb || rb.min == null || rb.max == null) return null;
  const mn = typeof rb.min === 'string' && /^\d+$/.test(String(rb.min).trim()) ? Number(String(rb.min).trim()) : Number(rb.min);
  const mx = typeof rb.max === 'string' && /^\d+$/.test(String(rb.max).trim()) ? Number(String(rb.max).trim()) : Number(rb.max);
  if (!Number.isFinite(mn) || !Number.isFinite(mx) || mn <= 0 || mx <= 0 || mn > mx) return null;
  return { min: Math.trunc(mn), max: Math.trunc(mx) };
});

const timePeriodUsesReportingYearFields = computed(
  () => Boolean(timePeriodComponent.value && timePeriodCodelistItems.value.length === 0 && reportingYearBoundsNumeric.value)
);

const reportingYearBoundsHintText = computed(() => {
  const b = reportingYearBoundsNumeric.value;
  const tpl = String(ui.value?.reportingYearBoundsHint || '').trim();
  if (!b || !tpl) return '';
  return tpl.replace('{min}', String(b.min)).replace('{max}', String(b.max));
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

function facetShowsFilter(componentName) {
  const key = String(componentName || '');
  const items = codelistSelectItems.value[key];
  if (items === undefined) return true;
  return items.length > 1;
}

/** Show filter row whenever the DSD defines the component (even with a single observed code). */
const geographyFilterVisible = computed(() => Boolean(geographyComponent.value));
const periodicityFilterVisible = computed(() => Boolean(periodicityComponent.value));
const timePeriodFilterVisible = computed(() => Boolean(timePeriodComponent.value));

const catalogSliceRequired = computed(() => {
  if (!schema.value) return false;
  const geoNeed = Boolean(geographyComponent.value && facetShowsFilter(geographyComponent.value.name));
  const perNeed = Boolean(periodicityComponent.value && facetShowsFilter(periodicityComponent.value.name));
  const tpNeed = Boolean(timePeriodComponent.value && facetShowsFilter(timePeriodComponent.value.name));
  const dimNeed = facetComponentsForC.value.some((c) => facetShowsFilter(c.name));
  return geoNeed || perNeed || tpNeed || dimNeed;
});

const catalogSliceSelectionComplete = computed(() => {
  if (!catalogSliceRequired.value) return true;
  const geo = geographyComponent.value;
  if (geo && facetShowsFilter(geo.name) && (!filterDraft.dGeography || filterDraft.dGeography.length === 0)) {
    return false;
  }
  const per = periodicityComponent.value;
  if (per && facetShowsFilter(per.name) && (!filterDraft.dPeriodicity || filterDraft.dPeriodicity.length === 0)) {
    return false;
  }
  const tp = timePeriodComponent.value;
  if (tp && facetShowsFilter(tp.name) && (!filterDraft.dTimePeriod || filterDraft.dTimePeriod.length === 0)) {
    return false;
  }
  for (const col of facetComponentsForC.value) {
    if (!facetShowsFilter(col.name)) continue;
    const arr = filterDraft.c[col.name];
    if (!arr || arr.length === 0) return false;
  }
  return true;
});

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

function facetLabel(c) {
  if (!c) return '';
  const directKeys = ['label', 'title', 'field_label', 'field_title', 'display_name', 'displayName', 'caption'];
  for (const k of directKeys) {
    const v = componentPropString(c, k);
    if (v) return v;
  }
  const meta = parseComponentMetadata(c.metadata);
  if (meta) {
    const metaKeys = ['label', 'title', 'field_title', 'field_label', 'display_name', 'displayName', 'concept_title', 'conceptTitle'];
    for (const k of metaKeys) {
      const v = componentPropString(meta, k);
      if (v) return v;
    }
  }
  return componentPropString(c, 'name');
}

function selectItemsFor(componentName) {
  const key = String(componentName || '').trim();
  if (!key) return [];
  return codelistSelectItems.value[key] || [];
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

function buildDataApiFiltersFromDraft() {
  const d = {};
  if (filterDraft.dGeography.length) d.geography = filterDraft.dGeography.map(String);
  if (filterDraft.dPeriodicity.length) d.periodicity = filterDraft.dPeriodicity.map(String);
  if (filterDraft.dTimePeriod.length) d.time_period = filterDraft.dTimePeriod.map(String);
  const c = {};
  for (const col of facetComponentsForC.value) {
    const arr = filterDraft.c[col.name];
    if (arr && arr.length) {
      c[col.name] = arr.map(String);
    }
  }
  const out = {};
  if (Object.keys(d).length) out.d = d;
  if (Object.keys(c).length) out.c = c;
  return out;
}

async function loadCodelistsForSchema() {
  codelistSelectItems.value = {};
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
    if (!['geography', 'periodicity', 'time_period', 'dimension'].includes(c.column_type)) continue;
    if (!byName[c.name]) {
      byName[c.name] = [];
    }
  }
  codelistSelectItems.value = byName;
}

const rangeSummary = computed(() => {
  const tpl = String(ui.value?.showingTemplate || 'Showing {from}–{to} of {total}');
  const total = observationListTotal.value;
  if (!observationRows.value.length) {
    return tpl.replace('{from}', '0').replace('{to}', '0').replace('{total}', String(total));
  }
  const from = offset.value + 1;
  const to = offset.value + observationRows.value.length;
  return tpl.replace('{from}', String(from)).replace('{to}', String(to)).replace('{total}', String(total));
});

const totalPages = computed(() => {
  const total = Number(observationListTotal.value) || 0;
  return Math.max(1, Math.ceil(total / OBSERVATIONS_PAGE_LIMIT));
});

const currentPage = computed(() => {
  return Math.floor(offset.value / OBSERVATIONS_PAGE_LIMIT) + 1;
});

function formatCell(val) {
  if (val === null || val === undefined) return '—';
  if (typeof val === 'object') {
    if (val.$date != null) return String(val.$date);
    return JSON.stringify(val);
  }
  return String(val);
}

function cellTooltipTitle(value) {
  const s = value == null ? '' : String(value);
  if (s.length < 120) return undefined;
  return s;
}

const tableHeaders = computed(() => {
  const keys = ['sid', 'period_start', 'period_end', 'reporting_year', 'reporting_freq'];
  for (const c of componentsSorted.value) {
    if (c?.name && !keys.includes(c.name)) keys.push(c.name);
  }
  return keys.map((k) => ({ title: k, key: k, sortable: true }));
});

const obsDataTableCustomSort = computed(() => {
  const o = {};
  for (const h of tableHeaders.value) {
    o[h.key] = () => 0;
  }
  return o;
});

const displayRows = computed(() => {
  const headers = tableHeaders.value;
  return observationRows.value.map((row) => {
    const out = {};
    for (const h of headers) {
      out[h.key] = formatCell(row[h.key]);
    }
    return out;
  });
});

function currentObservationSortParams() {
  const first = dataTableSortBy.value[0];
  const key = first && typeof first.key === 'string' && first.key ? first.key : 'period_start';
  const order = first && first.order === 'desc' ? 'desc' : 'asc';
  return { sort_by: key, sort: order };
}

function observationFiltersPayload() {
  const f = buildDataApiFiltersFromDraft();
  const fromTrim = String(filterDraft.reportingYearFrom ?? '').trim();
  const toTrim = String(filterDraft.reportingYearTo ?? '').trim();
  if (fromTrim) f.from = fromTrim;
  if (toTrim) f.to = toTrim;
  return Object.keys(f).length ? f : undefined;
}

/** Full GET URL for the current explorer state (matches `fetchObservations` except optional `_nocache`). */
const explorerObservationsQueryUrl = computed(() => {
  const root = String(publicTimeseriesDataBaseUrl.value || '').replace(/\/$/, '');
  if (!root) return '';
  const { sort_by, sort } = currentObservationSortParams();
  const params = {
    limit: OBSERVATIONS_PAGE_LIMIT,
    offset: offset.value,
    sort,
  };
  const sb = String(sort_by || '').trim();
  if (sb) params.sort_by = sb;
  mergeObservationFiltersIntoParams(params, observationFiltersPayload());
  const sp = new URLSearchParams();
  for (const [k, v] of Object.entries(params)) {
    if (v === undefined || v === null || v === '') continue;
    sp.set(k, String(v));
  }
  const q = sp.toString();
  return q ? `${root}?${q}` : `${root}`;
});

async function copyExplorerQueryUrl() {
  const s = String(explorerObservationsQueryUrl.value || '');
  if (!s) return;
  try {
    if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(s);
    else throw new Error('no clipboard');
    setMessage(String(ui.value?.copied || 'Copied.'), 'info');
  } catch {
    setMessage(String(ui.value?.copyFailed || 'Could not copy.'), 'error');
  }
}

async function reloadObservations() {
  if (!studyIdno.value) return;
  obsLoading.value = true;
  try {
    const { sort_by, sort } = currentObservationSortParams();
    const res = await fetchObservations({
      limit: OBSERVATIONS_PAGE_LIMIT,
      offset: offset.value,
      sort,
      sort_by,
      filters: observationFiltersPayload(),
    });
    observationRows.value = res.data || [];
    if (typeof res.total === 'number' && res.total >= 0) {
      observationListTotal.value = res.total;
    }
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Could not load data.', 'error');
    observationRows.value = [];
  } finally {
    obsLoading.value = false;
  }
}

function goToPage(pageNumber) {
  const page = Number(pageNumber);
  if (!Number.isFinite(page)) return;
  const clampedPage = Math.min(Math.max(1, Math.trunc(page)), totalPages.value);
  const nextOffset = (clampedPage - 1) * OBSERVATIONS_PAGE_LIMIT;
  if (nextOffset === offset.value) return;
  offset.value = nextOffset;
  reloadObservations();
}

function hasReportingYearDraft() {
  return Boolean(String(filterDraft.reportingYearFrom ?? '').trim() || String(filterDraft.reportingYearTo ?? '').trim());
}

function applyExplorerFilters() {
  /** API accepts `from`/`to` alone on `_ts_year`; do not block when only that slice is set. */
  const allowApplyWithReportingYearsOnly =
    timePeriodUsesReportingYearFields.value && hasReportingYearDraft();
  if (!allowApplyWithReportingYearsOnly && catalogSliceRequired.value && !catalogSliceSelectionComplete.value) {
    setMessage(String(ui.value?.filtersIncomplete || 'Choose the required filter values before continuing.'), 'warning');
    return;
  }
  offset.value = 0;
  reloadObservations();
}

watch(
  dataTableSortBy,
  () => {
    offset.value = 0;
    reloadObservations();
  },
  { deep: true }
);

watch(
  () => schema.value?.sid,
  () => {
    if (schema.value) {
      syncFilterDraftCShape();
    }
  }
);

async function loadRegistryDownloads() {
  if (!studyIdno.value || !siteUrl.value) {
    registryLoading.value = false;
    return;
  }
  registryLoading.value = true;
  try {
    const base = String(siteUrl.value).replace(/\/$/, '');
    const { data } = await axios.get(`${base}/api/downloads/${encodeURIComponent(studyIdno.value)}/files`, {
      params: { type: 'data' },
    });
    if (data && data.status === 'success' && Array.isArray(data.files)) {
      registryFiles.value = data.files;
    } else {
      registryFiles.value = [];
    }
  } catch {
    registryFiles.value = [];
  } finally {
    registryLoading.value = false;
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
  try {
    const sch = await fetchSchema();
    schema.value = sch;
    filterDraft.reportingYearFrom = '';
    filterDraft.reportingYearTo = '';
    syncFilterDraftCShape();
    await loadCodelistsForSchema();
    offset.value = 0;
    await Promise.all([reloadObservations(), loadRegistryDownloads()]);
  } catch (e) {
    fatalError.value = e?.response?.data?.message || e?.message || 'Could not load indicator data.';
    schema.value = null;
  } finally {
    pageLoading.value = false;
  }
}

onMounted(() => {
  loadAll();
});
</script>

<style scoped>
.catalog-indicator-page {
  width: 100%;
  padding-block: 0.5rem 1.5rem;
}

@media (min-width: 960px) {
  .catalog-indicator-page {
    padding-block: 0.75rem 2rem;
  }
}

.data-api-main {
  width: 100%;
}

.section-block + .section-block {
  margin-top: 1.5rem;
}

.api-usage-section + .section-block {
  margin-top: 0.75rem;
}

.study-abstract {
  white-space: pre-line;
}

.grid-filters-section {
  width: 100%;
  background: rgb(var(--v-theme-surface));
}

.explorer-time-block {
  width: 100%;
  margin-bottom: 1.25rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.facet-fields-row {
  row-gap: 0.75rem;
}

.facet-field-col {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.explorer-reporting-year-row {
  max-width: min(100%, 36rem);
}

.explorer-query-section {
  width: 100%;
  background: rgb(var(--v-theme-surface));
}

.explorer-query-line {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 0.5rem;
  padding: 0.5rem 0.35rem 0.5rem 0.65rem;
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.explorer-query-code {
  display: block;
  font-family: ui-monospace, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
  font-size: 0.75rem;
  line-height: 1.45;
  min-width: 0;
}

.data-shell {
  background: rgb(var(--v-theme-surface));
}

.obs-data-table {
  overflow: hidden;
}

.obs-data-table :deep(.v-table__wrapper) {
  max-height: min(62vh, 780px);
  overflow-y: auto;
  overflow-x: auto;
  min-height: 0;
  scrollbar-gutter: stable;
}

.obs-data-table :deep(th) {
  font-weight: 600 !important;
  font-size: 0.75rem !important;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55) !important;
}

.obs-data-table :deep(tbody .v-data-table__td) {
  vertical-align: top;
  max-width: 22rem;
}

.obs-data-table :deep(tbody td) {
  font-family: v-bind(CHART_DATA_FONT_FAMILY);
  font-size: 0.75rem;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.015em;
}

.obs-cell-clamp {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  overflow: hidden;
  white-space: normal;
  word-break: break-word;
  overflow-wrap: anywhere;
  min-width: 0;
}

.explorer-pagination :deep(.v-pagination) {
  margin: 0;
}

.explorer-range-summary {
  line-height: 1.2;
  padding-top: 0.2rem;
}

.filter-apply-outlined.v-btn--variant-outlined {
  border-color: rgba(var(--v-theme-on-surface), 0.12) !important;
  border-radius: 14px !important;
}

.filter-apply-outlined.v-btn--variant-outlined:not(.v-btn--disabled):hover {
  border-color: rgba(var(--v-theme-on-surface), 0.22) !important;
}
</style>
