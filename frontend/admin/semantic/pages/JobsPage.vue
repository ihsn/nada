<template>
  <div>
    <div class="semantic-page-toolbar">
      <v-select
        v-model="statusFilter"
        :items="statusOptions"
        variant="outlined"
        density="compact"
        hide-details
        style="max-width: 200px"
        label="Status"
        @update:model-value="load"
      />
      <v-switch v-model="autoRefresh" label="Auto-refresh (10s)" hide-details density="compact" />
      <v-spacer />
      <v-btn variant="outlined" prepend-icon="mdi-refresh" :loading="loading" @click="load">Refresh</v-btn>
    </div>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4" closable @click:close="error = null">
      {{ errorMessage(error) }}
    </v-alert>

    <v-card elevation="1" rounded="lg">
      <v-data-table :headers="headers" :items="jobs" :loading="loading" item-value="id" density="comfortable">
        <template #no-data>
          <div class="text-medium-emphasis py-6">No jobs yet. Start from Index.</div>
        </template>
        <template #item.status="{ item }">
          <v-chip size="small" :color="statusColor(item.status)">{{ item.status }}</v-chip>
        </template>
        <template #item.key="{ item }">
          {{ humanizeKey(item.key) }}
        </template>
          <template #item.created_at="{ item }">
            {{ formatTime(item.created_at) }}
          </template>
          <template #item.progress="{ item }">
            <div v-if="item.progress && item.progress.total" style="min-width: 160px">
              <v-progress-linear
                :model-value="item.progress.percent ?? 0"
                :color="item.progress.failed ? 'warning' : 'primary'"
                height="14"
                rounded
              >
                <template #default>
                  <span class="text-caption">{{ item.progress.processed }}/{{ item.progress.total }}</span>
                </template>
              </v-progress-linear>
              <div class="text-caption text-medium-emphasis mt-1">
                <span v-if="item.progress.failed">{{ item.progress.failed }} failed · </span>
                <code>{{ item.progress.current_idno }}</code>
              </div>
            </div>
            <span v-else>—</span>
          </template>
          <template #item.error="{ item }">
            <span v-if="item.error" class="text-error text-caption">{{ item.error }}</span>
          </template>
          <template #item.actions="{ item }">
            <v-btn size="small" variant="text" prepend-icon="mdi-information-outline" @click="openDetails(item.id)">
              Details
            </v-btn>
            <v-btn
              v-if="canEdit && !isTerminal(item.status)"
              size="small"
              color="error"
              variant="text"
              prepend-icon="mdi-cancel"
              @click="cancel(item.id)"
            >
              Cancel
            </v-btn>
          </template>
        </v-data-table>
    </v-card>

    <v-dialog v-model="detailsDialog" max-width="720">
      <v-card>
        <v-card-title class="d-flex align-center">
          Job details
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" size="small" @click="detailsDialog = false" />
        </v-card-title>
        <v-card-text>
          <v-progress-linear v-if="detailsLoading" indeterminate class="mb-4" />
          <v-alert v-if="detailsError" type="error" variant="tonal" class="mb-4">
            {{ detailsErrorMessage(detailsError) }}
          </v-alert>

          <template v-if="details">
            <div class="d-flex flex-wrap gap-4 mb-4">
              <div><span class="text-caption text-medium-emphasis">Kind</span><br />{{ details.kind }}</div>
              <div><span class="text-caption text-medium-emphasis">Status</span><br />
                <v-chip size="small" :color="statusColor(details.status)">{{ details.status }}</v-chip>
              </div>
              <div><span class="text-caption text-medium-emphasis">Key</span><br /><code>{{ details.key }}</code></div>
            </div>

            <div v-if="details.progress && details.progress.total" class="mb-4">
              <div class="text-subtitle-2 mb-1">Progress</div>
              <v-progress-linear
                :model-value="details.progress.percent ?? 0"
                :color="details.progress.failed ? 'warning' : 'primary'"
                height="18"
                rounded
              >
                <template #default>
                  <span class="text-caption">{{ details.progress.percent }}%</span>
                </template>
              </v-progress-linear>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ details.progress.processed }} / {{ details.progress.total }} processed
                <span v-if="details.progress.failed">· {{ details.progress.failed }} failed</span>
                <span v-if="details.progress.current_idno"> · last: <code>{{ details.progress.current_idno }}</code></span>
              </div>
            </div>

            <div v-if="details.error" class="mb-4">
              <div class="text-subtitle-2 mb-1">Job error</div>
              <div class="text-error text-body-2">{{ details.error }}</div>
            </div>

            <template v-if="details.result">
              <div class="mb-4">
                <div class="text-subtitle-2 mb-1">Result</div>
                <v-table density="compact">
                  <tbody>
                    <tr v-for="key in resultSummaryKeys" :key="key">
                      <td class="text-medium-emphasis" style="width: 160px">{{ key }}</td>
                      <td>{{ formatValue(details.result[key]) }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </div>

              <div v-if="details.result.load_errors?.length" class="mb-4">
                <div class="text-subtitle-2 mb-1 text-warning">
                  Load errors ({{ details.result.load_errors.length }})
                </div>
                <div class="text-caption text-medium-emphasis mb-2">
                  Metadata failed to fetch/parse for these idnos before a document was even built.
                </div>
                <v-table density="compact">
                  <thead>
                    <tr><th>idno</th><th>type</th><th>error</th></tr>
                  </thead>
                  <tbody>
                    <tr v-for="(e, i) in details.result.load_errors" :key="i">
                      <td><code>{{ e.idno }}</code></td>
                      <td>{{ e.metadata_type }}</td>
                      <td class="text-caption">{{ e.error }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </div>

              <div v-if="details.result.errors?.length" class="mb-4">
                <div class="text-subtitle-2 mb-1 text-error">
                  Write errors ({{ details.result.errors.length }})
                </div>
                <div class="text-caption text-medium-emphasis mb-2">
                  Failed writing to the search backend (distinct from load errors above).
                </div>
                <v-table density="compact">
                  <tbody>
                    <tr v-for="(e, i) in details.result.errors" :key="i">
                      <td><code>{{ e.id || '—' }}</code></td>
                      <td class="text-caption">{{ e.error }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </div>

              <div v-if="details.result.empty_docs?.length" class="mb-4">
                <div class="text-subtitle-2 mb-1">
                  No content ({{ details.result.empty_docs.length }})
                </div>
                <div class="text-caption text-medium-emphasis mb-2">
                  Loaded without error but produced nothing to index — a data characteristic of
                  the catalog record, not a failure. "indexed" above counts documents, not idnos,
                  so <code>rows − load errors − no-content</code> is the idno count that actually
                  produced the indexed documents (one idno can yield more than one document).
                </div>
                <v-table density="compact">
                  <thead>
                    <tr><th>idno</th><th>type</th><th>reason</th></tr>
                  </thead>
                  <tbody>
                    <tr v-for="(e, i) in details.result.empty_docs" :key="i">
                      <td><code>{{ e.idno }}</code></td>
                      <td>{{ e.metadata_type }}</td>
                      <td class="text-caption">{{ e.reason }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </div>

              <div v-if="details.result.quality && Object.keys(details.result.quality.issues || {}).length" class="mb-4">
                <div class="text-subtitle-2 mb-1">
                  Quality issues ({{ details.result.quality.checked }} documents checked)
                </div>
                <v-table density="compact">
                  <thead>
                    <tr><th>issue</th><th>count</th><th>sample idnos</th></tr>
                  </thead>
                  <tbody>
                    <tr v-for="(v, k) in details.result.quality.issues" :key="k">
                      <td>{{ k }}</td>
                      <td>{{ v.count }}</td>
                      <td class="text-caption">{{ (v.sample_idnos || []).join(', ') }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </div>
            </template>
          </template>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { humanizeKey } from '../typeLabels.js';

defineOptions({ name: 'SemanticJobsPage' });

const { canEdit } = useAppConfig();
const { loading, error, errorMessage, listJobs, cancelJob } = useSemanticApi();
const { loading: detailsLoading, error: detailsError, errorMessage: detailsErrorMessage, getJob } = useSemanticApi();

const headers = [
  { title: 'Kind', key: 'kind' },
  { title: 'Job', key: 'key' },
  { title: 'Status', key: 'status' },
  { title: 'Created', key: 'created_at' },
  { title: 'Progress', key: 'progress', sortable: false },
  { title: 'Error', key: 'error' },
  { title: '', key: 'actions', sortable: false },
];

const statusOptions = [
  { title: 'All', value: '' },
  { title: 'Pending', value: 'pending' },
  { title: 'Running', value: 'running' },
  { title: 'Succeeded', value: 'succeeded' },
  { title: 'Failed', value: 'failed' },
  { title: 'Cancelled', value: 'cancelled' },
];

// A fixed key order (rather than Object.keys, which would vary by job kind)
// so the result table reads the same way for every job. 'deleted'/'failed'/
// 'skipped'/'missing_total'/'stale_total' are specific to reconcile_diff_once
// (search_index_reconcile_diff jobs) — the rest are from the indexing jobs.
const resultSummaryKeys = [
  'indexed',
  'deleted',
  'failed',
  'skipped',
  'missing_total',
  'stale_total',
  'requested',
  'rows',
  'resumed_skipped',
  'cancelled',
  'catalog_type',
  'metadata_type',
  'index',
];

const statusFilter = ref('');
const autoRefresh = ref(false);
const jobs = ref([]);
let timer = null;

const detailsDialog = ref(false);
const details = ref(null);

function isTerminal(status) {
  return ['succeeded', 'failed', 'cancelled'].includes(status);
}

function statusColor(status) {
  return { pending: 'grey', running: 'primary', succeeded: 'success', failed: 'error', cancelled: 'warning' }[status] || 'grey';
}

function formatTime(iso) {
  if (!iso) return '—';
  try { return new Date(iso).toLocaleString(); } catch { return iso; }
}

function formatValue(v) {
  if (v === null || v === undefined) return '—';
  if (typeof v === 'boolean') return v ? 'yes' : 'no';
  return v;
}

async function load() {
  try {
    const params = { limit: 100 };
    if (statusFilter.value) params.status = statusFilter.value;
    const data = await listJobs(params);
    jobs.value = data?.jobs || [];
  } catch {
    // error ref already set
  }
}

async function cancel(id) {
  try {
    await cancelJob(id);
    await load();
  } catch {
    // error ref already set
  }
}

async function openDetails(id) {
  detailsDialog.value = true;
  details.value = null;
  try {
    details.value = await getJob(id);
  } catch {
    // detailsError ref already set
  }
}

function scheduleAutoRefresh() {
  clearInterval(timer);
  if (autoRefresh.value) {
    timer = setInterval(load, 10000);
  }
}

onMounted(async () => {
  await load();
  if (jobs.value.some((j) => !isTerminal(j.status))) {
    autoRefresh.value = true;
  }
});
onBeforeUnmount(() => clearInterval(timer));

watch(autoRefresh, scheduleAutoRefresh);
</script>
