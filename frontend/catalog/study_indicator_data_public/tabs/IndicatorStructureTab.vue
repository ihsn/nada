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
              <div class="section-head d-flex flex-wrap align-center gap-2 mb-3">
                <div class="d-flex align-center gap-2">
                  <div class="section-title">Data structure</div>
                  <v-chip size="small" variant="tonal" color="primary" class="font-weight-medium" style="margin-left: 10px;">
                    {{ dataStructureComponents.length }} columns
                  </v-chip>
                </div>
                <v-spacer />
                <v-btn
                  size="x-small"
                  variant="text"
                  color="primary"
                  prepend-icon="mdi-download"
                  density="compact"
                  class="font-weight-medium"
                  @click="downloadDataStructureJson"
                >
                  Download JSON
                </v-btn>
                <v-btn
                  size="x-small"
                  variant="text"
                  color="primary"
                  prepend-icon="mdi-download"
                  density="compact"
                  class="font-weight-medium"
                  @click="downloadDataStructureXml"
                >
                  Download SDMX/XML
                </v-btn>
              </div>
              <v-sheet rounded="lg" border class="data-shell overflow-hidden">
                <v-data-table
                  v-model:expanded="dsExpanded"
                  :headers="dsTableHeaders"
                  :items="dataStructureComponents"
                  :items-per-page="-1"
                  :row-props="dsComponentsRowProps"
                  item-value="name"
                  show-expand
                  density="comfortable"
                  class="elevation-0 ds-components-table"
                  hover
                  hide-default-footer
                  @update:expanded="onDsExpandedUpdate"
                >
                  <template #item.role="{ item }">
                    <v-chip
                      v-if="item.role"
                      size="x-small"
                      :color="dsRoleChipColor(item.role)"
                      variant="tonal"
                      class="text-uppercase font-weight-semibold"
                    >
                      {{ item.role }}
                    </v-chip>
                    <span v-else class="text-medium-emphasis">—</span>
                  </template>
                  <template #item.codelist="{ item }">
                    <div class="d-flex align-center gap-2 flex-wrap">
                      <code v-if="item.codelist_idno" class="stat-block__code">{{ item.codelist_idno }}</code>
                      <span v-else-if="item.codelist_id" class="text-medium-emphasis">#{{ item.codelist_id }}</span>
                      <span v-else class="text-medium-emphasis">—</span>
                      <v-chip
                        v-if="codelistTotalForComponent(item) != null"
                        size="x-small"
                        variant="tonal"
                        color="primary"
                        class="font-weight-medium"
                      >
                        {{ codelistTotalForComponent(item) }} codes
                      </v-chip>
                    </div>
                  </template>
                  <template #item.data-table-expand="{ item, internalItem, isExpanded, toggleExpand }">
                    <v-btn
                      v-if="item.codelist_id"
                      :icon="isExpanded(internalItem) ? 'mdi-chevron-up' : 'mdi-chevron-down'"
                      size="small"
                      variant="text"
                      density="comfortable"
                      @click.stop="toggleExpand(internalItem)"
                    />
                  </template>
                  <template #expanded-row="{ item, columns }">
                    <tr v-if="item.codelist_id" class="ds-expand-row">
                      <td :colspan="columns.length" class="pa-0">
                        <div class="ds-expand-panel pa-4">
                          <div class="field-label mb-2">Codelist</div>
                          <div class="d-flex flex-wrap align-center gap-3 mb-3">
                            <div class="text-body-2 font-weight-medium">
                              <code v-if="item.codelist_idno" class="stat-block__code">{{ item.codelist_idno }}</code>
                              <span v-else class="text-medium-emphasis">#{{ item.codelist_id }}</span>
                            </div>
                            <v-spacer />
                            <v-text-field
                              :model-value="codelistFilters[item.name] ?? ''"
                              prepend-inner-icon="mdi-magnify"
                              placeholder="Search codes or labels…"
                              density="compact"
                              variant="solo-filled"
                              flat
                              hide-details
                              clearable
                              class="ds-codelist-search rounded-lg"
                              @update:model-value="(v) => onCodelistSearchInput(item, v)"
                            />
                          </div>
                          <v-sheet rounded="lg" border class="data-shell overflow-hidden">
                            <v-data-table-server
                              :headers="codelistTableHeaders"
                              :items="codelistPagedState[item.name]?.items ?? []"
                              :items-length="codelistPagedState[item.name]?.total ?? 0"
                              :loading="!!codelistPagedState[item.name]?.loading"
                              :page="codelistPagedState[item.name]?.page ?? 1"
                              :items-per-page="codelistPagedState[item.name]?.perPage ?? 25"
                              :items-per-page-options="[10, 25, 50, 100, 200]"
                              density="comfortable"
                              class="elevation-0 ds-codelist-table"
                              hover
                              @update:options="(opts) => onCodelistOptionsUpdate(item, opts)"
                            >
                              <template #no-data>
                                <span class="text-body-2 text-medium-emphasis">No codelist items match this search.</span>
                              </template>
                            </v-data-table-server>
                          </v-sheet>
                        </div>
                      </td>
                    </tr>
                  </template>
                </v-data-table>
              </v-sheet>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { usePublicTimeseriesApi } from '../composables/usePublicTimeseriesApi';

defineOptions({ name: 'IndicatorStructureTab' });

const { config } = useAppConfig();
const studyIdno = computed(() => String(config.value?.studyIdno ?? '').trim());
const { dataPath, fetchSchema, fetchCodelistItemsPaged } = usePublicTimeseriesApi(studyIdno);

const pageLoading = ref(true);
const fatalError = ref('');
const schema = ref(null);

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

const dataStructureComponents = computed(() =>
  componentsSorted.value.map((c) => ({
    name: c?.name ?? '',
    label: c?.label || c?.title || '',
    role: c?.column_type || '',
    data_type: c?.data_type || '',
    codelist_id: c?.codelist_id || null,
    codelist_idno: c?.codelist_idno || c?.codelist_name || '',
    sort_order: Number(c?.sort_order) || 0,
  }))
);

function downloadDataStructureJson() {
  if (!studyIdno.value) return;
  const baseName = studyIdno.value || 'data-structure';
  const filename = `${String(baseName).replace(/[^\w.-]+/g, '_')}-dsd.json`;
  const endpointUrl = `${dataPath()}/export?_nocache=${Date.now()}`;
  const link = document.createElement('a');
  link.href = endpointUrl;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function downloadDataStructureXml() {
  if (!studyIdno.value) return;
  const baseName = studyIdno.value || 'data-structure';
  const filename = `${String(baseName).replace(/[^\w.-]+/g, '_')}-dsd.xml`;
  const endpointUrl = `${dataPath()}/export-xml?_nocache=${Date.now()}`;
  const link = document.createElement('a');
  link.href = endpointUrl;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function dsComponentsRowProps({ item }) {
  if (!item?.codelist_id) {
    return {};
  }
  return {
    style: { cursor: 'pointer' },
    onClick: () => {
      const name = item.name;
      const cur = [...dsExpanded.value];
      const i = cur.indexOf(name);
      if (i >= 0) cur.splice(i, 1);
      else cur.push(name);
      dsExpanded.value = cur;
      onDsExpandedUpdate(cur);
    },
  };
}

const dsTableHeaders = [
  { title: '', key: 'data-table-expand', width: 48, sortable: false },
  { title: 'Name', key: 'name', sortable: false },
  { title: 'Label', key: 'label', sortable: false },
  { title: 'Role', key: 'role', sortable: false },
  { title: 'Type', key: 'data_type', sortable: false },
  { title: 'Codelist', key: 'codelist', sortable: false },
];

const codelistTableHeaders = [
  { title: 'Code', key: 'value', sortable: true, width: 200 },
  { title: 'Label', key: 'title', sortable: true },
];

const dsExpanded = ref([]);
const codelistFilters = reactive({});
const codelistPagedState = reactive({});
const codelistDebounceTimers = {};

function codelistTotalForComponent(item) {
  if (!item?.codelist_id) return null;
  const st = codelistPagedState[item.name];
  if (st && Number.isFinite(st.total)) return st.total;
  return null;
}

function ensureCodelistState(componentName) {
  if (!codelistPagedState[componentName]) {
    codelistPagedState[componentName] = {
      items: [],
      total: 0,
      page: 1,
      perPage: 25,
      loading: false,
      search: '',
      requestId: 0,
      ctrl: null,
    };
  }
  return codelistPagedState[componentName];
}

async function loadCodelistPage(componentName, codelistId, { page, perPage, search }) {
  if (!componentName || !codelistId) return;
  const st = ensureCodelistState(componentName);
  if (st.ctrl) {
    try {
      st.ctrl.abort();
    } catch {
      /* ignore */
    }
  }
  const ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
  const reqId = ++st.requestId;
  st.ctrl = ctrl;
  st.loading = true;
  try {
    const offset = (Math.max(1, page) - 1) * perPage;
    const res = await fetchCodelistItemsPaged({
      id: codelistId,
      offset,
      limit: perPage,
      search,
      signal: ctrl?.signal,
    });
    if (reqId !== st.requestId) return;
    st.items = (res.items || []).map((it) => ({
      value: it.code ?? it.value ?? '',
      title: it.title ?? it.label ?? '',
    }));
    st.total = res.total;
    if (res.limit > 0) {
      st.perPage = res.limit;
      st.page = Math.floor(res.offset / res.limit) + 1;
    } else {
      st.page = page;
      st.perPage = perPage;
    }
    st.search = search;
  } catch (err) {
    if (err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED' || err?.name === 'AbortError') return;
    if (reqId !== st.requestId) return;
    st.items = [];
    st.total = 0;
  } finally {
    if (reqId === st.requestId) {
      st.loading = false;
      st.ctrl = null;
    }
  }
}

function onCodelistOptionsUpdate(item, opts) {
  if (!item?.codelist_id) return;
  const st = ensureCodelistState(item.name);
  const page = Math.max(1, Number(opts?.page) || 1);
  const perPage = Math.max(1, Number(opts?.itemsPerPage) || st.perPage || 25);
  if (page === st.page && perPage === st.perPage && st.items.length && !st.loading) {
    return;
  }
  loadCodelistPage(item.name, item.codelist_id, {
    page,
    perPage,
    search: codelistFilters[item.name] ?? '',
  });
}

function onCodelistSearchInput(item, value) {
  codelistFilters[item.name] = value ?? '';
  clearTimeout(codelistDebounceTimers[item.name]);
  codelistDebounceTimers[item.name] = setTimeout(() => {
    const st = ensureCodelistState(item.name);
    loadCodelistPage(item.name, item.codelist_id, {
      page: 1,
      perPage: st.perPage || 25,
      search: codelistFilters[item.name] ?? '',
    });
  }, 250);
}

function onDsExpandedUpdate(expanded) {
  const list = Array.isArray(expanded) ? expanded : [];
  for (const name of list) {
    const c = dataStructureComponents.value.find((x) => x.name === name);
    if (!c?.codelist_id) continue;
    const st = ensureCodelistState(c.name);
    if (!st.items.length && !st.loading) {
      loadCodelistPage(c.name, c.codelist_id, {
        page: 1,
        perPage: st.perPage || 25,
        search: '',
      });
    }
  }
}

watch(
  dataStructureComponents,
  (rows) => {
    const allowed = new Set(rows.filter((r) => r.codelist_id).map((r) => r.name));
    const next = dsExpanded.value.filter((n) => allowed.has(n));
    if (next.length !== dsExpanded.value.length) {
      dsExpanded.value = next;
    }
  },
  { deep: true }
);

function dsRoleChipColor(role) {
  switch (String(role || '').toLowerCase()) {
    case 'observation_value':
      return 'success';
    case 'time_period':
      return 'info';
    case 'geography':
      return 'primary';
    case 'periodicity':
      return 'secondary';
    case 'dimension':
      return 'warning';
    default:
      return 'default';
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
    schema.value = await fetchSchema();
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

.field-label {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  color: rgba(var(--v-theme-on-surface), 0.65);
  margin-bottom: 0.35rem;
}

.main-panel {
  min-height: 420px;
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

.data-shell {
  background: rgb(var(--v-theme-surface));
}

.ds-summary {
  background: rgba(var(--v-theme-on-surface), 0.025);
}

.ds-summary__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem 1.5rem;
}

.ds-summary__value {
  font-size: 0.95rem;
  font-weight: 500;
  margin-top: 0.15rem;
  word-break: break-word;
}

.ds-summary__desc {
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  padding-top: 0.85rem;
}

.ds-components-table {
  max-height: none;
  overflow: visible;
}

.ds-components-table :deep(th) {
  font-weight: 600 !important;
  font-size: 0.75rem !important;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55) !important;
}

.ds-expand-row > td {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.ds-expand-panel {
  border-top: 1px dashed rgba(var(--v-theme-on-surface), 0.12);
}

.ds-codelist-search {
  max-width: 320px;
  flex: 1 1 220px;
}

.ds-codelist-table {
  max-height: 360px;
  overflow: auto;
}

.ds-codelist-table :deep(th) {
  font-weight: 600 !important;
  font-size: 0.7rem !important;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55) !important;
}
</style>
