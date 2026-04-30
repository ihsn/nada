<template>
  <div class="indicator-data-tab">
    <v-alert v-if="missingDsd && !pageLoading" type="info" variant="tonal" density="compact" class="mb-5">
      No indicator data is attached yet. To attach data, first select and attach a data structure (DSD).
    </v-alert>

    <div class="d-flex flex-wrap align-center mb-5">
      <h2 class="text-h6 mb-0">Data structure</h2>
      <v-spacer />
      <div class="d-flex flex-wrap align-center gap-3">
        <v-btn
          v-if="schema?.dsd_id"
          size="small"
          variant="tonal"
          prepend-icon="mdi-swap-horizontal"
          @click="openChangeDsdDialog"
        >
          Change linked DSD
        </v-btn>
        <v-btn
          v-if="schema?.dsd_id"
          size="small"
          variant="tonal"
          prepend-icon="mdi-database-cog-outline"
          :href="dsdAdminHref"
          target="_blank"
          rel="noopener"
        >
          Open DSD
        </v-btn>
        <v-btn
          v-if="schema?.dsd_id"
          size="small"
          color="primary"
          variant="tonal"
          prepend-icon="mdi-database-sync"
          :loading="resyncLoading"
          @click="runResyncRehash"
        >
          Resync data
        </v-btn>
        <v-btn
          v-if="schema?.dsd_id"
          size="small"
          color="error"
          variant="text"
          prepend-icon="mdi-link-off"
          :loading="linkSaving"
          @click="detachDsdLink"
        >
          Detach DSD
        </v-btn>
      </div>
    </div>

    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-5" />

    <v-alert v-if="fatalError && !missingDsd" type="error" class="mb-5" prominent>
      {{ fatalError }}
    </v-alert>

    <v-card v-if="missingDsd && !pageLoading" variant="outlined" class="mb-5 pa-5 section-card">
      <v-autocomplete
        v-if="dsdSelectItems.length"
        v-model="dsdPickValue"
        :items="dsdSelectItems"
        item-title="title"
        item-value="value"
        label="Choose from catalogue"
        density="comfortable"
        variant="outlined"
        clearable
        hide-details
        class="dsd-picker"
        @update:model-value="onDsdPick"
      />
      <div class="d-flex flex-wrap align-center gap-2 action-row">
        <v-btn color="primary" :loading="linkSaving" @click="saveDsdLink">Attach</v-btn>
        <v-btn v-if="!dsdSelectItems.length" variant="text" :loading="dsdListLoading" @click="loadDsdCatalogueList">Load catalogue list</v-btn>
        <v-btn variant="text" :href="dataStructuresAdminHref" target="_blank" rel="noopener">Open data structures admin</v-btn>
      </div>
    </v-card>

    <template v-else-if="schema">
      <v-alert
        v-if="Number(schema.ts_sync_required) === 1"
        type="warning"
        variant="tonal"
        density="comfortable"
        class="ts-sync-alert"
      >
        <div class="ts-sync-alert__body">
          <div class="text-subtitle-2 font-weight-medium ts-sync-alert__title">Indicator data is out of sync</div>
          <p class="text-body-2 mb-2">
            The linked DSD changed after your last import or full rehash. Use the <strong>Resync data</strong> button at the
            top of this tab to recompute <code>key_hash</code> and related fields from your stored observations. Re-import
            the CSV only if you need to replace the underlying values.
          </p>
          <p v-if="schema.ts_dimensions" class="text-body-2 text-medium-emphasis mb-0">
            Current dimension fields (catalog): <code>{{ schema.ts_dimensions }}</code>
          </p>
        </div>
      </v-alert>

      <v-row class="panel-row mb-5">
        <v-col cols="12">
          <v-card variant="outlined" class="pa-5 h-100 section-card">
            <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
              <div class="text-subtitle-1 font-weight-medium">Data structure</div>
              <v-chip v-if="schema?.dsd_id" size="small" variant="tonal" color="primary">
                DSD ID: {{ schema.dsd_id }}
              </v-chip>
            </div>

            <div v-if="dataStructureTitle" class="text-body-1 font-weight-medium mb-2">
              {{ dataStructureTitle }}
            </div>
            <div v-else class="text-body-2 text-medium-emphasis mb-2">
              No linked DSD (set metadata.data_structure_reference).
            </div>

            <div v-if="schema.data_structure?.idno" class="d-flex align-center flex-wrap gap-2">
              <span class="text-body-2 text-medium-emphasis">IDNO</span>
              <v-chip size="small" variant="outlined">
                <code>{{ schema.data_structure.idno }}</code>
              </v-chip>
            </div>

          </v-card>
        </v-col>
      </v-row>

      <v-card variant="outlined" class="pa-0 section-card section-stack downloads-expand-card value-counts-section">
        <v-expansion-panels v-model="valueCountsPanel" flat multiple class="downloads-panels">
          <v-expansion-panel value="value-counts">
            <v-expansion-panel-title class="text-subtitle-1 font-weight-medium px-5 py-4">
              <div class="d-flex flex-wrap align-center gap-2 w-100 pr-2">
                <span>Value counts cache</span>
                <v-spacer />
                <v-btn
                  color="primary"
                  variant="tonal"
                  size="small"
                  prepend-icon="mdi-sync"
                  :loading="valueCountsSyncing"
                  @click.stop="runValueCountsSync"
                >
                  Sync value counts
                </v-btn>
              </div>
            </v-expansion-panel-title>
            <v-expansion-panel-text class="px-5 pb-5">
              <v-alert
                v-if="valueCountsError"
                type="warning"
                density="compact"
                variant="tonal"
                class="mb-3"
              >
                {{ valueCountsError }}
              </v-alert>
              <v-progress-linear v-if="valueCountsLoading" indeterminate color="primary" class="mb-3" />
              <div class="d-flex flex-wrap gap-2 mb-3">
                <v-chip size="small" variant="tonal">Distinct codes: {{ valueCountsSummary.total_distinct_codes || 0 }}</v-chip>
                <v-chip size="small" variant="tonal">Total observations: {{ valueCountsSummary.total_observations || 0 }}</v-chip>
                <v-chip size="small" variant="tonal">Components: {{ valueCountsSummary.components?.length || 0 }}</v-chip>
              </div>
              <v-data-table
                :headers="valueCountsHeaders"
                :items="valueCountsSummary.components || []"
                density="compact"
                class="elevation-0"
                hide-default-footer
              />
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </v-card>

      <v-card variant="outlined" class="pa-0 section-card section-stack downloads-expand-card">
        <v-expansion-panels v-model="importCsvPanel" flat multiple class="downloads-panels">
          <v-expansion-panel value="import-csv">
            <v-expansion-panel-title class="text-subtitle-1 font-weight-medium px-5 py-4">
              Import CSV
            </v-expansion-panel-title>
            <v-expansion-panel-text class="px-5 pb-5">
              <v-alert v-if="importFeedback" :type="importFeedbackType" density="compact" variant="tonal" class="mb-4" closable @click:close="importFeedback = ''">
                {{ importFeedback }}
              </v-alert>
              <p class="text-body-2 text-medium-emphasis mb-4">
                First row must be headers. Columns are matched to DSD component <code>name</code> (case-insensitive); optional JSON mapping below renames CSV headers.
                Columns that are not DSD fields (for example <code>_ts_year</code>) are skipped. Each successful import updates the study
                <code>resources</code> slot <code>ts_csv_latest</code> (configurable) so the CSV appears with other microdata/database files below. API: <code>POST …/data/import</code> (multipart: <code>idno</code>, <code>file</code>, …).
              </p>
              <v-row dense>
                <v-col cols="12" md="6">
                  <v-file-input
                    v-model="importFile"
                    label="CSV file"
                    accept=".csv,text/csv"
                    density="comfortable"
                    variant="outlined"
                    prepend-icon="mdi-paperclip"
                    show-size
                    clearable
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <p class="text-body-2 text-medium-emphasis mb-0 mt-1">
                    CSV headers are matched directly to DSD component names.
                  </p>
                </v-col>
              </v-row>
              <div class="d-flex flex-wrap gap-2 mt-4">
                <v-btn variant="tonal" prepend-icon="mdi-download" :disabled="!componentsSorted.length" @click="downloadCsvTemplate">
                  Download template CSV
                </v-btn>
                <v-btn
                  color="primary"
                  prepend-icon="mdi-upload"
                  :loading="importLoading"
                  :disabled="!importFileModel"
                  @click="submitCsvImport"
                >
                  Import
                </v-btn>
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </v-card>

      <v-card variant="outlined" class="pa-0 section-card section-stack downloads-expand-card">
        <v-expansion-panels v-model="downloadsPanel" flat multiple class="downloads-panels">
          <v-expansion-panel value="downloads">
            <v-expansion-panel-title class="text-subtitle-1 font-weight-medium px-5 py-4">
              Downloads
            </v-expansion-panel-title>
            <v-expansion-panel-text class="px-4 pb-4">
              <p class="text-body-2 text-medium-emphasis mb-4">
                Includes the latest indicator CSV (<code>resource_idno</code> e.g. <code>ts_csv_latest</code>) and other study files classified as microdata/database
                (<code>GET /api/downloads/{idno}/files?type=data</code>).
              </p>
              <v-progress-linear v-if="downloadsLoading" indeterminate color="primary" class="mb-4" />

              <div class="text-subtitle-2 font-weight-medium mb-2">Study microdata / database files</div>
              <v-data-table
                :headers="microdataHeaders"
                :items="bulkMicrodata"
                :loading="downloadsLoading"
                density="compact"
                class="elevation-0"
                hide-default-footer
              >
                <template #item.changed="{ item }">
                  {{ formatMicrodataDate(item.changed) }}
                </template>
                <template #item.actions="{ item }">
                  <v-btn
                    v-if="!item.external_link && item.links?.download"
                    size="small"
                    variant="tonal"
                    prepend-icon="mdi-download"
                    :href="item.links.download"
                    target="_blank"
                    rel="noopener"
                  >
                    {{ fileExtLabel(item.filename) }}
                  </v-btn>
                  <v-btn
                    v-else-if="item.external_link && item.filename"
                    size="small"
                    variant="tonal"
                    prepend-icon="mdi-open-in-new"
                    :href="item.filename"
                    target="_blank"
                    rel="noopener"
                  >
                    Link
                  </v-btn>
                  <span v-else class="text-medium-emphasis">—</span>
                </template>
              </v-data-table>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </v-card>

      <v-card variant="outlined" class="pa-5 section-card section-stack">
        <div class="d-flex flex-wrap align-center gap-2 mb-3">
          <span class="text-subtitle-1 font-weight-medium">Data preview</span>
          <v-spacer />
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
          class="elevation-0"
          hide-default-footer
        />

        <div class="d-flex flex-wrap align-center justify-space-between gap-2 mt-3">
          <span class="text-body-2 text-medium-emphasis">
            Showing {{ observationRows.length ? offset + 1 : 0 }}–{{ offset + observationRows.length }} of {{ observationListTotal }}
          </span>
          <div class="d-flex gap-2">
            <v-btn size="small" variant="tonal" :disabled="offset <= 0 || obsLoading" @click="goPrev">Previous</v-btn>
            <v-btn size="small" variant="tonal" :disabled="offset + pageSize >= observationListTotal || obsLoading" @click="goNext">
              Next
            </v-btn>
          </div>
        </div>
      </v-card>
    </template>

    <v-dialog v-model="changeDsdDialog" max-width="560" scrollable>
      <v-card>
        <v-card-title class="text-h6">Change linked data structure</v-card-title>
        <v-card-text>
          <v-alert v-if="observationCount > 0" type="warning" variant="tonal" density="compact" class="mb-4">
            This study already has <strong>{{ observationCount }}</strong> observation(s). Changing the DSD can break
            <code>key_hash</code> alignment or orphan existing Mongo documents. Prefer clearing or re-importing after a
            change if something looks wrong.
          </v-alert>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Pick another global DSD. This updates <code>metadata.data_structure_reference</code> for this study.
          </p>
          <v-autocomplete
            v-if="dsdSelectItems.length"
            v-model="dsdPickValue"
            :items="dsdSelectItems"
            item-title="title"
            item-value="value"
            label="Choose from catalogue"
            density="comfortable"
            variant="outlined"
            clearable
            hide-details
            class="dsd-picker"
            @update:model-value="onDsdPick"
          />
          <div class="d-flex flex-wrap gap-2 action-row">
            <v-btn v-if="!dsdSelectItems.length" variant="text" :loading="dsdListLoading" @click="loadDsdCatalogueList">Load catalogue list</v-btn>
            <v-btn variant="text" :href="dataStructuresAdminHref" target="_blank" rel="noopener">Open data structures admin</v-btn>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="changeDsdDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="linkSaving" @click="saveDsdLinkFromDialog">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyTimeseriesApi } from '../composables/useStudyTimeseriesApi';

defineOptions({ name: 'StudyTimeseriesDataPage' });

const setMessage = inject('setMessage', () => {});

const { config, apiBaseUrl, dataStructuresApiBaseUrl, siteUrl } = useAppConfig();
const studyIdno = computed(() => String(config.value?.studyIdno ?? '').trim());

const {
  fetchSchema,
  fetchObservationCount,
  fetchData,
  fetchValueCountsSummary,
  syncValueCounts,
  importCsvData,
  rehashData,
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
const changeDsdDialog = ref(false);

const schema = ref(null);
const observationCount = ref(0);
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

const downloadsLoading = ref(false);
const bulkMicrodata = ref([]);
/** Expansion panel model: empty = collapsed (default). Open panel uses value `downloads`. */
const downloadsPanel = ref([]);
/** Same pattern for Import CSV section. */
const importCsvPanel = ref([]);
/** Value counts cache section; collapsed by default. */
const valueCountsPanel = ref([]);

const microdataHeaders = [
  { title: 'Title', key: 'title', sortable: false },
  { title: 'File', key: 'filename', sortable: false },
  { title: 'Date', key: 'changed', sortable: false },
  { title: '', key: 'actions', sortable: false, width: 140 },
];

const importFileModel = computed(() => {
  const f = importFile.value;
  if (!f) return null;
  return Array.isArray(f) ? f[0] ?? null : f;
});

const dataStructureTitle = computed(() => {
  const ds = schema.value?.data_structure;
  if (!ds) return '';
  return ds.title || ds.name || '';
});

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
  return keys.map((k) => ({ title: k, key: k, sortable: false }));
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

function onDsdPick(v) {
  if (v != null && v !== '') {
    dsdIdnoDraft.value = String(v);
  }
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
  const { data } = await axios.post(
    url,
    payload,
    { headers: { 'Content-Type': 'application/json' }, withCredentials: true }
  );
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

async function openChangeDsdDialog() {
  dsdIdnoDraft.value = schema.value?.data_structure?.idno ? String(schema.value.data_structure.idno) : '';
  dsdPickValue.value = null;
  changeDsdDialog.value = true;
  if (!dsdSelectItems.value.length) {
    loadDsdCatalogueList();
  }
}

async function saveDsdLink() {
  const idno = String(dsdIdnoDraft.value || '').trim();
  if (!idno) {
    setMessage('Select a DSD from the dropdown.', 'warning');
    return;
  }
  linkSaving.value = true;
  try {
    await postDsdLink(idno);
    setMessage('Data structure linked. Reloading…', 'success');
    missingDsd.value = false;
    dsdPickValue.value = null;
    await loadAll();
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Could not save link.';
    setMessage(msg, 'error');
  } finally {
    linkSaving.value = false;
  }
}

async function saveDsdLinkFromDialog() {
  const idno = String(dsdIdnoDraft.value || '').trim();
  if (!idno) {
    setMessage('Select a DSD from the dropdown.', 'warning');
    return;
  }
  linkSaving.value = true;
  try {
    await postDsdLink(idno);
    setMessage('Linked data structure updated. Reloading…', 'success');
    changeDsdDialog.value = false;
    missingDsd.value = false;
    dsdPickValue.value = null;
    await loadAll();
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Could not save link.';
    setMessage(msg, 'error');
  } finally {
    linkSaving.value = false;
  }
}

async function detachDsdLink() {
  if (!window.confirm('Detach the linked DSD from this study?')) {
    return;
  }
  linkSaving.value = true;
  try {
    await postDsdDetach();
    changeDsdDialog.value = false;
    dsdIdnoDraft.value = '';
    dsdPickValue.value = null;
    setMessage('Data structure detached. Reloading…', 'success');
    await loadAll();
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Could not detach DSD.';
    setMessage(msg, 'error');
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
    await loadDownloadsPanel();
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
    await loadAll();
    await loadDownloadsPanel();
  } catch (e) {
    const msg = e?.message || e?.response?.data?.message || 'Import failed.';
    importFeedbackType.value = 'error';
    importFeedback.value = msg;
    setMessage(msg, 'error');
  } finally {
    importLoading.value = false;
  }
}

function formatMicrodataDate(changed) {
  if (!changed) return '—';
  try {
    const d = new Date(changed);
    return Number.isNaN(d.getTime()) ? String(changed) : d.toLocaleString();
  } catch {
    return String(changed);
  }
}

function fileExtLabel(filename) {
  if (!filename || typeof filename !== 'string') return 'Download';
  const ext = filename.split('.').pop();
  return ext && ext.length <= 8 ? ext.toUpperCase() : 'Download';
}

async function loadDownloadsPanel() {
  if (!studyIdno.value) {
    bulkMicrodata.value = [];
    return;
  }
  downloadsLoading.value = true;
  try {
    const base = (siteUrl.value || '').replace(/\/$/, '');
    const microUrl = `${base}/api/downloads/${encodeURIComponent(studyIdno.value)}/files?type=data`;
    const { data } = await axios.get(microUrl, { withCredentials: true });
    if (data?.status === 'success' && Array.isArray(data.files)) {
      bulkMicrodata.value = data.files;
    } else {
      bulkMicrodata.value = [];
    }
  } catch {
    bulkMicrodata.value = [];
  } finally {
    downloadsLoading.value = false;
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
    valueCountsError.value = e?.response?.data?.message || e?.message || 'Could not load value counts summary.';
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
    setMessage(`Value counts synced (${inserted} row(s)).`, 'success');
    await loadValueCountsSummary();
  } catch (e) {
    const msg = e?.message || e?.response?.data?.message || 'Could not sync value counts.';
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
    return;
  }
  pageLoading.value = true;
  fatalError.value = '';
  missingDsd.value = false;
  try {
    const [sch, count] = await Promise.all([fetchSchema(), fetchObservationCount()]);
    schema.value = sch;
    observationCount.value = Number(count) || 0;
    offset.value = 0;
    observationListTotal.value = Number(count) || 0;
    await Promise.all([reloadObservations(), loadValueCountsSummary()]);
    await loadDownloadsPanel();
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Could not load timeseries data.';
    if (isMissingDsdMessage(msg)) {
      missingDsd.value = true;
      fatalError.value = '';
      schema.value = null;
      observationRows.value = [];
      observationCount.value = 0;
      observationListTotal.value = 0;
      valueCountsSummary.value = { total_rows: 0, total_distinct_codes: 0, total_observations: 0, components: [] };
      valueCountsError.value = '';
      if (!dsdSelectItems.value.length) {
        loadDsdCatalogueList();
      }
      await loadDownloadsPanel();
    } else {
      fatalError.value = msg;
      schema.value = null;
      valueCountsSummary.value = { total_rows: 0, total_distinct_codes: 0, total_observations: 0, components: [] };
      valueCountsError.value = '';
      await loadDownloadsPanel();
    }
  } finally {
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
  max-width: 1200px;
}

.section-card {
  border-radius: 12px;
}

.section-stack + .section-stack {
  margin-top: 20px;
}

.value-counts-section {
  margin-top: 24px;
}

.panel-row {
  margin: -8px;
}

.panel-row > .v-col {
  padding: 8px;
}

.dsd-picker {
  margin-bottom: 8px;
}

.action-row {
  margin-top: 16px;
}

.downloads-expand-card :deep(.v-expansion-panel) {
  border-radius: 12px;
}

.downloads-panels :deep(.v-expansion-panel-title) {
  min-height: unset;
}

.downloads-panels :deep(.v-expansion-panel-text__wrapper) {
  padding-top: 0;
}

.ts-sync-alert {
  margin-bottom: 24px;
}

/* v-alert / density can reset utility classes; explicit spacing on our wrapper */
.ts-sync-alert__body {
  padding-bottom: 20px;
}

.ts-sync-alert__title {
  margin-bottom: 14px;
}

.ts-sync-alert__body > p:last-child {
  margin-bottom: 0;
}
</style>
