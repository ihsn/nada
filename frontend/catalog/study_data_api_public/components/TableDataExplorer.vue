<template>
  <section class="tdx">
    <div class="tda-section-head">
      <h2 class="tda-h2">{{ ui.explorerHeading }}</h2>
    </div>

    <!-- filters: only for tables with a data dictionary; large tables only on indexed columns -->
    <v-sheet v-if="filterableFields.length" border rounded="lg" class="pa-3 mb-3">
      <div class="d-flex align-center mb-2">
        <span class="text-subtitle-2">{{ ui.filtersHeading }}</span>
        <span v-if="filterableFields.length < allColumns.length" class="text-caption text-medium-emphasis ml-2">
          {{ ui.filterIndexedHint }}
        </span>
        <v-spacer />
        <v-btn v-if="appliedFilters.length" size="x-small" variant="text" class="text-none" @click="clearFilters">
          {{ ui.clearFilters }}
        </v-btn>
      </div>
      <form @submit.prevent="applyFilters">
        <div v-for="(filter, idx) in filterDraft" :key="filter.id" class="tdx-filter-row">
          <v-autocomplete
            v-model="filter.column"
            :items="filterableFields"
            :label="ui.filterColumn"
            density="compact"
            variant="outlined"
            hide-details
            class="tdx-filter-column"
            @update:model-value="filter.value = codeItems(filter.column).length ? [] : ''"
          />
          <v-autocomplete
            v-if="codeItems(filter.column).length"
            v-model="filter.value"
            :items="codeItems(filter.column)"
            item-title="title"
            item-value="value"
            :label="ui.filterValue"
            multiple
            chips
            closable-chips
            density="compact"
            variant="outlined"
            hide-details
            class="tdx-filter-value"
          />
          <v-text-field
            v-else
            v-model="filter.value"
            :label="ui.filterValue"
            :hint="filter.column ? (isNumericColumn(filter.column) ? ui.filterNumericHint : ui.filterTextHint) : ''"
            :persistent-hint="!!filter.column"
            density="compact"
            variant="outlined"
            class="tdx-filter-value"
          />
          <v-btn
            icon="mdi-close"
            size="small"
            variant="text"
            :title="ui.removeFilter"
            :aria-label="ui.removeFilter"
            @click="filterDraft.splice(idx, 1)"
          />
        </div>
        <div class="d-flex flex-wrap ga-2 mt-1">
          <v-btn size="small" variant="text" class="text-none" prepend-icon="mdi-plus" @click="addFilter">
            {{ ui.addFilter }}
          </v-btn>
          <v-spacer />
          <v-btn size="small" color="primary" type="submit" class="text-none" :variant="filtersChanged ? 'flat' : 'tonal'">
            {{ ui.applyFilters }}
          </v-btn>
        </div>
      </form>
    </v-sheet>

    <!-- status line -->
    <div class="d-flex flex-wrap align-center ga-2 mb-2 text-body-2">
      <span v-if="found !== null && rows.length">
        {{ fmt(ui.showing, { from: (offset + 1).toLocaleString(), to: (offset + rows.length).toLocaleString(), found: found.toLocaleString() }) }}
      </span>
      <span v-if="found !== null && total !== null && found !== total" class="text-medium-emphasis">
        · {{ fmt(ui.showingFiltered, { found: found.toLocaleString(), total: total.toLocaleString() }) }}
      </span>
      <v-spacer />
      <v-progress-circular v-if="loading" indeterminate size="18" width="2" color="primary" :aria-label="ui.loading" />
      <v-btn
        icon="mdi-cog-outline"
        size="small"
        variant="text"
        :title="columnsButtonText"
        :aria-label="columnsButtonText"
        :disabled="!allColumns.length"
        @click="columnsMenu = true"
      />
    </div>

    <!-- column picker -->
    <v-dialog v-model="columnsMenu" max-width="560" scrollable>
      <v-card rounded="lg" class="tdx-columns-dialog">
        <div class="d-flex align-center px-5 pt-4 pb-1">
          <div>
            <div class="text-subtitle-1 font-weight-medium">{{ ui.columnsHeading }}</div>
            <div class="text-caption text-medium-emphasis">
              {{ fmt(ui.columnsSelected, { selected: columnDraft.length, total: allColumns.length }) }}
            </div>
          </div>
          <v-spacer />
          <v-btn icon="mdi-close" size="small" variant="text" :aria-label="ui.cancel" @click="columnsMenu = false" />
        </div>

        <div class="px-5 pt-3 pb-3">
          <v-text-field
            v-model="columnSearch"
            :placeholder="ui.columnsSearch"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
          />
          <div class="d-flex ga-1 mt-2">
            <v-btn size="small" variant="text" class="text-none px-2" @click="selectColumns(allColumns)">{{ ui.selectAll }}</v-btn>
            <v-btn size="small" variant="text" class="text-none px-2" @click="selectColumns(defaultColumns)">{{ ui.selectDefault }}</v-btn>
            <v-btn size="small" variant="text" class="text-none px-2" @click="selectColumns([])">{{ ui.selectNone }}</v-btn>
          </div>
        </div>

        <v-divider />

        <v-card-text class="pa-0 tdx-columns-body">
          <v-list density="compact" class="py-1">
            <v-list-item
              v-for="name in filteredColumnNames"
              :key="name"
              class="px-5"
              @click="toggleColumn(name)"
            >
              <template #prepend>
                <v-checkbox-btn
                  :model-value="columnDraft.includes(name)"
                  density="compact"
                  color="primary"
                  class="mr-2"
                  @click.stop="toggleColumn(name)"
                />
              </template>
              <v-list-item-title class="text-body-2 font-weight-medium">{{ name }}</v-list-item-title>
              <v-list-item-subtitle v-if="labelOf(name) && labelOf(name) !== name" class="text-caption">
                {{ labelOf(name) }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <div v-if="!filteredColumnNames.length" class="text-body-2 text-medium-emphasis text-center py-8">
            {{ ui.dictionaryEmpty }}
          </div>
        </v-card-text>

        <v-divider />

        <v-card-actions class="px-5 py-3">
          <v-spacer />
          <v-btn variant="text" class="text-none" @click="columnsMenu = false">{{ ui.cancel }}</v-btn>
          <v-btn
            color="primary"
            variant="flat"
            class="text-none px-5"
            :disabled="!columnDraft.length"
            @click="applyColumns"
          >
            {{ ui.applyFilters }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-3">
      {{ ui.errorPrefix }} {{ error }}
    </v-alert>
    <v-alert v-if="ignoredFields.length" type="warning" variant="tonal" density="compact" class="mb-3">
      {{ fmt(ui.ignoredFields, { fields: ignoredFields.join(', ') }) }}
    </v-alert>

    <!-- grid -->
    <v-sheet v-if="!error" elevation="2" rounded="lg" class="tdx-grid-wrap" :class="{ 'tdx-grid-wrap--loading': loading }">
      <v-table density="compact" hover class="tdx-grid">
        <thead>
          <tr>
            <th class="tdx-rownum">#</th>
            <th v-for="name in gridColumns" :key="name" :title="headerTitle(name)">
              <div class="tdx-head-name">{{ name }}</div>
              <div v-if="labelOf(name) && labelOf(name) !== name" class="tdx-head-label text-medium-emphasis">
                {{ labelOf(name) }}
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, idx) in rows" :key="offset + idx">
            <td class="tdx-rownum text-medium-emphasis">{{ offset + idx + 1 }}</td>
            <td v-for="name in gridColumns" :key="name" :title="cellTitle(name, row[name])" class="tdx-cell">
              {{ cellText(row[name]) }}<span v-if="codeLabel(name, row[name])" class="tdx-code-label text-medium-emphasis">
                {{ codeLabel(name, row[name]) }}</span>
            </td>
          </tr>
          <tr v-if="!loading && !rows.length">
            <td :colspan="gridColumns.length + 1" class="text-center text-medium-emphasis py-8">
              {{ appliedFilters.length ? ui.noRows : ui.noData }}
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-sheet>

    <div class="d-flex justify-end mt-3">
      <v-pagination
        v-if="visiblePageCount > 1"
        v-model="page"
        :length="visiblePageCount"
        :total-visible="7"
        density="compact"
        size="small"
      />
    </div>

    <!-- developer details -->
    <v-expansion-panels v-model="devPanel" class="mt-4" variant="accordion">
      <v-expansion-panel value="dev" elevation="0" rounded="lg">
        <v-expansion-panel-title class="text-subtitle-2">{{ ui.developerHeading }}</v-expansion-panel-title>
        <v-expansion-panel-text>
          <div class="text-caption text-medium-emphasis mb-1">{{ ui.queryUrlLabel }}</div>
          <div class="d-flex align-start ga-2 mb-4">
            <code class="tdx-code flex-grow-1">{{ queryUrlDisplay }}</code>
            <v-btn size="small" variant="text" icon="mdi-content-copy" :title="ui.copy" :aria-label="ui.copy" @click="copy(queryUrl)" />
            <v-btn size="small" variant="text" icon="mdi-open-in-new" :title="ui.open" :aria-label="ui.open" :href="queryUrl" target="_blank" rel="noopener" />
          </div>
          <template v-if="devPanel === 'dev' && responseJson">
            <div class="d-flex align-center mb-1">
              <span class="text-caption text-medium-emphasis">{{ ui.jsonResponseLabel }}</span>
              <v-spacer />
              <v-btn size="small" variant="text" icon="mdi-content-copy" :title="ui.copy" :aria-label="ui.copy" @click="copy(responseJson)" />
            </div>
            <pre class="tdx-json">{{ responseJson }}</pre>
          </template>
        </v-expansion-panel-text>
      </v-expansion-panel>
    </v-expansion-panels>

    <v-snackbar v-model="snackbar.show" :timeout="2000" location="bottom">{{ snackbar.text }}</v-snackbar>
  </section>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import {
  DEFAULT_VISIBLE_COLUMNS,
  PAGE_SIZE,
  MAX_PAGES,
  buildDataParams,
  displayQuery,
  cellText,
  filtersToParams,
  isNumericType,
  readViewState,
  writeViewState,
  fmt,
} from '../utils/tableQuery.js';

defineOptions({ name: 'TableDataExplorer' });

const props = defineProps({
  api: { type: Object, required: true },
  dictionary: { type: Array, default: () => [] },
  /** columns offered as filters (info → filterable_fields) */
  filterableFields: { type: Array, default: () => [] },
  ui: { type: Object, required: true },
});

const emit = defineEmits(['loaded']);

// ---- columns -------------------------------------------------------------
const columnsFromData = ref([]);
const dictionaryByName = computed(() => {
  const map = {};
  for (const f of props.dictionary) {
    if (f?.name) map[f.name] = f;
  }
  return map;
});
/** Dictionary order; tables without a dictionary use the keys of the returned rows. */
const allColumns = computed(() =>
  props.dictionary.length ? props.dictionary.map((f) => f.name).filter(Boolean) : columnsFromData.value
);
const defaultColumns = computed(() => allColumns.value.slice(0, DEFAULT_VISIBLE_COLUMNS));

const selectedColumns = ref([]);
const columnDraft = ref([]);
const columnsMenu = ref(false);
const columnSearch = ref('');

/** Columns drawn in the grid: the selection (in dictionary order), else the default first columns. */
const gridColumns = computed(() => {
  if (selectedColumns.value.length) {
    const chosen = new Set(selectedColumns.value);
    return allColumns.value.filter((c) => chosen.has(c));
  }
  return defaultColumns.value;
});
/** Every column shown → the request omits fields= (same result, short URL). */
const allColumnsSelected = computed(
  () => !allColumns.value.length || gridColumns.value.length === allColumns.value.length
);
const filteredColumnNames = computed(() => {
  const q = String(columnSearch.value || '').toLowerCase().trim();
  if (!q) return allColumns.value;
  return allColumns.value.filter(
    (name) => name.toLowerCase().includes(q) || String(labelOf(name)).toLowerCase().includes(q)
  );
});

watch(columnsMenu, (open) => {
  if (open) columnDraft.value = [...gridColumns.value];
});

function selectColumns(list) {
  columnDraft.value = [...list];
}
function toggleColumn(name) {
  const i = columnDraft.value.indexOf(name);
  if (i === -1) columnDraft.value = [...columnDraft.value, name];
  else columnDraft.value = columnDraft.value.filter((c) => c !== name);
}
const columnsButtonText = computed(() =>
  fmt(props.ui.columnsButton, { selected: gridColumns.value.length, total: allColumns.value.length })
);
function applyColumns() {
  selectedColumns.value = allColumns.value.filter((c) => columnDraft.value.includes(c));
  columnsMenu.value = false;
  page.value = 1;
  load();
}

function labelOf(name) {
  return dictionaryByName.value[name]?.label || '';
}
function isNumericColumn(name) {
  return isNumericType(dictionaryByName.value[name]?.data_type);
}
function headerTitle(name) {
  const f = dictionaryByName.value[name];
  if (!f) return name;
  return [name, f.label && f.label !== name ? f.label : '', f.data_type ? `(${f.data_type})` : '']
    .filter(Boolean)
    .join(' ');
}

const codeMaps = computed(() => {
  const maps = {};
  for (const f of props.dictionary) {
    if (Array.isArray(f?.code_list) && f.code_list.length) {
      const m = {};
      for (const c of f.code_list) {
        if (c && c.code !== undefined && c.code !== null) m[String(c.code)] = c.label || '';
      }
      maps[f.name] = m;
    }
  }
  return maps;
});
function codeItems(name) {
  const f = dictionaryByName.value[name];
  if (!f || !Array.isArray(f.code_list)) return [];
  return f.code_list
    .filter((c) => c && c.code !== undefined && c.code !== null)
    .map((c) => ({ value: String(c.code), title: c.label ? `${c.code} – ${c.label}` : String(c.code) }));
}
function codeLabel(name, value) {
  const m = codeMaps.value[name];
  if (!m || value === null || value === undefined) return '';
  const label = m[String(value)];
  return label && label !== String(value) ? label : '';
}
function cellTitle(name, value) {
  const text = cellText(value);
  const label = codeLabel(name, value);
  return label ? `${text} – ${label}` : text;
}

// ---- filters -------------------------------------------------------------
let filterSeq = 0;
const filterDraft = ref([]);
const appliedFilters = ref([]);

function cleanFilters(list) {
  return (list || [])
    .map((f) => ({ column: f.column || '', value: Array.isArray(f.value) ? [...f.value] : String(f.value ?? '') }))
    .filter((f) => f.column && (Array.isArray(f.value) ? f.value.length : f.value.trim() !== ''));
}
const filtersChanged = computed(
  () => JSON.stringify(filtersToParams(cleanFilters(filterDraft.value))) !== JSON.stringify(filtersToParams(appliedFilters.value))
);
function addFilter() {
  filterDraft.value.push({ id: ++filterSeq, column: '', value: '' });
}
function applyFilters() {
  appliedFilters.value = cleanFilters(filterDraft.value);
  page.value = 1;
  load();
}
function clearFilters() {
  filterDraft.value = [];
  appliedFilters.value = [];
  page.value = 1;
  load();
}

// ---- paging + data -------------------------------------------------------
const page = ref(1);
const pageSize = ref(PAGE_SIZE);
const rows = ref([]);
const found = ref(null);
const total = ref(null);
const loading = ref(false);
const error = ref('');
const ignoredFields = ref([]);
const lastResponse = ref(null);

const offset = computed(() => (page.value - 1) * pageSize.value);
const pageCount = computed(() => (found.value ? Math.ceil(found.value / pageSize.value) : 0));
/** Only the first MAX_PAGES pages can be browsed; filters narrow larger results. */
const visiblePageCount = computed(() => Math.min(pageCount.value, MAX_PAGES));

watch(page, () => load());

const currentParams = computed(() =>
  buildDataParams({
    columns: gridColumns.value,
    allColumnsSelected: allColumnsSelected.value,
    filters: appliedFilters.value,
    limit: pageSize.value,
    offset: offset.value,
  })
);
const queryUrl = computed(() => `${props.api.urls.data}?${currentParams.value.toString()}`);
const queryUrlDisplay = computed(() => `${props.api.urls.data}?${displayQuery(currentParams.value)}`);

let controller = null;
let requestSeq = 0;

async function load() {
  if (controller) controller.abort();
  controller = new AbortController();
  const seq = ++requestSeq;
  loading.value = true;
  error.value = '';

  saveViewState();

  try {
    const body = await props.api.fetchData(currentParams.value, controller.signal);
    if (seq !== requestSeq) return; // a newer request has started
    rows.value = Array.isArray(body?.data) ? body.data : [];
    found.value = typeof body?.found === 'number' ? body.found : rows.value.length;
    total.value = typeof body?.total === 'number' ? body.total : null;
    ignoredFields.value = Array.isArray(body?.ignored_fields) ? body.ignored_fields : [];
    lastResponse.value = body;
    if (!props.dictionary.length && !columnsFromData.value.length && rows.value.length) {
      columnsFromData.value = Object.keys(rows.value[0]);
    }
    emit('loaded', { found: found.value, total: total.value });
  } catch (e) {
    if (e?.name === 'AbortError' || seq !== requestSeq) return;
    rows.value = [];
    found.value = null;
    ignoredFields.value = [];
    lastResponse.value = null;
    error.value = e?.message || String(e);
  } finally {
    if (seq === requestSeq) loading.value = false;
  }
}

// ---- page URL state ------------------------------------------------------
function saveViewState() {
  writeViewState({
    columns: selectedColumns.value.length && !allColumnsSelected.value ? selectedColumns.value : undefined,
    all: (selectedColumns.value.length && allColumnsSelected.value) || undefined,
    filters: appliedFilters.value,
    page: page.value,
  });
}

function restoreViewState() {
  const state = readViewState();
  if (!state) return;
  if (Number(state.page) > 1) page.value = Math.min(Math.floor(Number(state.page)), MAX_PAGES);
  if (Array.isArray(state.filters)) {
    const allowed = new Set(props.filterableFields);
    appliedFilters.value = cleanFilters(state.filters).filter((f) => allowed.has(f.column));
    filterDraft.value = appliedFilters.value.map((f) => ({ id: ++filterSeq, ...f }));
  }
  if (state.all && allColumns.value.length) {
    selectedColumns.value = [...allColumns.value];
  } else if (Array.isArray(state.columns) && allColumns.value.length) {
    const known = new Set(allColumns.value);
    selectedColumns.value = state.columns.filter((c) => known.has(c));
  }
}

// ---- developer panel -----------------------------------------------------
const devPanel = ref(null);
const responseJson = computed(() => (lastResponse.value ? JSON.stringify(lastResponse.value, null, 2) : ''));
const snackbar = reactive({ show: false, text: '' });

async function copy(text) {
  try {
    await navigator.clipboard.writeText(text);
    snackbar.text = props.ui.copied;
  } catch {
    snackbar.text = props.ui.copyFailed;
  }
  snackbar.show = true;
}

onMounted(() => {
  restoreViewState();
  if (!filterDraft.value.length && props.filterableFields.length) addFilter();
  load();
});
onBeforeUnmount(() => {
  if (controller) controller.abort();
});
</script>

<style scoped>
.tdx-filter-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 8px;
}
.tdx-filter-column {
  flex: 0 0 260px;
  max-width: 40%;
}
.tdx-filter-value {
  flex: 1 1 auto;
  min-width: 0;
}
@media (max-width: 600px) {
  .tdx-filter-row {
    flex-wrap: wrap;
  }
  .tdx-filter-column {
    flex: 1 1 100%;
    max-width: 100%;
  }
}
.tdx-columns-body {
  height: 380px;
}
.tdx-grid-wrap {
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.14) !important;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06) !important;
  transition: opacity 0.15s;
}
.tdx-grid-wrap--loading {
  opacity: 0.6;
}
.tdx-grid :deep(th) {
  vertical-align: bottom;
  white-space: nowrap;
  background: #eef1f5 !important;
  border-bottom: 1px solid rgba(0, 0, 0, 0.12) !important;
}
.tdx-head-name {
  font-weight: 600;
  font-size: 0.8125rem;
}
.tdx-head-label {
  font-size: 0.7rem;
  font-weight: 400;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
}
.tdx-rownum {
  width: 56px;
  font-size: 0.75rem;
}
.tdx-cell {
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace;
  font-size: 0.8125rem;
}
.tdx-code-label {
  font-size: 0.75rem;
  margin-left: 4px;
}
.tdx-code {
  display: block;
  padding: 6px 8px;
  font-size: 0.75rem;
  word-break: break-all;
  background: rgba(var(--v-theme-on-surface), 0.05);
  border-radius: 4px;
}
.tdx-json {
  max-height: 320px;
  overflow: auto;
  padding: 8px;
  font-size: 0.75rem;
  background: rgba(var(--v-theme-on-surface), 0.05);
  border-radius: 4px;
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
