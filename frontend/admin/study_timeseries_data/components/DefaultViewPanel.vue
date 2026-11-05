<template>
  <div class="default-view-panel">
    <div class="d-flex flex-wrap align-center dv-inline">
      <span class="text-body-2 text-medium-emphasis">
        What the public chart and table pages show first, when the page link has no filters.
        Everything is optional; codes without data are skipped.
      </span>
      <v-spacer />
      <v-btn
        v-if="chartPageUrl"
        :href="chartPageUrl"
        target="_blank"
        rel="noopener"
        size="small"
        variant="text"
        prepend-icon="mdi-chart-line"
        class="text-none"
      >
        Open chart
      </v-btn>
      <v-btn
        v-if="tablePageUrl"
        :href="tablePageUrl"
        target="_blank"
        rel="noopener"
        size="small"
        variant="text"
        prepend-icon="mdi-table"
        class="text-none"
      >
        Open table
      </v-btn>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="my-3" />
    <v-alert v-if="loadError" type="warning" density="compact" variant="tonal" class="my-3">
      {{ loadError }}
    </v-alert>

    <template v-if="!loading && !loadError">
      <!-- Copy settings from a chart or table page link -->
      <div class="text-caption text-medium-emphasis dv-label">Copy from a chart or table link</div>
      <div class="d-flex align-start dv-inline">
        <v-text-field
          v-model="linkDraft"
          placeholder="Paste the address of a chart or table page set up the way you want"
          density="compact"
          variant="outlined"
          hide-details
          :disabled="saving"
          @keydown.enter.prevent="copyFromLink"
        />
        <v-btn variant="tonal" class="text-none" :disabled="!linkDraft.trim() || saving" @click="copyFromLink">
          Copy settings
        </v-btn>
      </div>

      <div class="text-subtitle-2 font-weight-medium dv-section-title">Filters</div>

      <template v-if="geographyComponent">
        <div class="text-caption text-medium-emphasis dv-label">{{ componentTitle(geographyComponent) }}</div>
        <v-autocomplete
          v-model="form.geography"
          :items="codeItems(geographyComponent.name)"
          multiple
          chips
          closable-chips
          clearable
          density="compact"
          variant="outlined"
          placeholder="First available"
          hint="All selected codes that have data are shown, e.g. World alone or World and regions."
          persistent-hint
          :disabled="saving"
        />
      </template>

      <template v-for="col in filterComponents" :key="col.name">
        <div class="text-caption text-medium-emphasis dv-label">{{ componentTitle(col) }}</div>
        <v-autocomplete
          v-model="form.dimensions[col.name]"
          :items="codeItems(col.name)"
          multiple
          chips
          closable-chips
          clearable
          density="compact"
          variant="outlined"
          placeholder="Not set"
          hide-details
          :disabled="saving"
        />
      </template>

      <div class="text-subtitle-2 font-weight-medium dv-section-title">Time period</div>
      <v-row>
        <v-col cols="12" sm="6">
          <div class="text-caption text-medium-emphasis dv-label">From</div>
          <v-text-field
            v-model="form.from"
            density="compact"
            variant="outlined"
            placeholder="e.g. 1990"
            hide-details
            :disabled="saving"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <div class="text-caption text-medium-emphasis dv-label">To</div>
          <v-text-field
            v-model="form.to"
            density="compact"
            variant="outlined"
            placeholder="Latest data"
            hide-details
            :disabled="saving"
          />
        </v-col>
      </v-row>
      <div class="text-caption text-medium-emphasis dv-hint">
        Use the indicator's period format (2000, 2000-Q1, 2000-01). Leave both empty to show the full range.
      </div>

      <div class="text-subtitle-2 font-weight-medium dv-section-title">Chart</div>
      <div class="text-caption text-medium-emphasis dv-label">Chart type</div>
      <v-select
        v-model="form.chartType"
        :items="chartTypeItems"
        density="compact"
        variant="outlined"
        hide-details
        :disabled="saving"
      />

      <div class="text-subtitle-2 font-weight-medium dv-section-title">Table layout</div>
      <v-row>
        <v-col cols="12" sm="6">
          <div class="text-caption text-medium-emphasis dv-label">Rows</div>
          <v-autocomplete
            v-model="form.tableRows"
            :items="layoutItemsExcluding(form.tableColumns)"
            multiple
            chips
            closable-chips
            clearable
            density="compact"
            variant="outlined"
            placeholder="Not set"
            hide-details
            :disabled="saving"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <div class="text-caption text-medium-emphasis dv-label">Columns</div>
          <v-autocomplete
            v-model="form.tableColumns"
            :items="layoutItemsExcluding(form.tableRows)"
            multiple
            chips
            closable-chips
            clearable
            density="compact"
            variant="outlined"
            placeholder="Not set"
            hide-details
            :disabled="saving"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <div class="text-caption text-medium-emphasis dv-label">Time order</div>
          <v-select
            v-model="form.timeOrder"
            :items="timeOrderItems"
            density="compact"
            variant="outlined"
            hide-details
            :disabled="saving"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <div class="text-caption text-medium-emphasis dv-label">Header style</div>
          <v-select
            v-model="form.headers"
            :items="headerItems"
            density="compact"
            variant="outlined"
            hide-details
            :disabled="saving"
          />
        </v-col>
      </v-row>
      <div class="text-caption text-medium-emphasis dv-hint">
        Columns placed in neither rows nor columns are added to the rows.
      </div>

      <v-alert v-if="saveError" type="error" density="compact" variant="tonal" class="dv-error">
        {{ saveError }}
      </v-alert>

      <div class="dv-actions">
        <div v-if="lastChangedText" class="text-caption text-medium-emphasis dv-last-changed">{{ lastChangedText }}</div>
        <div class="d-flex flex-wrap align-center dv-buttons">
          <v-btn color="primary" variant="flat" class="text-none" :loading="saving" :disabled="!dirty" @click="save">
            Save default view
          </v-btn>
          <v-btn variant="text" class="text-none" :disabled="!dirty || saving" @click="resetForm">Discard changes</v-btn>
          <v-spacer />
          <v-btn
            color="error"
            variant="text"
            class="text-none"
            :disabled="!savedPresentation || saving"
            @click="clearAll"
          >
            Remove default view
          </v-btn>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, inject, onMounted, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { getLayoutEligibleDimensions, TIME_PERIOD_KEY } from '@/shared/timeseries/indicatorSchemaUtils.js';
import { readCodesFromQueryParam } from '@/shared/timeseries/indicatorFilterQueryParams.js';
import { useStudyTimeseriesApi } from '../composables/useStudyTimeseriesApi';

defineOptions({ name: 'DefaultViewPanel' });

const props = defineProps({
  studyIdno: { type: String, required: true },
  /** DSD components from the admin schema response. */
  components: { type: Array, default: () => [] },
});

const setMessage = inject('setMessage', () => {});
const { config } = useAppConfig();
const { fetchPresentation, savePresentation } = useStudyTimeseriesApi(computed(() => props.studyIdno));

const chartTypeItems = [
  { title: 'Not set (automatic)', value: '' },
  { title: 'Automatic', value: 'auto' },
  { title: 'Line', value: 'line' },
  { title: 'Column (vertical bars)', value: 'column' },
  { title: 'Horizontal bars', value: 'hbar' },
];
const timeOrderItems = [
  { title: 'Not set (oldest first)', value: '' },
  { title: 'Oldest first', value: 'asc' },
  { title: 'Latest first', value: 'desc' },
];
const headerItems = [
  { title: 'Not set (grouped)', value: '' },
  { title: 'Grouped', value: 'grouped' },
  { title: 'Flat', value: 'flat' },
];

const loading = ref(true);
const loadError = ref('');
const saving = ref(false);
const saveError = ref('');
const linkDraft = ref('');
/** `data_presentation` as last loaded or saved; null when the indicator has none. */
const savedPresentation = ref(null);
/** When and by whom the saved settings were last changed ({ changed: unix seconds, changed_by: username }). */
const savedChange = ref({ changed: null, changed_by: null });
const timePeriodName = ref('');
/** component_name => [{ code, label }] (codes with data) */
const optionsByColumn = ref({});

const form = reactive({
  geography: [],
  dimensions: {},
  from: '',
  to: '',
  chartType: '',
  tableRows: [],
  tableColumns: [],
  timeOrder: '',
  headers: '',
});

const componentsSorted = computed(() =>
  [...(props.components || [])].sort((a, b) => (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0))
);
const geographyComponent = computed(() => componentsSorted.value.find((c) => c?.column_type === 'geography') || null);
const periodicityComponent = computed(
  () => componentsSorted.value.find((c) => c?.column_type === 'periodicity') || null
);
/** Frequency and dimension columns that have observed codes. */
const filterComponents = computed(() =>
  componentsSorted.value.filter(
    (c) => ['periodicity', 'dimension'].includes(c?.column_type) && (optionsByColumn.value[c.name] || []).length
  )
);

/** Table layout choices as data structure column names (the time column under its own name). */
const layoutItems = computed(() => {
  const ov = componentsSorted.value.find((c) => c?.column_type === 'observation_value')?.name || '';
  return getLayoutEligibleDimensions(componentsSorted.value, {
    observationValueKey: ov,
    timePeriodKey: timePeriodName.value,
  }).map((d) => {
    const name = d.key === TIME_PERIOD_KEY ? d.component?.name || timePeriodName.value : d.key;
    return { title: componentTitle(d.component) || name, value: name };
  });
});

function layoutItemsExcluding(taken) {
  const set = new Set(taken || []);
  return layoutItems.value.filter((it) => !set.has(it.value));
}

function componentTitle(c) {
  if (!c) return '';
  const label = String(c.label ?? '').trim();
  return label && label !== c.name ? `${label} (${c.name})` : String(c.name ?? '');
}

function codeItems(columnName) {
  return (optionsByColumn.value[columnName] || []).map((o) => ({
    title: o.label && o.label !== o.code ? `${o.label} (${o.code})` : o.code,
    value: o.code,
  }));
}

const siteBase = computed(() => String(config.value?.siteUrl || '').replace(/\/?$/, '/'));
const studySid = computed(() => Number(config.value?.studySid) || 0);
const chartPageUrl = computed(() =>
  studySid.value && siteBase.value !== '/' ? `${siteBase.value}catalog/${studySid.value}/indicator-chart` : ''
);
const tablePageUrl = computed(() =>
  studySid.value && siteBase.value !== '/' ? `${siteBase.value}catalog/${studySid.value}/indicator-table` : ''
);

/** Form -> `data_presentation`, leaving out everything not set (null when nothing is). */
function buildPresentation() {
  const out = {};
  const clean = (arr) => (arr || []).map((v) => String(v ?? '').trim()).filter(Boolean);

  const geography = clean(form.geography);
  if (geography.length) out.geography = geography;

  const from = form.from.trim();
  const to = form.to.trim();
  if (from || to) out.time_period = { ...(from ? { from } : {}), ...(to ? { to } : {}) };

  const dimensions = [];
  for (const col of componentsSorted.value) {
    const values = clean(form.dimensions[col.name]);
    if (values.length) dimensions.push({ column: col.name, values });
  }
  if (dimensions.length) out.dimensions = dimensions;

  if (form.chartType) out.chart_type = form.chartType;

  const table = {};
  if (form.tableRows.length) table.rows = [...form.tableRows];
  if (form.tableColumns.length) table.columns = [...form.tableColumns];
  if (form.timeOrder) table.time_order = form.timeOrder;
  if (form.headers) table.headers = form.headers;
  if (Object.keys(table).length) out.table_layout = table;

  return Object.keys(out).length ? out : null;
}

function fillForm(p) {
  const dims = {};
  for (const d of Array.isArray(p?.dimensions) ? p.dimensions : []) {
    if (d?.column) dims[d.column] = [...(d.values || [])];
  }
  form.geography = [...(p?.geography || [])];
  form.dimensions = dims;
  form.from = String(p?.time_period?.from ?? '');
  form.to = String(p?.time_period?.to ?? '');
  form.chartType = p?.chart_type || '';
  form.tableRows = [...(p?.table_layout?.rows || [])];
  form.tableColumns = [...(p?.table_layout?.columns || [])];
  form.timeOrder = p?.table_layout?.time_order || '';
  form.headers = p?.table_layout?.headers || '';
}

const dirty = computed(() => JSON.stringify(buildPresentation()) !== JSON.stringify(savedPresentation.value));

const lastChangedText = computed(() => {
  const { changed, changed_by: by } = savedChange.value;
  if (!savedPresentation.value || !changed) return '';
  const when = new Date(changed * 1000).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
  return by ? `Last changed ${when} by ${by}` : `Last changed ${when}`;
});

/** Keep the saved settings and change info from a GET or POST result. */
function applyResult(res) {
  savedPresentation.value = res?.data_presentation || null;
  savedChange.value = { changed: res?.changed ?? null, changed_by: res?.changed_by ?? null };
  fillForm(savedPresentation.value);
}

function resetForm() {
  saveError.value = '';
  fillForm(savedPresentation.value);
}

async function load() {
  if (!props.studyIdno) return;
  loading.value = true;
  loadError.value = '';
  try {
    const res = await fetchPresentation();
    const byCol = {};
    for (const f of res.filter_options || []) {
      if (f?.component_name) byCol[f.component_name] = Array.isArray(f.options) ? f.options : [];
    }
    optionsByColumn.value = byCol;
    timePeriodName.value = String(res.time_period_component || '');
    applyResult(res);
  } catch (e) {
    loadError.value = e?.message || 'Could not load the default view.';
  } finally {
    loading.value = false;
  }
}

async function persist(presentation, successText) {
  saving.value = true;
  saveError.value = '';
  try {
    applyResult(await savePresentation(presentation));
    setMessage(successText, 'success');
  } catch (e) {
    saveError.value = e?.message || 'Save failed';
  } finally {
    saving.value = false;
  }
}

function save() {
  return persist(buildPresentation(), 'Default view saved.');
}

function clearAll() {
  if (!window.confirm('Remove the default view? The chart and table pages will go back to their built-in defaults.')) {
    return;
  }
  return persist(null, 'Default view removed.');
}

/**
 * Fill the form from a public chart or table page address (geo, period, c[…], from, to, chart,
 * table_rows, table_cols, table_time_order, table_flatten). Only settings present in the link change.
 */
function copyFromLink() {
  const raw = linkDraft.value.trim();
  if (!raw) return;
  let q;
  try {
    q = new URL(raw, window.location.href).searchParams;
  } catch {
    setMessage('That is not a valid page address.', 'warning');
    return;
  }

  let copied = 0;
  const geo = geographyComponent.value;
  if (geo && q.has('geo')) {
    form.geography = readCodesFromQueryParam(q, 'geo');
    copied++;
  }
  const per = periodicityComponent.value;
  if (per && q.has('period')) {
    form.dimensions[per.name] = readCodesFromQueryParam(q, 'period');
    copied++;
  }
  for (const col of componentsSorted.value) {
    const key = `c[${col.name}]`;
    if (q.has(key)) {
      form.dimensions[col.name] = readCodesFromQueryParam(q, key);
      copied++;
    }
  }
  if (q.has('from') || q.has('to')) {
    form.from = String(q.get('from') ?? '').trim();
    form.to = String(q.get('to') ?? '').trim();
    copied++;
  }
  if (q.has('chart')) {
    const ct = String(q.get('chart') || '').trim().toLowerCase();
    form.chartType = ct === 'bar' || ct === 'columns' ? 'column' : chartTypeItems.some((it) => it.value === ct) ? ct : '';
    copied++;
  }
  const toName = (k) => (k === TIME_PERIOD_KEY ? timePeriodName.value : k);
  const layoutNames = new Set(layoutItems.value.map((it) => it.value));
  if (q.has('table_rows') || q.has('table_cols')) {
    form.tableRows = readCodesFromQueryParam(q, 'table_rows').map(toName).filter((k) => layoutNames.has(k));
    form.tableColumns = readCodesFromQueryParam(q, 'table_cols')
      .map(toName)
      .filter((k) => layoutNames.has(k) && !form.tableRows.includes(k));
    form.timeOrder = q.get('table_time_order') === 'desc' ? 'desc' : 'asc';
    form.headers = q.get('table_flatten') === '1' ? 'flat' : 'grouped';
    copied++;
  }

  if (copied) {
    linkDraft.value = '';
    setMessage('Settings copied from the link. Review them and save.', 'info');
  } else {
    setMessage('The link has no chart or table settings to copy.', 'warning');
  }
}

watch(() => props.studyIdno, load);
onMounted(load);
</script>

<style scoped>
/* Own spacing classes: the admin theme also loads Bootstrap, whose mt-* utilities clash with Vuetify's. */
.default-view-panel {
  max-width: 880px;
}

.dv-section-title {
  margin-top: 40px;
  margin-bottom: 16px;
}

/* Same field and label sizes as the rest of the data page (.field-group / .field-label there). */
.default-view-panel :deep(.v-field) {
  font-size: 0.8125rem;
}

.dv-label {
  margin-top: 24px;
  margin-bottom: 6px;
  font-weight: 500;
  line-height: 1.25rem;
  letter-spacing: 0.01em;
  color: rgba(var(--v-theme-on-surface), 0.68);
}

/* No extra gap for the first label under a heading or at the top of a column. */
.dv-section-title + .dv-label,
.v-col > .dv-label:first-child {
  margin-top: 0;
}

.dv-hint {
  margin-top: 8px;
}

.dv-inline,
.dv-buttons {
  gap: 8px;
}

.dv-error {
  margin-top: 24px;
}

.dv-actions {
  margin-top: 48px;
  padding-top: 24px;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.dv-last-changed {
  margin-bottom: 12px;
}
</style>
