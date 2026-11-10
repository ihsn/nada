<template>
  <v-card class="cleanup-panel" rounded="lg" elevation="1">
    <div class="cleanup-panel__header">
      <div>
        <div class="cleanup-panel__title">{{ title }}</div>
        <div class="cleanup-panel__subtitle">
          Table <code>{{ table }}</code> · archived to <code>{{ filePattern }}</code>
        </div>
      </div>
      <v-btn
        variant="text"
        size="small"
        prepend-icon="mdi-refresh"
        :loading="loadingStats || loadingFiles"
        :disabled="running"
        @click="refresh"
      >
        Refresh
      </v-btn>
    </div>

    <v-divider />

    <div class="cleanup-panel__body">
      <v-alert v-if="loadError" type="error" variant="tonal" density="compact" class="mb-5">
        {{ loadError }}
      </v-alert>

      <!-- Table statistics -->
      <section class="cleanup-panel__section">
        <v-row v-if="stats" dense>
          <v-col v-for="tile in statTiles" :key="tile.label" cols="12" sm="4">
            <v-sheet rounded="lg" class="cleanup-panel__tile">
              <div class="cleanup-panel__tile-label">{{ tile.label }}</div>
              <div class="cleanup-panel__tile-value" :class="{ 'cleanup-panel__tile-value--sm': tile.small }">
                {{ tile.value }}
              </div>
            </v-sheet>
          </v-col>
        </v-row>
        <v-row v-else-if="loadingStats" dense>
          <v-col v-for="n in 3" :key="n" cols="12" sm="4">
            <v-skeleton-loader type="list-item-two-line" class="cleanup-panel__tile" />
          </v-col>
        </v-row>
      </section>

      <!-- Retention & archive location -->
      <section v-if="stats" class="cleanup-panel__section">
        <div class="cleanup-panel__section-title">Retention &amp; archive</div>

        <v-alert v-if="stats.directory_in_webroot" type="error" variant="tonal" density="compact" class="mb-0">
          <strong>The archive directory is inside the web root</strong> and is publicly accessible, so cleanup is
          disabled. Set <code>db_logs_csv_dir</code> in <code>application/config/db_logs.php</code> to a directory
          outside the web root.
        </v-alert>

        <template v-else>
          <dl class="cleanup-panel__facts">
            <dt>Retention</dt>
            <dd>{{ stats.retention_days }} days</dd>
            <dt>Cutoff</dt>
            <dd>Entries before {{ stats.cutoff_date }}</dd>
            <dt>Archive path</dt>
            <dd class="d-flex flex-wrap align-center ga-2">
              <code class="cleanup-panel__path">{{ stats.csv_dir }}</code>
              <v-chip v-if="stats.csv_dir_writable" size="x-small" label color="success" variant="tonal">Writable</v-chip>
              <v-chip v-else-if="stats.csv_dir_exists" size="x-small" label color="error" variant="tonal">Not writable</v-chip>
              <v-chip v-else size="x-small" label color="warning" variant="tonal">Created on first cleanup</v-chip>
            </dd>
          </dl>
          <p class="cleanup-panel__help mb-0">
            Old entries are appended to monthly CSV files, then permanently deleted from the database.
          </p>
          <v-alert v-if="dirNotWritable" type="warning" variant="tonal" density="compact" class="mt-4 mb-0">
            The web server cannot write to the archive directory. Cleanup is disabled until this is fixed.
          </v-alert>
        </template>
      </section>

      <!-- Run cleanup -->
      <section v-if="!canCleanup" class="cleanup-panel__section">
        <div class="cleanup-panel__section-title">Run cleanup</div>
        <p class="cleanup-panel__help mb-0">
          Running a cleanup requires the <strong>cleanup</strong> privilege of the <strong>Site and API logs</strong>
          permission.
        </p>
      </section>
      <section v-else class="cleanup-panel__section">
        <div class="cleanup-panel__section-title">Run cleanup</div>
        <div class="d-flex flex-wrap align-end ga-3">
          <div class="cleanup-panel__time-field">
            <div class="text-caption text-medium-emphasis mb-1">Seconds per request (1–30)</div>
            <v-text-field
              v-model.number="timeLimit"
              type="number"
              min="1"
              max="30"
              density="compact"
              variant="outlined"
              hide-details
              :disabled="running"
            />
          </div>
          <v-btn
            color="primary"
            variant="flat"
            height="40"
            prepend-icon="mdi-archive-arrow-down-outline"
            :disabled="!canStart"
            :loading="running && !stopping"
            @click="start"
          >
            {{ progress && progress.status === 'stopped' ? 'Resume cleanup' : 'Start cleanup' }}
          </v-btn>
          <v-btn
            v-if="running"
            color="error"
            variant="outlined"
            height="40"
            prepend-icon="mdi-stop"
            :loading="stopping"
            @click="stop"
          >
            Stop
          </v-btn>
        </div>
        <p class="cleanup-panel__help mt-2 mb-0">
          Each request processes 500-row batches until the time limit, then the next request starts automatically.
        </p>

        <v-progress-linear v-if="running" indeterminate color="primary" rounded class="mt-4" />

        <v-alert
          v-if="progress"
          :type="progressAlertType"
          variant="tonal"
          density="compact"
          class="mt-4 mb-0"
        >
          <div class="font-weight-medium">
            <template v-if="progress.status === 'completed'">Cleanup completed. No more entries older than the cutoff.</template>
            <template v-else-if="progress.status === 'stopped'">Cleanup stopped. Resume to continue with the remaining entries.</template>
            <template v-else-if="progress.status === 'failed'">Cleanup failed: {{ progress.message }}</template>
            <template v-else>Cleanup in progress…</template>
          </div>
          <div class="text-caption mt-1">
            {{ formatNumber(progress.rows_deleted) }} of {{ formatNumber(progress.rows_processed) }} rows archived and
            deleted in {{ progress.requests }} request(s).
          </div>
        </v-alert>
      </section>

      <!-- Exported files -->
      <section class="cleanup-panel__section">
        <div class="cleanup-panel__section-title">
          Exported CSV files
          <span v-if="files.length" class="text-medium-emphasis font-weight-regular">({{ files.length }})</span>
        </div>
        <v-table density="comfortable" class="cleanup-panel__files">
          <thead>
            <tr>
              <th>File name</th>
              <th>Last modified</th>
              <th class="text-end">Size</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="file in files" :key="file.filename">
              <td>
                <v-icon icon="mdi-file-delimited-outline" size="18" color="teal" class="mr-2" />
                <span class="text-mono">{{ file.filename }}</span>
              </td>
              <td class="text-no-wrap">{{ file.date }}</td>
              <td class="text-end text-no-wrap">{{ file.size_formatted }}</td>
            </tr>
            <tr v-if="!files.length">
              <td colspan="3" class="text-center text-medium-emphasis py-6">
                {{ loadingFiles ? 'Loading…' : 'No exported files yet' }}
              </td>
            </tr>
          </tbody>
        </v-table>
      </section>
    </div>

    <v-dialog v-model="confirmOpen" max-width="460">
      <v-card>
        <v-card-title class="text-h6">Start cleanup?</v-card-title>
        <v-card-text>
          Entries older than {{ stats?.cutoff_date }} will be exported to CSV and permanently deleted from the
          {{ table }} table. This cannot be undone from the admin UI.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmOpen = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" @click="run">Start</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useLogsApi } from '../composables/useLogsApi';
import { formatNumber } from '../utils/format';

const props = defineProps({
  table: { type: String, required: true, validator: (v) => v === 'sitelogs' || v === 'api_logs' },
  title: { type: String, required: true },
});

const { config } = useAppConfig();
const { cleanupStats, cleanupFiles, cleanupChunk } = useLogsApi();

const canCleanup = computed(() => !!config.value?.canCleanup);

const stats = ref(null);
const files = ref([]);
const loadingStats = ref(false);
const loadingFiles = ref(false);
const loadError = ref('');
const timeLimit = ref(20);
const running = ref(false);
const stopping = ref(false);
const progress = ref(null);
const confirmOpen = ref(false);

const filePattern = computed(() => (props.table === 'api_logs' ? 'api_log-YYYY-MM.csv' : 'db-log-YYYY-MM.csv'));

const statTiles = computed(() => {
  const s = stats.value || {};
  return [
    { label: 'Total rows', value: formatNumber(s.total_rows) },
    { label: 'Table size', value: s.table_size_mb != null ? `${s.table_size_mb} MB` : 'N/A' },
    { label: 'Oldest entry', value: s.oldest_log_date || 'N/A', small: true },
  ];
});

const dirNotWritable = computed(() => !!stats.value?.csv_dir_exists && !stats.value?.csv_dir_writable);

const canStart = computed(
  () => !!stats.value && !running.value && !stats.value.directory_in_webroot && !dirNotWritable.value
);

const progressAlertType = computed(() => {
  switch (progress.value?.status) {
    case 'completed':
      return 'success';
    case 'stopped':
      return 'warning';
    case 'failed':
      return 'error';
    default:
      return 'info';
  }
});

async function loadStats() {
  loadingStats.value = true;
  try {
    stats.value = await cleanupStats(props.table);
  } catch (e) {
    loadError.value = e.message;
  } finally {
    loadingStats.value = false;
  }
}

async function loadFiles() {
  loadingFiles.value = true;
  try {
    files.value = await cleanupFiles(props.table);
  } catch (e) {
    loadError.value = e.message;
  } finally {
    loadingFiles.value = false;
  }
}

function refresh() {
  loadError.value = '';
  return Promise.all([loadStats(), loadFiles()]);
}

function start() {
  if (progress.value?.status === 'stopped') {
    run();
  } else {
    confirmOpen.value = true;
  }
}

async function run() {
  confirmOpen.value = false;
  const limit = Math.min(30, Math.max(1, Number(timeLimit.value) || 20));
  timeLimit.value = limit;
  running.value = true;
  stopping.value = false;
  const carry = progress.value?.status === 'stopped' ? progress.value : null;
  progress.value = {
    status: null,
    rows_processed: carry?.rows_processed || 0,
    rows_deleted: carry?.rows_deleted || 0,
    requests: carry?.requests || 0,
    message: '',
  };

  try {
    while (!stopping.value) {
      const res = await cleanupChunk(props.table, limit);
      progress.value.rows_processed += res.rows_processed || 0;
      progress.value.rows_deleted += res.rows_deleted || 0;
      progress.value.requests += 1;
      if (!res.rows_processed) {
        progress.value.status = 'completed';
        break;
      }
    }
    if (!progress.value.status) progress.value.status = 'stopped';
  } catch (e) {
    progress.value.status = 'failed';
    progress.value.message = e.message;
  } finally {
    running.value = false;
    stopping.value = false;
    refresh();
  }
}

function stop() {
  stopping.value = true;
}

onMounted(refresh);
</script>

<style scoped>
.cleanup-panel {
  margin-bottom: 24px;
}

.cleanup-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 24px;
}

.cleanup-panel__title {
  font-size: 1.0625rem;
  font-weight: 600;
  line-height: 1.4;
  color: rgb(var(--v-theme-on-surface));
}

.cleanup-panel__subtitle {
  margin-top: 2px;
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.cleanup-panel__body {
  padding: 8px 24px 24px;
}

.cleanup-panel__section {
  padding-top: 20px;
}

.cleanup-panel__section + .cleanup-panel__section {
  margin-top: 20px;
  border-top: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.cleanup-panel__section-title {
  margin-bottom: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: 0.01em;
  color: rgba(var(--v-theme-on-surface), 0.8);
}

.cleanup-panel__tile {
  height: 100%;
  padding: 14px 16px;
  background-color: rgba(var(--v-theme-on-surface), 0.04);
}

.cleanup-panel__tile-label {
  font-size: 0.75rem;
  font-weight: 500;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.cleanup-panel__tile-value {
  margin-top: 4px;
  font-size: 1.375rem;
  font-weight: 600;
  line-height: 1.3;
  color: rgb(var(--v-theme-on-surface));
}

.cleanup-panel__tile-value--sm {
  font-size: 1rem;
  line-height: 1.9;
}

.cleanup-panel__facts {
  display: grid;
  grid-template-columns: max-content 1fr;
  column-gap: 24px;
  row-gap: 8px;
  margin: 0 0 12px;
  font-size: 0.875rem;
}

.cleanup-panel__facts dt {
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.cleanup-panel__facts dd {
  margin: 0;
  color: rgb(var(--v-theme-on-surface));
}

.cleanup-panel__path {
  padding: 2px 8px;
  border-radius: 4px;
  background-color: rgba(var(--v-theme-on-surface), 0.06);
  font-size: 0.8125rem;
  word-break: break-all;
}

.cleanup-panel__help {
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.cleanup-panel__time-field {
  width: 200px;
}

.cleanup-panel__files {
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  overflow: hidden;
}

.cleanup-panel__files :deep(thead th) {
  background-color: rgba(var(--v-theme-on-surface), 0.04);
  font-size: 0.75rem;
  font-weight: 600 !important;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.65) !important;
}

.text-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
}

@media (max-width: 600px) {
  .cleanup-panel__header,
  .cleanup-panel__body {
    padding-left: 16px;
    padding-right: 16px;
  }

  .cleanup-panel__facts {
    grid-template-columns: 1fr;
    row-gap: 2px;
  }

  .cleanup-panel__facts dd {
    margin-bottom: 8px;
  }
}
</style>
