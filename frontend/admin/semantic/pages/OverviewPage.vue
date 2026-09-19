<template>
  <div class="admin-semantic-stack">
    <HealthStrip
      :overview="overview"
      :loading="loading"
      :error="error ? errorMessage(error) : null"
      :can-edit="canEdit"
      :warming-up="warmingUp"
      @refresh="loadAll"
      @warmup="warmup"
    />

    <v-alert v-if="summaryError" type="error" variant="tonal" density="compact">
      {{ summaryErrorMessage(summaryError) }}
    </v-alert>
    <KpiStrip v-else :tiles="kpiTiles" :loading="loadingSummary" />

    <div class="semantic-overview-body">
      <v-card elevation="1" rounded="lg" class="semantic-overview-main">
        <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
          Coverage by type
          <v-spacer />
          <span class="text-caption text-medium-emphasis font-weight-regular">
            Catalog records compared with indexed entries
          </span>
        </v-card-title>
        <v-divider />
        <v-card-text>
          <CoverageTable
            :rows="sortedTypeBreakdown"
            :total="typeBreakdownTotal"
            :loading="loadingTypeBreakdown"
            :error="typeBreakdownError ? typeBreakdownErrorMessage(typeBreakdownError) : null"
            :row-segments="rowSegments"
            @select-errors="openTypeErrors"
          />
          <div class="text-caption text-medium-emphasis mt-2">
            Click an error count to open those rows on Index.
          </div>
        </v-card-text>
      </v-card>

      <div class="semantic-overview-side admin-semantic-stack">
        <v-card elevation="1" rounded="lg">
          <v-card-title class="semantic-card-title d-flex align-center">
            Recent activity
            <v-spacer />
            <router-link to="/jobs" class="text-caption text-decoration-none">View all →</router-link>
          </v-card-title>
          <v-divider />
          <v-list density="compact" class="py-1">
            <template v-for="(job, i) in recentJobs" :key="job.id">
              <v-list-item class="py-2">
                <div class="d-flex align-center justify-space-between ga-2">
                  <span class="text-body-2 text-truncate">{{ humanizeKey(job.key) }}</span>
                  <v-chip size="x-small" :color="jobStatusColor(job.status)">{{ job.status }}</v-chip>
                </div>
                <div v-if="job.result" class="text-caption mt-1">
                  <span v-if="job.result.failed" class="text-error">{{ job.result.failed }} failed</span>
                  <span v-if="job.result.failed && job.result.indexed"> · </span>
                  <span v-if="job.result.indexed">{{ job.result.indexed }} indexed</span>
                </div>
                <div class="text-caption text-medium-emphasis mt-1">{{ formatTime(job.created_at) }}</div>
              </v-list-item>
              <v-divider v-if="i < recentJobs.length - 1" />
            </template>
            <v-list-item v-if="!loadingRecentJobs && recentJobs.length === 0">
              <span class="text-body-2 text-medium-emphasis">No jobs yet. Start from Index.</span>
            </v-list-item>
          </v-list>
        </v-card>

        <v-card elevation="1" rounded="lg">
          <v-card-title class="semantic-card-title d-flex align-center">
            Change queue
            <v-spacer />
            <router-link to="/sync" class="text-caption text-decoration-none">View →</router-link>
          </v-card-title>
          <v-divider />
          <v-card-text>
            <p class="text-caption text-medium-emphasis mb-4">
              Live catalog edits waiting to be indexed — separate from the coverage table.
            </p>
            <div class="d-flex" style="gap: 32px">
              <div>
                <div class="text-caption text-medium-emphasis">Pending</div>
                <div class="text-h6 font-weight-bold mt-1">{{ formatCount(syncStatus?.queue?.pending) }}</div>
              </div>
              <div>
                <div class="text-caption text-medium-emphasis">Failed</div>
                <div
                  class="text-h6 font-weight-bold mt-1"
                  :class="(syncStatus?.queue?.failed ?? 0) > 0 ? 'text-error' : ''"
                >
                  {{ formatCount(syncStatus?.queue?.failed) }}
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { useCoverageBreakdown } from '../composables/useCoverageBreakdown.js';
import { formatCount, humanizeKey } from '../typeLabels.js';
import HealthStrip from '../components/HealthStrip.vue';
import KpiStrip from '../components/KpiStrip.vue';
import CoverageTable from '../components/CoverageTable.vue';

defineOptions({ name: 'SemanticOverviewPage' });

const router = useRouter();
const { canEdit } = useAppConfig();
const { loading, error, errorMessage, getOverview, warmupEmbeddings } = useSemanticApi();
const { loading: loadingRecentJobs, listJobs } = useSemanticApi();
const { getSyncStatus } = useSemanticApi();

const {
  loadingSummary,
  summaryError,
  summaryErrorMessage,
  loadingTypeBreakdown,
  typeBreakdownError,
  typeBreakdownErrorMessage,
  typeBreakdownTotal,
  sortedTypeBreakdown,
  rowSegments,
  kpiTiles,
  loadCoverage,
} = useCoverageBreakdown();

const overview = ref(null);
const warmingUp = ref(false);
const recentJobs = ref([]);
const syncStatus = ref(null);

function jobStatusColor(status) {
  return { pending: 'grey', running: 'primary', succeeded: 'success', failed: 'error', cancelled: 'warning' }[status] || 'grey';
}

function formatTime(iso) {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return iso;
  }
}

function openTypeErrors(dataType) {
  router.push({ path: '/diff', query: { type: dataType, hasError: '1' } });
}

async function loadRecentJobs() {
  try {
    const data = await listJobs({ limit: 4 });
    recentJobs.value = data?.jobs || [];
  } catch {
    // Jobs page has the full view
  }
}

async function loadSyncStatus() {
  try {
    syncStatus.value = await getSyncStatus();
  } catch {
    syncStatus.value = null;
  }
}

async function loadOverview() {
  try {
    overview.value = await getOverview();
  } catch {
    // error ref already set by the composable
  }
}

async function loadAll() {
  await Promise.all([loadOverview(), loadCoverage(), loadRecentJobs(), loadSyncStatus()]);
}

async function warmup() {
  warmingUp.value = true;
  try {
    await warmupEmbeddings();
    await loadOverview();
  } catch {
    // error ref already set by the composable
  } finally {
    warmingUp.value = false;
  }
}

onMounted(loadAll);
</script>

<style scoped>
.semantic-overview-body {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.semantic-overview-main {
  min-width: 0;
  flex: 1 1 auto;
}

.semantic-overview-side {
  width: 100%;
}

@media (min-width: 1100px) {
  .semantic-overview-body {
    flex-direction: row;
    align-items: flex-start;
  }

  .semantic-overview-side {
    width: 300px;
    flex: none;
  }
}
</style>
