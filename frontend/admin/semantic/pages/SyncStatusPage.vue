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
        Process now
      </v-btn>
    </Teleport>

    <v-alert v-if="error || queueError" type="error" variant="tonal" closable @click:close="clearErrors">
      {{ errorMessage(error || queueError) }}
    </v-alert>

    <v-alert v-if="status && status.index_engine !== 'nada_ai'" type="warning" variant="tonal">
      NADA does not track catalog changes for NADA-AI, so this queue stays empty. Set
      <strong>Index kept in sync</strong> to NADA-AI in Site configurations › Search.
    </v-alert>

    <v-alert v-if="lastPoll" type="success" variant="tonal" closable @click:close="lastPoll = null">
      NADA-AI is working through the queue; items leave it as they are done.
    </v-alert>
    <v-alert v-if="lastRequeue != null" type="success" variant="tonal" closable @click:close="lastRequeue = null">
      Requeued {{ lastRequeue }} failed item(s) as pending.
    </v-alert>

    <v-card elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
        Queue
        <v-spacer />
        <template v-if="status">
          <v-chip size="small" variant="tonal" :color="trackingOn ? 'success' : 'warning'">
            {{ trackingOn ? 'Change tracking on' : 'Change tracking off' }}
          </v-chip>
          <v-chip v-if="status.index_engine" size="small" variant="tonal">Kept in sync: {{ status.index_engine }}</v-chip>
          <v-chip v-if="status.search_engine" size="small" variant="tonal">Search: {{ status.search_engine }}</v-chip>
        </template>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-4">
        <v-progress-linear v-if="loadingStatus && !status" indeterminate color="primary" class="mb-4" />
        <div v-else class="semantic-queue-facts">
          <div>
            <div class="semantic-queue-label">Pending</div>
            <div class="text-h6 font-weight-bold">{{ formatCount(pendingCount) }}</div>
          </div>
          <div>
            <div class="semantic-queue-label">Failed</div>
            <div class="text-h6 font-weight-bold" :class="failedCount > 0 ? 'text-error' : ''">
              {{ formatCount(failedCount) }}
            </div>
          </div>
          <template v-if="tracked">
            <div>
              <div class="semantic-queue-label">Tracking since</div>
              <div class="text-body-2">{{ formatQueueTime(tracked.tracked_since) }}</div>
            </div>
            <div>
              <div class="semantic-queue-label">Last full sync</div>
              <div class="text-body-2">{{ tracked.last_sync_at ? formatQueueTime(tracked.last_sync_at) : 'Never' }}</div>
            </div>
            <div>
              <div class="semantic-queue-label">NADA-AI last read the queue</div>
              <div class="text-body-2">{{ tracked.last_poll_at ? formatQueueTime(tracked.last_poll_at) : 'Never' }}</div>
            </div>
          </template>
        </div>

        <div v-if="scheduler" class="semantic-queue-scheduler">
          <v-icon icon="mdi-robot-outline" size="18" color="primary" />
          <div>
            <div class="semantic-queue-label">NADA-AI</div>
            <div class="text-body-2">{{ schedulerLine }}</div>
          </div>
        </div>

        <div class="semantic-queue-items-bar">
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
          <span class="text-body-2 text-medium-emphasis">
            {{
              queueView === 'pending'
                ? 'Waiting to be processed'
                : 'Failed the last attempt — requeue to try again'
            }}
          </span>
        </div>
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
          <template #item.change_class="{ item }">
            {{ changeClassLabel(item.change_class) }}
            <v-chip v-if="item.priority === 1" size="x-small" variant="tonal" class="ml-1">sync</v-chip>
          </template>
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

        <p class="semantic-queue-note text-body-2 text-medium-emphasis">
          Catalog edits, and the work “Update” on
          <router-link :to="{ name: 'indexing' }" class="text-decoration-none">Indexing</router-link>
          queues, wait here. NADA-AI works through them one item at a time, catalog edits first.
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
const { getSchedulerStatus } = useSemanticApi();
const scheduler = ref(null);
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
const tracked = computed(() => status.value?.tracked || null);

/** What NADA-AI's scheduler is doing, in one line. */
const schedulerLine = computed(() => {
  const s = scheduler.value;
  if (!s) return '';
  const pass = s.pass || {};
  switch (s.state) {
    case 'processing':
      return `Processing${s.current?.object_key ? ` ${s.current.object_key} (${changeClassLabel(s.current.change_class)})` : ''}`
        + ` · ${formatCount(pass.processed || 0)} done in this pass${pass.failed ? `, ${pass.failed} failed` : ''}`;
    case 'idle':
      return `Up to date${s.next_poll_at ? ` · next check at ${formatQueueTime(s.next_poll_at)}` : ''}`;
    case 'paused':
      return `Paused: ${s.pause?.message || s.pause?.reason || ''}${s.pause?.resume_at ? ` · next try at ${formatQueueTime(s.pause.resume_at)}` : ''}`;
    default:
      return s.state;
  }
});
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
  try {
    scheduler.value = await getSchedulerStatus();
  } catch {
    scheduler.value = null; // NADA-AI not answering: the banner says so
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

<style scoped>
.semantic-queue-facts {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 20px 32px;
}

.semantic-queue-label {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  margin-bottom: 4px;
}

.semantic-queue-items-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 16px;
  margin: 24px 0 8px;
  padding-top: 20px;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.semantic-queue-note {
  margin: 24px 0 0;
  padding-top: 16px;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.semantic-queue-scheduler {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 20px;
  padding: 12px 14px;
  border-radius: 8px;
  background: rgba(var(--v-theme-primary), 0.06);
}
</style>
