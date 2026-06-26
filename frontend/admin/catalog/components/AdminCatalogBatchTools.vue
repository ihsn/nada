<template>
  <div class="admin-catalog-batch pa-1">
    <v-progress-linear v-if="batchLoading" indeterminate color="primary" height="3" class="mb-4" />

    <v-alert v-if="loadError" type="error" density="compact" class="mb-4" rounded="0" border="start">
      {{ loadError }}
    </v-alert>

    <!-- Import options -->
    <v-card v-if="tool === 'batch-import'" class="admin-catalog-surface mb-4" rounded="0" elevation="1">
      <v-card-title class="text-subtitle-1 d-flex align-center gap-2">
        <v-icon icon="mdi-tune-variant" size="20" class="text-medium-emphasis" />
        {{ t('batch_import_options', 'Import options') }}
      </v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-4">
          {{ t('batch_import_blurb', 'Import DDI XML files from the server import folder into a collection.') }}
        </p>
        <div class="batch-import-fields mt-4">
          <div class="batch-field-group batch-field-collection batch-field-collection-group">
            <div class="batch-field-label">{{ t('collection', 'Collection') }}</div>
            <v-select
              v-model="importTargetRepo"
              :items="importCollections"
              item-title="title"
              item-value="repositoryid"
              density="comfortable"
              variant="outlined"
              hide-details
              :disabled="batchRunning"
            />
          </div>
          <div class="batch-field-group batch-field-overwrite batch-field-overwrite-inline">
            <div class="batch-field-label">{{ t('overwrite', 'Overwrite existing studies') }}</div>
            <v-switch
              v-model="importOverwrite"
              color="primary"
              density="compact"
              hide-details
              class="mt-0"
              :disabled="batchRunning"
            />
          </div>
        </div>
        <v-alert
          v-if="importFiles.length === 0 && !batchLoading"
          type="info"
          variant="tonal"
          density="comfortable"
          rounded="0"
          class="my-4"
        >
          {{
            t(
              'import_ddi_no_files_found_detailed',
              'No DDI files were found in the import folder. Place DDI XML in the server import directory (for example via FTP), then reload this page. To add a single study, use Add study from the catalog menu.'
            )
          }}
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Running / progress summary -->
    <v-card
      v-if="batchRunning || progressSummary"
      class="admin-catalog-surface mb-4 batch-progress-card"
      rounded="0"
      elevation="2"
      :color="batchRunning ? undefined : 'surface'"
    >
      <v-card-text>
        <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-3">
          <div class="d-flex align-center gap-2 min-w-0">
            <v-progress-circular
              v-if="batchRunning"
              indeterminate
              size="24"
              width="3"
              color="primary"
              class="flex-shrink-0"
            />
            <v-icon v-else icon="mdi-check-circle-outline" color="success" size="24" class="flex-shrink-0" />
            <div class="min-w-0">
              <div class="text-subtitle-2 font-weight-medium">
                {{ batchRunning ? t('batch_processing', 'Processing…') : t('batch_finished', 'Batch finished') }}
              </div>
              <div v-if="currentItemDisplay" class="text-body-2 text-medium-emphasis text-truncate">
                {{ currentItemDisplay }}
              </div>
              <div v-else-if="!batchRunning && progressSummary" class="text-body-2 text-medium-emphasis">
                {{ progressSummary }}
              </div>
            </div>
          </div>
          <v-btn
            v-if="batchRunning"
            color="error"
            variant="elevated"
            class="text-none flex-shrink-0"
            prepend-icon="mdi-stop-circle-outline"
            @click="cancelBatch"
          >
            {{ t('stop', 'Stop') }}
          </v-btn>
        </div>
        <v-progress-linear
          :model-value="progressPercent"
          color="primary"
          height="8"
          class="batch-progress-bar"
        />
        <div class="d-flex justify-space-between text-caption text-medium-emphasis mt-1">
          <span>{{ progressCountsLabel }}</span>
          <span v-if="batchRunning && runQueue.length">{{ Math.min(queueIndex, runQueue.length) }} / {{ runQueue.length }}</span>
        </div>
      </v-card-text>
    </v-card>

    <div v-if="rowsForTable.length" class="mb-5 d-flex flex-wrap align-center batch-actions-row pb-2">
      <v-btn
        color="primary"
        variant="elevated"
        size="small"
        class="text-none"
        prepend-icon="mdi-checkbox-multiple-marked-outline"
        :disabled="batchRunning"
        @click="toggleSelectAll"
      >
        {{ allSelected ? t('deselect_all', 'Deselect all') : t('select_all', 'Select all') }}
      </v-btn>
      <v-btn
        color="primary"
        variant="elevated"
        size="small"
        class="text-none"
        :prepend-icon="runButtonIcon"
        :disabled="!canRun || batchRunning"
        :loading="batchRunning"
        @click="runBatch"
      >
        {{ runButtonLabel }}
      </v-btn>
    </div>

    <v-table v-if="rowsForTable.length" density="comfortable" class="elevation-1 batch-study-table mt-3">
        <thead>
          <tr>
            <th class="batch-col-check">
              <v-checkbox
                :model-value="allSelected"
                density="compact"
                hide-details
                :disabled="batchRunning"
                @update:model-value="toggleSelectAll"
              />
            </th>
            <th class="batch-col-status">{{ t('status', 'Status') }}</th>
            <th>{{ t('id', 'ID') }}</th>
            <th>{{ t('nation', 'Nation') }}</th>
            <th>{{ t('title', 'Title') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in rowsForTable"
            :key="`${tool}-${row.id}-${row.name}`"
            :class="rowRowClass(row)"
          >
            <td>
              <v-checkbox
                :model-value="selectedIds.has(row.id)"
                density="compact"
                hide-details
                :disabled="batchRunning"
                @update:model-value="(v) => setSelected(row.id, !!v)"
              />
            </td>
            <td class="batch-col-status">
              <v-chip
                v-if="rowPhase[row.id]"
                size="small"
                label
                :color="phaseChipColor(row.id)"
                :prepend-icon="phaseIcon(row.id)"
                variant="tonal"
                class="text-none"
              >
                {{ phaseLabel(row.id) }}
              </v-chip>
              <span v-else class="text-medium-emphasis text-caption">—</span>
            </td>
            <td class="tabular-nums">{{ row.numericId ?? '—' }}</td>
            <td>{{ row.nation }}</td>
            <td>
              <a
                v-if="row.numericId != null"
                :href="editUrl(row.numericId)"
                target="_blank"
                rel="noopener"
              >{{ row.title }}</a>
              <span v-else>{{ row.title }}</span>
            </td>
          </tr>
        </tbody>
      </v-table>

    <v-card v-if="logLines.length" class="admin-catalog-surface mt-4" rounded="0" elevation="1">
      <v-card-title class="text-subtitle-1 d-flex align-center gap-2">
        <v-icon icon="mdi-text-box-outline" size="20" class="text-medium-emphasis" />
        {{ t('batch_log', 'Log') }}
      </v-card-title>
      <v-card-text class="batch-log text-body-2 pt-0">
        <div v-for="(line, i) in logLines" :key="i" :class="line.ok ? 'text-success' : 'text-error'">
          {{ line.text }}
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch, onMounted } from 'vue';
import { useCatalogApi } from '../composables/useCatalogApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'AdminCatalogBatchTools' });

const props = defineProps({
  tool: { type: String, required: true },
  ownerRepo: { type: String, default: '' },
});

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const {
  fetchAllBatchStudies,
  fetchBatchImportFiles,
  postBatchImportFile,
  postRefreshStudy,
  fetchGenerateDdi,
} = useCatalogApi();

const studies = ref([]);
const importFiles = ref([]);
const importCollections = ref([]);
const importTargetRepo = ref('central');
const importOverwrite = ref(false);
const selectedIds = ref(new Set());
const batchLoading = ref(false);
const batchRunning = ref(false);
const loadError = ref('');
const logLines = ref([]);
const queueIndex = ref(0);
const runQueue = ref([]);

/** @type {import('vue').Ref<AbortController | null>} */
const batchAbort = ref(null);
const currentItem = ref(null);
const progressPercent = ref(0);
const rowPhase = reactive({});
const lastRunStats = ref({ ok: 0, err: 0, skipped: 0 });

function phaseKey(id) {
  return String(id);
}

const runButtonLabel = computed(() => {
  if (props.tool === 'batch-import') return t('btn_import', 'Import');
  if (props.tool === 'batch-refresh') return t('btn_refresh', 'Refresh');
  return t('Generate DDIs', 'Generate DDIs');
});

const runButtonIcon = computed(() => {
  if (props.tool === 'batch-import') return 'mdi-upload';
  if (props.tool === 'batch-refresh') return 'mdi-refresh';
  return 'mdi-auto-fix';
});

const rowsForTable = computed(() => {
  if (props.tool === 'batch-import') {
    return importFiles.value.map((f) => ({
      id: f.id,
      numericId: null,
      nation: '—',
      title: f.name,
    }));
  }
  return studies.value.map((s) => ({
    id: String(s.id),
    numericId: s.id,
    nation: s.nation ?? '',
    title: s.title ?? '',
  }));
});

const allSelected = computed(() => {
  const rows = rowsForTable.value;
  if (!rows.length) return false;
  return rows.every((r) => selectedIds.value.has(r.id));
});

const selectedCount = computed(() => selectedIds.value.size);

const canRun = computed(() => {
  if (props.tool === 'batch-import') {
    return selectedCount.value > 0 && !!importTargetRepo.value;
  }
  return selectedCount.value > 0;
});

const currentItemDisplay = computed(() => {
  const c = currentItem.value;
  if (!c) return '';
  const of = t('batch_item_of', '%s of %s', c.index, c.total);
  const suffix =
    c.numericId != null
      ? ` · ${t('study_id', 'Study ID')}: ${c.numericId}`
      : props.tool === 'batch-import'
        ? ` · ${t('file', 'File')}`
        : '';
  return `${of}: ${c.title}${suffix}`;
});

const progressCountsLabel = computed(() => {
  const s = lastRunStats.value;
  if (!batchRunning.value && (s.ok || s.err || s.skipped)) {
    const parts = [
      `${t('batch_done_ok', 'OK')}: ${s.ok}`,
      `${t('batch_done_errors', 'Errors')}: ${s.err}`,
    ];
    if (s.skipped) parts.push(`${t('batch_done_skipped', 'Skipped')}: ${s.skipped}`);
    return parts.join(' · ');
  }
  if (batchRunning.value && selectedCount.value) {
    return t('batch_selected_count', '%s selected', selectedCount.value);
  }
  return '';
});

const progressSummary = computed(() => {
  if (batchRunning.value) return '';
  const s = lastRunStats.value;
  if (!s.ok && !s.err && !s.skipped) return '';
  return progressCountsLabel.value;
});

function editUrl(sid) {
  const base = (siteUrl.value || '').replace(/\/$/, '');
  return `${base}/admin/catalog/edit/${sid}`;
}

function setSelected(key, checked) {
  const next = new Set(selectedIds.value);
  if (checked) {
    next.add(key);
  } else {
    next.delete(key);
  }
  selectedIds.value = next;
}

function toggleSelectAll() {
  const rows = rowsForTable.value;
  if (allSelected.value) {
    selectedIds.value = new Set();
  } else {
    selectedIds.value = new Set(rows.map((r) => r.id));
  }
}

function clearPhases() {
  for (const k of Object.keys(rowPhase)) {
    delete rowPhase[k];
  }
}

function setPhase(id, phase) {
  rowPhase[phaseKey(id)] = phase;
}

function rowRowClass(row) {
  const p = rowPhase[phaseKey(row.id)];
  return {
    'batch-row--running': p === 'running',
    'batch-row--ok': p === 'ok',
    'batch-row--err': p === 'err',
    'batch-row--skipped': p === 'skipped',
  };
}

function phaseLabel(id) {
  const p = rowPhase[phaseKey(id)];
  if (p === 'running') return t('batch_phase_running', 'Working…');
  if (p === 'ok') return t('batch_phase_ok', 'Done');
  if (p === 'err') return t('batch_phase_err', 'Failed');
  if (p === 'skipped') return t('batch_phase_skipped', 'Skipped');
  return '';
}

function phaseChipColor(id) {
  const p = rowPhase[phaseKey(id)];
  if (p === 'running') return 'primary';
  if (p === 'ok') return 'success';
  if (p === 'err') return 'error';
  if (p === 'skipped') return 'default';
  return 'default';
}

function phaseIcon(id) {
  const p = rowPhase[phaseKey(id)];
  if (p === 'running') return 'mdi-progress-clock';
  if (p === 'ok') return 'mdi-check';
  if (p === 'err') return 'mdi-alert-circle-outline';
  if (p === 'skipped') return 'mdi-skip-next-outline';
  return '';
}

async function loadData() {
  loadError.value = '';
  batchLoading.value = true;
  studies.value = [];
  importFiles.value = [];
  importCollections.value = [];
  selectedIds.value = new Set();
  logLines.value = [];
  clearPhases();
  lastRunStats.value = { ok: 0, err: 0, skipped: 0 };
  progressPercent.value = 0;
  currentItem.value = null;

  const owner = props.ownerRepo && String(props.ownerRepo).trim() !== '' ? props.ownerRepo : '';

  try {
    if (props.tool === 'batch-import') {
      const rid = owner || 'central';
      const data = await fetchBatchImportFiles(rid);
      importFiles.value = data.files || [];
      importCollections.value = data.collections || [];
      if (owner && importCollections.value.some((c) => c.repositoryid === owner)) {
        importTargetRepo.value = owner;
      } else if (
        importCollections.value.length &&
        !importCollections.value.some((c) => c.repositoryid === importTargetRepo.value)
      ) {
        importTargetRepo.value = importCollections.value[0].repositoryid;
      }
    } else {
      studies.value = await fetchAllBatchStudies(owner);
    }
  } catch (e) {
    console.error(e);
    loadError.value = e?.message || String(e);
  } finally {
    batchLoading.value = false;
  }
}

watch(
  () => [props.tool, props.ownerRepo],
  () => {
    loadData();
  }
);

onMounted(() => {
  loadData();
});

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

function isAbortError(e) {
  return e?.code === 'ERR_CANCELED' || e?.name === 'CanceledError' || e?.name === 'AbortError';
}

function cancelBatch() {
  batchAbort.value?.abort();
}

function markRemainingSkipped(rows, startIndex) {
  for (let j = startIndex; j < rows.length; j++) {
    const id = rows[j].id;
    const k = phaseKey(id);
    if (rowPhase[k] === 'pending' || rowPhase[k] === 'running') {
      setPhase(id, 'skipped');
      lastRunStats.value = { ...lastRunStats.value, skipped: lastRunStats.value.skipped + 1 };
    }
  }
}

async function runBatch() {
  const rows = rowsForTable.value.filter((r) => selectedIds.value.has(r.id));
  if (!rows.length) return;

  if (props.tool === 'batch-import' && !importTargetRepo.value) return;

  const ac = new AbortController();
  batchAbort.value = ac;
  batchRunning.value = true;
  logLines.value = [];
  runQueue.value = rows;
  queueIndex.value = 0;
  progressPercent.value = 0;
  currentItem.value = null;
  clearPhases();
  for (const r of rows) setPhase(r.id, 'pending');
  lastRunStats.value = { ok: 0, err: 0, skipped: 0 };

  const n = rows.length;
  const req = () => ({ signal: ac.signal });

  try {
    for (let i = 0; i < rows.length; i++) {
      if (ac.signal.aborted) {
        markRemainingSkipped(rows, i);
        logLines.value.push({
          ok: false,
          text: t('batch_cancelled_notice', 'Batch stopped. Remaining items were not processed.'),
        });
        break;
      }

      const row = rows[i];
      const idx = i + 1;
      queueIndex.value = idx;
      setPhase(row.id, 'running');
      currentItem.value = {
        id: row.id,
        title: row.title,
        numericId: row.numericId,
        index: idx,
        total: n,
      };
      progressPercent.value = n ? Math.round(((idx - 1) / n) * 100) : 0;

      try {
        if (props.tool === 'batch-import') {
          const data = await postBatchImportFile(row.id, importTargetRepo.value, importOverwrite.value, req());
          if (data.success) {
            logLines.value.push({ ok: true, text: `#${idx} ${row.title} — ${stripHtml(data.success)}` });
            setPhase(row.id, 'ok');
            lastRunStats.value = { ...lastRunStats.value, ok: lastRunStats.value.ok + 1 };
          } else {
            logLines.value.push({ ok: false, text: `#${idx} ${row.title} — ${data.error || 'Error'}` });
            setPhase(row.id, 'err');
            lastRunStats.value = { ...lastRunStats.value, err: lastRunStats.value.err + 1 };
          }
        } else if (props.tool === 'batch-refresh') {
          const data = await postRefreshStudy(row.numericId, req());
          if (data.success) {
            logLines.value.push({ ok: true, text: `#${idx} ${row.title} — ${data.success}` });
            setPhase(row.id, 'ok');
            lastRunStats.value = { ...lastRunStats.value, ok: lastRunStats.value.ok + 1 };
          } else {
            logLines.value.push({ ok: false, text: `#${idx} ${row.title} — ${data.error || 'Error'}` });
            setPhase(row.id, 'err');
            lastRunStats.value = { ...lastRunStats.value, err: lastRunStats.value.err + 1 };
          }
        } else if (props.tool === 'batch-generate') {
          const data = await fetchGenerateDdi(row.numericId, req());
          if (data.status === 'success') {
            logLines.value.push({ ok: true, text: `#${idx} ${row.title} — success` });
            setPhase(row.id, 'ok');
            lastRunStats.value = { ...lastRunStats.value, ok: lastRunStats.value.ok + 1 };
          } else {
            logLines.value.push({
              ok: false,
              text: `#${idx} ${row.title} — ${data.message || data.status || 'failed'}`,
            });
            setPhase(row.id, 'err');
            lastRunStats.value = { ...lastRunStats.value, err: lastRunStats.value.err + 1 };
          }
        }
      } catch (e) {
        if (isAbortError(e)) {
          setPhase(row.id, 'skipped');
          lastRunStats.value = { ...lastRunStats.value, skipped: lastRunStats.value.skipped + 1 };
          markRemainingSkipped(rows, i + 1);
          logLines.value.push({
            ok: false,
            text: t('batch_cancelled_notice', 'Batch stopped. Remaining items were not processed.'),
          });
          break;
        }
        logLines.value.push({ ok: false, text: `#${idx} ${row.title} — ${e?.message || String(e)}` });
        setPhase(row.id, 'err');
        lastRunStats.value = { ...lastRunStats.value, err: lastRunStats.value.err + 1 };
      }

      progressPercent.value = n ? Math.round((idx / n) * 100) : 100;
      await sleep(40);
    }
  } finally {
    batchRunning.value = false;
    batchAbort.value = null;
    currentItem.value = null;
    runQueue.value = [];
    queueIndex.value = 0;
    if (!ac.signal.aborted && n) {
      progressPercent.value = 100;
    }
  }
}

function stripHtml(s) {
  if (!s || typeof s !== 'string') return s;
  return s.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}
</script>

<style scoped>
.batch-import-fields {
  display: grid;
  grid-template-columns: minmax(260px, 420px);
  row-gap: 0.875rem;
}

.batch-field-collection {
  min-width: 0;
  max-width: 420px;
}

.batch-field-collection-group {
  margin-top: 0.5rem;
}

.batch-field-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.batch-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.7);
  line-height: 1.35;
}

.batch-field-overwrite :deep(.v-selection-control) {
  min-height: 32px;
}

.batch-field-overwrite :deep(.v-selection-control__wrapper) {
  margin-inline-start: -2px;
}

.batch-field-overwrite-inline {
  flex-direction: row;
  align-items: center;
  gap: 0.75rem;
}

.batch-field-overwrite-inline .batch-field-label {
  margin-bottom: 0;
}

.batch-progress-card {
  border: 1px solid rgba(25, 118, 210, 0.2);
}

.batch-progress-bar {
  opacity: 1;
}

.batch-actions-row {
  gap: 0.5rem;
}

.batch-col-check {
  width: 52px;
}

.batch-col-status {
  width: 112px;
}

.batch-study-table tbody tr.batch-row--running {
  background-color: rgba(25, 118, 210, 0.09) !important;
  box-shadow: inset 3px 0 0 rgb(var(--v-theme-primary));
}

.batch-study-table tbody tr {
  background-color: #fff;
}

.batch-study-table tbody tr.batch-row--ok {
  background-color: rgba(46, 125, 50, 0.06) !important;
}

.batch-study-table tbody tr.batch-row--err {
  background-color: rgba(211, 47, 47, 0.07) !important;
}

.batch-study-table tbody tr.batch-row--skipped {
  opacity: 0.72;
  background-color: rgba(0, 0, 0, 0.03) !important;
}

.batch-study-table :deep(th),
.batch-study-table :deep(td) {
  vertical-align: middle;
}

.batch-study-table :deep(.v-table__wrapper) {
  border: 1px solid rgba(0, 0, 0, 0.14);
}

.batch-study-table :deep(thead th) {
  background-color: rgba(0, 0, 0, 0.12);
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}

.batch-study-table tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.batch-log {
  max-height: 360px;
  overflow-y: auto;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
  font-size: 0.8125rem;
  white-space: pre-wrap;
  line-height: 1.45;
}
</style>
