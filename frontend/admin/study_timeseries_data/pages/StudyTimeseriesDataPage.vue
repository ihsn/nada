<template>
  <div class="indicator-data-tab">
    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-4" />

    <v-alert v-if="fatalError && !missingDsd" type="error" class="mb-4" prominent>
      {{ fatalError }}
    </v-alert>

    <!-- Single form: DSD + CSV import -->
    <v-card
      v-if="!pageLoading && !fatalError && (missingDsd || schema)"
      variant="flat"
      rounded="0"
      class="section-card mb-4 pa-0"
      tag="section"
    >
      <v-card-item class="indicator-data-header px-4 pt-4 pb-2">
        <div class="d-flex flex-wrap align-start justify-space-between gap-3">
          <div class="min-width-0 flex-grow-1">
            <v-card-title class="text-subtitle-1 pa-0">Indicator data</v-card-title>
            <v-card-subtitle class="pa-0 mt-1 text-wrap">
              <template v-if="hasIndicatorObservations">
                Observations are loaded. Importing a new CSV replaces all existing rows for this study.
              </template>
              <template v-else>
                Choose a data structure, then upload a CSV.
              </template>
            </v-card-subtitle>
          </div>
          <v-btn
            v-if="hasLinkedDsd"
            color="error"
            variant="tonal"
            size="small"
            class="text-none flex-shrink-0"
            :loading="linkSaving"
            :disabled="linkSaving || importLoading || clearIndicatorLoading || resyncLoading"
            @click="removeStructureAndData"
          >
            Remove structure & data
          </v-btn>
        </div>
      </v-card-item>

      <v-card-text>
        <div class="text-subtitle-2 font-weight-medium mb-3">Data structure</div>

        <div class="field-group">
          <div class="field-label-row d-flex flex-wrap align-center justify-space-between gap-y-1">
            <label class="field-label field-label--inline" for="indicator-dsd-catalogue">Select data structure</label>
            <div class="d-flex flex-wrap align-center justify-end gap-x-1 gap-y-1 flex-shrink-0">
              <template v-if="schema">
                <a
                  v-if="dsdAdminHref && dsdAdminHref !== '#'"
                  :href="dsdAdminHref"
                  class="text-caption text-primary text-decoration-none field-inline-link"
                  target="_blank"
                  rel="noopener"
                >
                  Open DSD
                </a>
                <v-btn
                  variant="text"
                  size="small"
                  density="compact"
                  prepend-icon="mdi-download"
                  class="text-caption text-primary px-1 text-none field-inline-link"
                  :disabled="missingDsd || !componentsSorted.length"
                  @click="downloadCsvTemplate"
                >
                  Download template
                </v-btn>
              </template>
            </div>
          </div>
          <div v-if="!dsdSelectItems.length">
            <v-btn variant="tonal" size="small" :loading="dsdListLoading" @click="loadDsdCatalogueList">Load catalogue</v-btn>
          </div>
          <v-autocomplete
            v-else
            id="indicator-dsd-catalogue"
            v-model="dsdPickValue"
            :items="dsdSelectItems"
            item-title="title"
            item-value="value"
            placeholder="Search or select…"
            density="compact"
            variant="outlined"
            :clearable="!!schema?.data_structure?.idno"
            hide-details
            :loading="linkSaving"
            :disabled="linkSaving"
            @update:model-value="onDsdPick"
          />
        </div>

        <v-alert
          v-if="importFeedback"
          :type="importFeedbackType"
          density="compact"
          variant="tonal"
          class="mb-3"
          closable
          @click:close="importFeedback = ''"
        >
          {{ importFeedback }}
        </v-alert>

        <div class="field-group field-group-upload" :class="{ 'mt-4': !schema }">
          <label v-if="missingDsd" class="field-label text-wrap" for="indicator-csv-upload">
            Attach a data structure above to enable file upload (CSV).
          </label>
          <template v-else>
            <div class="text-subtitle-2 font-weight-medium mb-3">Upload data (CSV)</div>
            <p v-if="schema && !missingDsd" class="text-body-2 text-medium-emphasis mb-2 mt-1">
              Headers must match DSD component names (case-insensitive); other columns are skipped.
            </p>
          </template>
          <div class="csv-upload-row d-flex flex-wrap align-start gap-3">
            <div class="csv-upload-row__field flex-grow-1 min-width-0">
              <v-file-input
                id="indicator-csv-upload"
                v-model="importFile"
                accept=".csv,text/csv"
                density="compact"
                variant="outlined"
                :prepend-icon="false"
                prepend-inner-icon="mdi-paperclip"
                show-size
                clearable
                hide-details
                placeholder="Choose file…"
                aria-label="Indicator data CSV file"
                :disabled="missingDsd"
              />
            </div> &nbsp;
            <v-btn
              color="primary"
              prepend-icon="mdi-upload"
              class="csv-upload-row__import flex-shrink-0"
              :loading="importLoading"
              :disabled="missingDsd || !importFileModel"
              @click="submitCsvImport"
            >
              Import
            </v-btn>
          </div>
          <div
            v-if="hasIndicatorObservations"
            class="csv-upload-attached d-flex flex-wrap align-center justify-space-between gap-2"
          >
            <div class="d-flex flex-wrap align-center gap-2 min-width-0">
              <v-chip size="small" variant="flat" label class="data-attached-chip">
                Data attached {{ observationListTotal.toLocaleString() }}
              </v-chip>
              <v-btn
                variant="text"
                density="compact"
                size="small"
                color="error"
                class="text-none px-2"
                :loading="clearIndicatorLoading"
                @click="confirmClearIndicatorData"
              >
                Remove data
              </v-btn>
            </div>
            <div class="d-flex flex-wrap align-center gap-0 flex-shrink-0">
              <v-btn
                v-if="canDownloadIndicatorCsv"
                variant="text"
                density="compact"
                size="small"
                class="text-none px-2"
                :href="indicatorImportCsvUrl"
                tag="a"
              >
                Download data
              </v-btn>
            </div>
          </div>
        </div>

      </v-card-text>
    </v-card>

    <v-alert
      v-if="schema && Number(schema.ts_sync_required) === 1 && hasIndicatorObservations"
      type="warning"
      variant="tonal"
      density="comfortable"
      class="mb-4"
    >
      <div class="d-flex flex-wrap align-center gap-3">
        <div class="flex-grow-1">
          <strong>Out of sync</strong> — indicator data is not cleared for the public catalogue until you resync or import.
          Public timeseries APIs return “unavailable” while this flag is set. Resync updates keys from existing Mongo rows
          for the <em>current</em> linked structure; use Import if you changed structure or need to replace values.
          <span v-if="schema.ts_dimensions" class="text-body-2 text-medium-emphasis d-block mt-1">
            Dimensions: <code>{{ schema.ts_dimensions }}</code>
          </span>
        </div>
        <v-btn color="primary" variant="flat" size="small" :loading="resyncLoading" @click="runResyncRehash"> Resync </v-btn>
      </div>
    </v-alert>

    <!-- Preview & dimension summaries (only when observations exist) -->
    <v-card v-if="schema && !pageLoading && hasIndicatorObservations" variant="flat" rounded="0" class="section-card pa-0">
      <div class="indicator-data-tab-panels">
        <v-tabs v-model="mainTab" color="primary" class="px-2" aria-label="Indicator data views">
          <v-tab value="preview">Data preview</v-tab>
          <v-tab value="value-counts">Dimension summaries</v-tab>
        </v-tabs>
        <v-window v-model="mainTab">
        <v-window-item value="preview" class="pa-4">
          <div class="d-flex flex-wrap align-center justify-end gap-2 mb-3">
            <v-btn-toggle v-model="sortDir" mandatory density="compact" variant="outlined" divided>
              <v-btn value="asc">Time ↑</v-btn>
              <v-btn value="desc">Time ↓</v-btn>
            </v-btn-toggle>
          </div>
          <v-data-table
            :headers="tableHeaders"
            :items="displayRows"
            :loading="obsLoading"
            density="compact"
            class="elevation-0 indicator-data-grid"
            hide-default-footer
          />
          <div class="d-flex flex-wrap align-center justify-space-between gap-2 mt-3">
            <span class="text-body-2 text-medium-emphasis">
              Showing {{ observationRows.length ? offset + 1 : 0 }}–{{ offset + observationRows.length }} of
              {{ observationListTotal }}
            </span>
            <div class="d-flex gap-2">
              <v-btn size="small" variant="tonal" :disabled="offset <= 0 || obsLoading" @click="goPrev">Previous</v-btn>
              <v-btn
                size="small"
                variant="tonal"
                :disabled="offset + pageSize >= observationListTotal || obsLoading"
                @click="goNext"
              >
                Next
              </v-btn>
            </div>
          </div>
        </v-window-item>

        <v-window-item value="value-counts" class="pa-4">
          <div class="d-flex flex-wrap align-center gap-2 mb-3">
            <span class="text-body-2 text-medium-emphasis">Per-dimension code counts used for filters and faceted views</span>
            <v-spacer />
            <v-btn
              color="primary"
              variant="tonal"
              size="small"
              prepend-icon="mdi-sync"
              :loading="valueCountsSyncing"
              @click="runValueCountsSync"
            >
              Sync dimension summaries
            </v-btn>
          </div>
          <v-alert v-if="valueCountsError" type="warning" density="compact" variant="tonal" class="mb-3">
            {{ valueCountsError }}
          </v-alert>
          <v-progress-linear v-if="valueCountsLoading" indeterminate color="primary" class="mb-3" />
          <div class="d-flex flex-wrap gap-2 mb-3">
            <v-chip size="small" variant="tonal">Distinct codes: {{ valueCountsSummary.total_distinct_codes || 0 }}</v-chip>
            <v-chip size="small" variant="tonal">Observations: {{ valueCountsSummary.total_observations || 0 }}</v-chip>
            <v-chip size="small" variant="tonal">Components: {{ valueCountsSummary.components?.length || 0 }}</v-chip>
          </div>
          <v-data-table
            :headers="valueCountsHeaders"
            :items="valueCountsSummary.components || []"
            density="compact"
            class="elevation-0 indicator-data-grid"
            hide-default-footer
          />
        </v-window-item>
      </v-window>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch, nextTick } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyTimeseriesApi } from '../composables/useStudyTimeseriesApi';

defineOptions({ name: 'StudyTimeseriesDataPage' });

const setMessage = inject('setMessage', () => {});

const { config, apiBaseUrl, dataStructuresApiBaseUrl } = useAppConfig();
const studyIdno = computed(() => String(config.value?.studyIdno ?? '').trim());

const {
  fetchSchema,
  fetchObservationCount,
  fetchData,
  fetchValueCountsSummary,
  syncValueCounts,
    importCsvData,
    rehashData,
    clearIndicatorData,
  } = useStudyTimeseriesApi(studyIdno);

const pageLoading = ref(true);
const obsLoading = ref(false);
const fatalError = ref('');
/** True when API reports no linked DSD — show link form instead of blocking error only. */
const missingDsd = ref(false);
const dsdIdnoDraft = ref('');
const dsdPickValue = ref(null);
const dsdSelectItems = ref([]);
const dsdListLoading = ref(false);
const linkSaving = ref(false);
/** Suppress onDsdPick while syncing v-model from schema after load. */
const dsdPickSyncing = ref(false);
/** Inner tabs: preview (default) | dimension summaries (tab id: value-counts) */
const mainTab = ref('preview');

const schema = ref(null);
const observationRows = ref([]);
const sortDir = ref('asc');
const offset = ref(0);
/** Total rows for current list filter (from data response; falls back to catalogue count). */
const observationListTotal = ref(0);
const pageSize = 25;
const csvDelimiter = ',';

/** v-file-input model: File | File[] | null */
const importFile = ref(null);
const importLoading = ref(false);
const clearIndicatorLoading = ref(false);
const resyncLoading = ref(false);
/** Inline import result or error (API messages show here even if global toast is missed). */
const importFeedback = ref('');
const importFeedbackType = ref('success');
const valueCountsLoading = ref(false);
const valueCountsSyncing = ref(false);
const valueCountsError = ref('');
const valueCountsSummary = ref({
  total_rows: 0,
  total_distinct_codes: 0,
  total_observations: 0,
  components: [],
});

const importFileModel = computed(() => {
  const f = importFile.value;
  if (!f) return null;
  return Array.isArray(f) ? f[0] ?? null : f;
});

const hasIndicatorObservations = computed(
  () => !!schema.value && !missingDsd.value && observationListTotal.value > 0
);

/** Linked DSD present — show detach + purge control */
const hasLinkedDsd = computed(() => !!schema.value?.data_structure?.idno);

const indicatorImportCsvUrl = computed(() => {
  const base = (apiBaseUrl.value || '').replace(/\/$/, '');
  const id = studyIdno.value;
  if (!base || !id) return '#';
  return `${base}/data/${encodeURIComponent(id)}/import-csv`;
});

/** Canonical import file exists on disk (schema from GET …/schema). */
const canDownloadIndicatorCsv = computed(
  () =>
    !!schema.value?.indicator_import_csv_filename && schema.value?.indicator_import_csv_present === true
);

const dsdAdminHref = computed(() => {
  const base = (config.value?.dataStructuresAdminUrl || '').replace(/\/$/, '');
  const id = schema.value?.dsd_id;
  if (!base || id == null) return '#';
  return `${base}#/structure/${encodeURIComponent(id)}`;
});

const dataStructuresAdminHref = computed(() => {
  const base = (config.value?.dataStructuresAdminUrl || '').replace(/\/$/, '');
  return base || '#';
});

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

const tableHeaders = computed(() => {
  const keys = ['key_hash', 'sid'];
  for (const c of componentsSorted.value) {
    if (c?.name) keys.push(c.name);
  }
  keys.push('_ts_period_start');
  return keys
    .filter((k) => k !== 'key_hash' && !String(k).startsWith('_'))
    .map((k) => ({ title: k, key: k, sortable: false }));
});

/** Flatten cell values for v-data-table (handles BSON date shapes). */
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

const valueCountsHeaders = [
  { title: 'Component', key: 'component_name', sortable: false },
  { title: 'Distinct codes', key: 'distinct_codes', sortable: false },
  { title: 'Observations', key: 'observations', sortable: false },
];

function isMissingDsdMessage(msg) {
  return String(msg || '').includes('No data structure linked');
}

/** Ensure linked study DSD appears in the autocomplete list (e.g. after catalogue load). */
function ensureCurrentDsdInCatalogue() {
  const ds = schema.value?.data_structure;
  if (!ds?.idno) return;
  const idno = String(ds.idno).trim();
  const exists = dsdSelectItems.value.some((item) => String(item?.value || '') === idno);
  if (exists) return;
  const title = ds.title || ds.name || idno;
  const row = {
    title: `${title} — ${idno}`,
    value: idno,
    id: schema.value?.dsd_id != null ? Number(schema.value.dsd_id) : null,
    reference: {
      idno,
      agency: String(ds.agency ?? '').trim(),
      name: String(ds.name ?? '').trim(),
      version: String(ds.version ?? '').trim(),
    },
  };
  dsdSelectItems.value = [row, ...dsdSelectItems.value];
}

/** Sync dropdown model from loaded schema (and skip pick handler while applying). */
async function finalizeDsdPickerState() {
  if (schema.value?.data_structure?.idno) {
    if (!dsdSelectItems.value.length) {
      await loadDsdCatalogueList();
    }
    ensureCurrentDsdInCatalogue();
    dsdPickSyncing.value = true;
    dsdPickValue.value = String(schema.value.data_structure.idno).trim();
    dsdIdnoDraft.value = dsdPickValue.value;
    await nextTick();
    dsdPickSyncing.value = false;
  } else {
    dsdPickSyncing.value = true;
    dsdPickValue.value = null;
    dsdIdnoDraft.value = '';
    await nextTick();
    dsdPickSyncing.value = false;
  }
}

async function onDsdPick(v) {
  if (dsdPickSyncing.value) return;
  if (v == null || v === '') {
    dsdIdnoDraft.value = '';
    if (schema.value?.data_structure?.idno) {
      const detached = await detachDsdLink();
      if (!detached) {
        await finalizeDsdPickerState();
      }
    }
    return;
  }
  if (linkSaving.value) return;
  const idno = String(v).trim();
  const current = schema.value?.data_structure?.idno ? String(schema.value.data_structure.idno).trim() : '';
  if (schema.value && current === idno) {
    dsdIdnoDraft.value = idno;
    return;
  }
  dsdIdnoDraft.value = idno;
  const hasObs = observationListTotal.value > 0;
  if (current && hasObs && idno !== current) {
    const ok = window.confirm(
      'Change the linked data structure? All indicator observations and the stored import CSV for this study will be removed. Public indicator views stay hidden until you import again or resync. Continue?'
    );
    if (!ok) {
      await finalizeDsdPickerState();
      return;
    }
  }
  await saveDsdLink();
}

async function loadDsdCatalogueList() {
  const base = (dataStructuresApiBaseUrl.value || '').replace(/\/$/, '');
  if (!base) {
    setMessage('Catalogue API URL is not configured.', 'warning');
    return;
  }
  dsdListLoading.value = true;
  try {
    const { data } = await axios.get(base, {
      params: { with_components: '0' },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'List failed');
    const rows = data.result?.data_structures ?? [];
    dsdSelectItems.value = rows
      .filter((r) => r?.idno && r?.agency && r?.name && r?.version)
      .map((r) => ({
        title: `${r.title || r.name || '—'} — ${r.idno}`,
        value: String(r.idno).trim(),
        id: Number(r.id) || null,
        reference: {
          idno: String(r.idno).trim(),
          agency: String(r.agency).trim(),
          name: String(r.name).trim(),
          version: String(r.version).trim(),
        },
      }));
    if (!dsdSelectItems.value.length) {
      setMessage('No data structures returned (empty catalogue or no access).', 'info');
    }
    if (schema.value?.data_structure?.idno) {
      ensureCurrentDsdInCatalogue();
      dsdPickSyncing.value = true;
      dsdPickValue.value = String(schema.value.data_structure.idno).trim();
      await nextTick();
      dsdPickSyncing.value = false;
    }
  } catch (e) {
    dsdSelectItems.value = [];
    setMessage(e?.response?.data?.message || e?.message || 'Could not load DSD list (admin access may be required).', 'warning');
  } finally {
    dsdListLoading.value = false;
  }
}

async function postDsdLink(idno) {
  const base = (apiBaseUrl.value || '').replace(/\/$/, '');
  if (!base) {
    setMessage('Timeseries API URL is not configured.', 'error');
    return false;
  }
  const url = `${base}/data/${encodeURIComponent(studyIdno.value)}/attach-dsd`;
  const selected = dsdSelectItems.value.find((item) => String(item?.value || '') === String(idno));
  const resolvedIdno = String(selected?.reference?.idno || idno || '').trim();
  if (!resolvedIdno) {
    throw new Error('Selected DSD is missing idno.');
  }
  const payload = {
    data_structure_reference: resolvedIdno,
  };
  if (selected?.id) {
    payload.data_structure_id = Number(selected.id);
  }
  const { data } = await axios.post(url, payload, { headers: { 'Content-Type': 'application/json' }, withCredentials: true });
  if (data.status && data.status !== 'success') {
    throw new Error(data.message || 'Update failed');
  }
  return true;
}

async function postDsdDetach() {
  const base = (apiBaseUrl.value || '').replace(/\/$/, '');
  if (!base) {
    setMessage('Timeseries API URL is not configured.', 'error');
    return false;
  }
  const url = `${base}/data/${encodeURIComponent(studyIdno.value)}/attach-dsd`;
  const { data } = await axios.post(
    url,
    { detach: true },
    { headers: { 'Content-Type': 'application/json' }, withCredentials: true }
  );
  if (data.status && data.status !== 'success') {
    throw new Error(data.message || 'Update failed');
  }
  return true;
}

async function saveDsdLink() {
  const idno = String(dsdIdnoDraft.value || '').trim();
  if (!idno) {
    setMessage('Select a DSD from the dropdown.', 'warning');
    return;
  }
  linkSaving.value = true;
  try {
    const wasLinked = !!(schema.value?.data_structure?.idno);
    await postDsdLink(idno);
    setMessage(wasLinked ? 'Data structure updated.' : 'Data structure linked.', 'success');
    await loadAll();
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Could not save link.';
    setMessage(msg, 'error');
    await finalizeDsdPickerState();
  } finally {
    linkSaving.value = false;
  }
}

/** @returns {Promise<boolean>} true if detached */
async function detachDsdLink() {
  if (!window.confirm('Remove the linked DSD from this study?')) {
    return false;
  }
  linkSaving.value = true;
  try {
    await postDsdDetach();
    dsdIdnoDraft.value = '';
    setMessage('Data structure removed.', 'success');
    await loadAll();
    return true;
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Could not detach DSD.';
    setMessage(msg, 'error');
    await finalizeDsdPickerState();
    return false;
  } finally {
    linkSaving.value = false;
  }
}

/** Detach DSD and purge indicator data (same backend path as clearing the DSD picker). */
async function removeStructureAndData() {
  if (!hasLinkedDsd.value) return;
  const ok = window.confirm(
    'Remove the linked data structure and all indicator data for this study?\n\n' +
      'This deletes all observations, dimension summaries, and the stored import CSV. ' +
      'Public indicator views will be unavailable until you link a structure and import again. ' +
      'This cannot be undone.\n\nContinue?'
  );
  if (!ok) return;
  linkSaving.value = true;
  try {
    await postDsdDetach();
    dsdIdnoDraft.value = '';
    setMessage('Data structure and indicator data removed.', 'success');
    await loadAll();
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Could not remove structure and data.';
    setMessage(msg, 'error');
    await finalizeDsdPickerState();
  } finally {
    linkSaving.value = false;
  }
}

async function runResyncRehash() {
  if (!studyIdno.value || !schema.value?.dsd_id) {
    setMessage('Link a DSD first.', 'warning');
    return;
  }
  resyncLoading.value = true;
  try {
    const res = await rehashData({});
    const updated = typeof res.updated === 'number' ? res.updated : 0;
    setMessage(`Resync complete: ${updated} observation(s) updated.`, 'success');
    await loadAll();
  } catch (e) {
    const msg = e?.message || e?.response?.data?.message || 'Rehash failed.';
    setMessage(msg, 'error');
  } finally {
    resyncLoading.value = false;
  }
}

function downloadCsvTemplate() {
  const sep = csvDelimiter;
  const names = componentsSorted.value.map((c) => c.name).filter(Boolean);
  if (!names.length) {
    setMessage('No component names for template.', 'warning');
    return;
  }
  const header = names.join(sep);
  const blob = new Blob([`${header}\n`], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `observations_template_${schema.value?.idno || 'study'}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

async function submitCsvImport() {
  const file = importFileModel.value;
  if (!file) {
    setMessage('Choose a CSV file.', 'warning');
    return;
  }
  if (observationListTotal.value > 0) {
    const ok = window.confirm(
      'This import will replace all existing indicator observations for this study with the rows in your CSV. Continue?'
    );
    if (!ok) return;
  }
  importLoading.value = true;
  importFeedback.value = '';
  try {
    const res = await importCsvData({
      file,
      delimiter: csvDelimiter,
      ensureUniqueIndex: true,
    });
    const inserted = res.inserted ?? 0;
    const lines = res.lines_read ?? '—';
    const ignored = Array.isArray(res.ignored_columns) ? res.ignored_columns : [];
    let detail = `Imported ${inserted} observation(s) (lines read: ${lines}).`;
    if (res.resource_saved === false) {
      detail += ' CSV was not attached as a study resource (check survey folder / validation logs).';
      importFeedbackType.value = 'warning';
    }
    if (ignored.length) {
      const shown = ignored.slice(0, 20).join(', ');
      const more = ignored.length > 20 ? ` (+${ignored.length - 20} more)` : '';
      detail += ` Skipped ${ignored.length} non-DSD column(s): ${shown}${more}.`;
    }
    if (res.resource_saved !== false) {
      importFeedbackType.value = ignored.length ? 'info' : 'success';
    }
    importFeedback.value = detail;
    setMessage(`Imported ${inserted} observation(s).`, 'success');
    importFile.value = null;
    mainTab.value = 'preview';
    await loadAll();
  } catch (e) {
    const msg = e?.message || e?.response?.data?.message || 'Import failed.';
    importFeedbackType.value = 'error';
    importFeedback.value = msg;
    setMessage(msg, 'error');
  } finally {
    importLoading.value = false;
  }
}

async function confirmClearIndicatorData() {
  const ok = window.confirm(
    'Remove all indicator observations, dimension summaries, and the stored import CSV for this study? The linked data structure will stay attached.'
  );
  if (!ok) return;
  clearIndicatorLoading.value = true;
  importFeedback.value = '';
  try {
    await clearIndicatorData();
    importFeedbackType.value = 'success';
    importFeedback.value = 'Indicator data removed.';
    setMessage('Indicator data removed.', 'success');
    await loadAll();
  } catch (e) {
    const msg = e?.message || e?.response?.data?.message || 'Could not remove data.';
    importFeedbackType.value = 'error';
    importFeedback.value = msg;
    setMessage(msg, 'error');
  } finally {
    clearIndicatorLoading.value = false;
  }
}

async function loadValueCountsSummary() {
  if (!studyIdno.value || !schema.value?.dsd_id) {
    valueCountsSummary.value = { total_rows: 0, total_distinct_codes: 0, total_observations: 0, components: [] };
    valueCountsError.value = '';
    return;
  }
  valueCountsLoading.value = true;
  valueCountsError.value = '';
  try {
    const summary = await fetchValueCountsSummary();
    valueCountsSummary.value = {
      total_rows: Number(summary?.total_rows) || 0,
      total_distinct_codes: Number(summary?.total_distinct_codes) || 0,
      total_observations: Number(summary?.total_observations) || 0,
      components: Array.isArray(summary?.components) ? summary.components : [],
    };
  } catch (e) {
    valueCountsSummary.value = { total_rows: 0, total_distinct_codes: 0, total_observations: 0, components: [] };
    valueCountsError.value = e?.response?.data?.message || e?.message || 'Could not load dimension summaries.';
  } finally {
    valueCountsLoading.value = false;
  }
}

async function runValueCountsSync() {
  if (!studyIdno.value || !schema.value?.dsd_id) {
    setMessage('No linked DSD to sync.', 'warning');
    return;
  }
  valueCountsSyncing.value = true;
  try {
    const res = await syncValueCounts();
    const inserted = Number(res?.inserted) || 0;
    setMessage(`Dimension summaries synced (${inserted} row(s)).`, 'success');
    await loadValueCountsSummary();
  } catch (e) {
    const msg = e?.message || e?.response?.data?.message || 'Could not sync dimension summaries.';
    setMessage(msg, 'error');
  } finally {
    valueCountsSyncing.value = false;
  }
}

function formatCell(val) {
  if (val === null || val === undefined) return '—';
  if (typeof val === 'object') {
    if (val.$date != null) return String(val.$date);
    const s = JSON.stringify(val);
    return s.length > 80 ? `${s.slice(0, 77)}…` : s;
  }
  return String(val);
}

async function reloadObservations() {
  if (!studyIdno.value) return;
  obsLoading.value = true;
  try {
    const res = await fetchData({
      limit: pageSize,
      offset: offset.value,
      sort: sortDir.value === 'desc' ? 'desc' : 'asc',
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

function goPrev() {
  offset.value = Math.max(0, offset.value - pageSize);
  reloadObservations();
}

function goNext() {
  offset.value = offset.value + pageSize;
  reloadObservations();
}

async function loadAll() {
  if (!studyIdno.value) {
    fatalError.value = 'Missing study IDNO in page configuration.';
    missingDsd.value = false;
    pageLoading.value = false;
    await finalizeDsdPickerState();
    return;
  }
  pageLoading.value = true;
  fatalError.value = '';
  missingDsd.value = false;
  try {
    const [sch, count] = await Promise.all([fetchSchema(), fetchObservationCount()]);
    schema.value = sch;
    offset.value = 0;
    observationListTotal.value = Number(count) || 0;
    await Promise.all([reloadObservations(), loadValueCountsSummary()]);
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Could not load timeseries data.';
    if (isMissingDsdMessage(msg)) {
      missingDsd.value = true;
      fatalError.value = '';
      schema.value = null;
      observationRows.value = [];
      observationListTotal.value = 0;
      valueCountsSummary.value = { total_rows: 0, total_distinct_codes: 0, total_observations: 0, components: [] };
      valueCountsError.value = '';
      if (!dsdSelectItems.value.length) {
        await loadDsdCatalogueList();
      }
    } else {
      fatalError.value = msg;
      schema.value = null;
      valueCountsSummary.value = { total_rows: 0, total_distinct_codes: 0, total_observations: 0, components: [] };
      valueCountsError.value = '';
    }
  } finally {
    await finalizeDsdPickerState();
    pageLoading.value = false;
  }
}

watch(sortDir, () => {
  offset.value = 0;
  reloadObservations();
});

onMounted(() => {
  loadAll();
});
</script>

<style scoped>
.indicator-data-tab {
  width: 100%;
  max-width: none;
  background-color: #fff;
  box-sizing: border-box;
}

/* Flat section cards: title block + body both solid white */
.section-card {
  background-color: #fff !important;
}

.section-card :deep(.v-card-item),
.section-card :deep(.v-card-text),
.section-card :deep(.v-tabs),
.section-card :deep(.v-window),
.section-card :deep(.v-window-item) {
  background-color: #fff !important;
}

/* Strip between tab underline and panel: slide-group + window inner wrappers stay transparent */
.indicator-data-tab-panels {
  background-color: #fff;
}

.section-card :deep(.v-slide-group),
.section-card :deep(.v-slide-group__container),
.section-card :deep(.v-slide-group__content),
.section-card :deep(.v-window__container) {
  background-color: #fff !important;
}

.min-width-0 {
  min-width: 0;
}

.field-group {
  margin-bottom: 12px;
}

.field-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.25rem;
  letter-spacing: 0.01em;
  color: rgba(var(--v-theme-on-surface), 0.68);
  margin-bottom: 6px;
}

.field-label--inline {
  margin-bottom: 0;
}

.field-label-row {
  margin-bottom: 6px;
}

.field-admin-icon {
  margin-top: -2px;
}

.field-inline-link:hover {
  text-decoration: underline !important;
}

.field-group :deep(.v-field) {
  font-size: 0.8125rem;
}

.field-group-upload {
  padding-bottom: 20px;
}

.csv-upload-row {
  margin-bottom: 16px;
}

.csv-upload-attached {
  margin-top: 12px;
}

.indicator-data-grid {
  margin-top: 16px;
  margin-bottom: 16px;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  overflow: hidden;
}

.indicator-data-grid :deep(thead th),
.indicator-data-grid :deep(.v-data-table__th) {
  background: rgba(var(--v-theme-on-surface), 0.06) !important;
  font-weight: 600 !important;
}

.indicator-data-grid :deep(.v-data-table__td) {
  border-color: rgba(var(--v-border-color), var(--v-border-opacity));
}

.data-attached-chip {
  background: rgba(var(--v-theme-success), 0.1) !important;
  color: rgba(var(--v-theme-on-surface), 0.62) !important;
  font-weight: 500;
  box-shadow: none !important;
}
</style>
