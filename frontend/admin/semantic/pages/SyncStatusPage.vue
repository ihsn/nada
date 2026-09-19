<template>
  <div class="admin-semantic-stack">
    <Teleport to="#semantic-page-actions">
      <v-btn
        variant="outlined"
        prepend-icon="mdi-refresh"
        :loading="loadingStatus || loadingQueue"
        @click="loadAll"
      >
        Refresh
      </v-btn>
      <v-btn
        v-if="failedCount > 0"
        variant="outlined"
        prepend-icon="mdi-replay"
        :loading="requeueing"
        :disabled="!canEdit"
        @click="requeue"
      >
        Requeue failed
      </v-btn>
      <v-btn
        color="primary"
        prepend-icon="mdi-play"
        :loading="reconciling"
        :disabled="!canEdit || !trackingOn || pendingCount === 0"
        @click="processPending"
      >
        Process pending
      </v-btn>
    </Teleport>

    <v-alert v-if="error || queueError" type="error" variant="tonal" closable @click:close="clearErrors">
      {{ errorMessage(error || queueError) }}
    </v-alert>

    <v-alert v-if="status && !trackingOn" type="warning" variant="tonal">
      NADA does not report this deployment as its search provider yet — the change queue will stay
      empty until NADA's search provider / tracking configuration points here.
    </v-alert>

    <v-alert v-if="lastPoll" type="success" variant="tonal" closable @click:close="lastPoll = null">
      Submitted {{ lastPoll.polled ?? lastPoll.count ?? 0 }} pending item(s) as jobs. They stay in this queue until the jobs
      finish —
      <router-link to="/jobs" class="text-decoration-none">watch progress on Jobs</router-link>.
    </v-alert>
    <v-alert v-if="lastRequeue != null" type="success" variant="tonal" closable @click:close="lastRequeue = null">
      Requeued {{ lastRequeue }} failed item(s) as pending.
    </v-alert>

    <v-card elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
        Queue
        <v-spacer />
        <span v-if="status" class="text-caption text-medium-emphasis font-weight-regular">
          {{ trackingOn ? 'Change tracking on' : 'Change tracking off' }}
          <template v-if="status.search_provider"> · {{ status.search_provider }}</template>
        </span>
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-progress-linear v-if="loadingStatus && !status" indeterminate color="primary" class="mb-4" />
        <div v-else class="d-flex flex-wrap" style="gap: 32px">
          <div>
            <div class="text-caption text-medium-emphasis">Pending</div>
            <div class="text-h6 font-weight-bold mt-1">{{ formatCount(pendingCount) }}</div>
          </div>
          <div>
            <div class="text-caption text-medium-emphasis">Failed</div>
            <div
              class="text-h6 font-weight-bold mt-1"
              :class="failedCount > 0 ? 'text-error' : ''"
            >
              {{ formatCount(failedCount) }}
            </div>
          </div>
        </div>
        <p class="text-caption text-medium-emphasis mb-0 mt-4">
          Live catalog edits waiting to be indexed — each pending row becomes a job.
          Coverage drift the queue might have missed is on
          <router-link to="/diff" class="text-decoration-none">Index</router-link>.
        </p>
      </v-card-text>
    </v-card>

    <v-card elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
        <v-btn-toggle v-model="queueView" density="compact" divided mandatory>
          <v-btn value="pending" size="small">
            Pending
            <v-chip size="x-small" class="ml-2">{{ pendingCount }}</v-chip>
          </v-btn>
          <v-btn value="failed" size="small">
            Failed
            <v-chip size="x-small" class="ml-2" :color="failedCount > 0 ? 'error' : undefined">
              {{ failedCount }}
            </v-chip>
          </v-btn>
        </v-btn-toggle>
        <v-spacer />
        <span class="text-caption text-medium-emphasis font-weight-regular">
          {{
            queueView === 'pending'
              ? 'Waiting to be processed'
              : 'Failed the last attempt — requeue to try again'
          }}
        </span>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-3">
        <v-data-table
          :headers="queueHeaders"
          :items="visibleItems"
          :loading="loadingQueue"
          item-value="id"
          density="comfortable"
        >
          <template #item.object_key="{ item }">
            <code>{{ item.object_key || item.object_id }}</code>
          </template>
          <template #item.object_type="{ item }">{{ humanizeKey(item.object_type) }}</template>
          <template #item.change_class="{ item }">{{ changeClassLabel(item.change_class) }}</template>
          <template #item.last_error="{ item }">
            <span
              v-if="item.last_error"
              class="text-caption text-warning d-inline-block text-truncate"
              style="max-width: 280px"
              :title="item.last_error"
            >{{ item.last_error }}</span>
            <span v-else class="text-medium-emphasis">—</span>
          </template>
          <template #item.changed="{ item }">{{ formatQueueTime(item.changed) }}</template>
          <template #no-data>
            <div class="text-medium-emphasis py-6">
              {{
                queueView === 'pending'
                  ? 'Nothing waiting. Catalog edits will show up here.'
                  : 'No failed items.'
              }}
            </div>
          </template>
        </v-data-table>
        <p
          v-if="visibleTotal > visibleItems.length"
          class="text-caption text-medium-emphasis mb-0 mt-2"
        >
          Showing {{ visibleItems.length }} of {{ formatCount(visibleTotal) }}.
        </p>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { changeClassLabel, formatCount, humanizeKey } from '../typeLabels.js';

defineOptions({ name: 'SemanticSyncStatusPage' });

const { canEdit } = useAppConfig();
const {
  error,
  errorMessage,
  getSyncStatus,
  reconcileNow,
  requeueFailed,
} = useSemanticApi();
const { error: queueError, listChangeQueue } = useSemanticApi();

const status = ref(null);
const pendingItems = ref([]);
const failedItems = ref([]);
const pendingTotal = ref(0);
const failedTotal = ref(0);
const loadingStatus = ref(false);
const loadingQueue = ref(false);
const reconciling = ref(false);
const requeueing = ref(false);
const lastPoll = ref(null);
const lastRequeue = ref(null);
const queueView = ref('pending');

const queueHeaders = [
  { title: 'idno', key: 'object_key' },
  { title: 'type', key: 'object_type' },
  { title: 'change', key: 'change_class' },
  { title: 'attempts', key: 'attempts' },
  { title: 'last error', key: 'last_error', sortable: false },
  { title: 'changed', key: 'changed' },
];

const trackingOn = computed(() => !!status.value?.tracking_enabled);
const pendingCount = computed(() => Number(status.value?.queue?.pending ?? pendingTotal.value) || 0);
const failedCount = computed(() => Number(status.value?.queue?.failed ?? failedTotal.value) || 0);
const visibleItems = computed(() => (queueView.value === 'failed' ? failedItems.value : pendingItems.value));
const visibleTotal = computed(() => (queueView.value === 'failed' ? failedTotal.value : pendingTotal.value));

function clearErrors() {
  error.value = null;
  queueError.value = null;
}

function formatQueueTime(ts) {
  if (!ts) return '—';
  const ms = Number(ts) > 1e12 ? Number(ts) : Number(ts) * 1000;
  if (!ms) return '—';
  try {
    return new Date(ms).toLocaleString();
  } catch {
    return String(ts);
  }
}

async function loadStatus() {
  loadingStatus.value = true;
  try {
    status.value = await getSyncStatus();
  } catch {
    status.value = null;
  } finally {
    loadingStatus.value = false;
  }
}

async function loadQueue() {
  loadingQueue.value = true;
  try {
    const pending = await listChangeQueue({ status: 'pending', limit: 100 });
    pendingItems.value = pending?.items || [];
    pendingTotal.value = Number(pending?.total) || pendingItems.value.length;
    const failed = await listChangeQueue({ status: 'failed', limit: 100 });
    failedItems.value = failed?.items || [];
    failedTotal.value = Number(failed?.total) || failedItems.value.length;
  } catch {
    pendingItems.value = [];
    failedItems.value = [];
    pendingTotal.value = 0;
    failedTotal.value = 0;
  } finally {
    loadingQueue.value = false;
  }
}

async function loadAll() {
  await Promise.all([loadStatus(), loadQueue()]);
}

async function processPending() {
  reconciling.value = true;
  lastRequeue.value = null;
  try {
    lastPoll.value = await reconcileNow();
    await loadAll();
  } catch {
    lastPoll.value = null;
  } finally {
    reconciling.value = false;
  }
}

async function requeue() {
  const n = failedCount.value;
  if (!n) return;
  if (!window.confirm(`Requeue ${n} failed item(s) as pending?`)) return;
  requeueing.value = true;
  lastPoll.value = null;
  try {
    const data = await requeueFailed();
    lastRequeue.value = data?.reset ?? n;
    queueView.value = 'pending';
    await loadAll();
  } catch {
    lastRequeue.value = null;
  } finally {
    requeueing.value = false;
  }
}

onMounted(loadAll);
</script>
