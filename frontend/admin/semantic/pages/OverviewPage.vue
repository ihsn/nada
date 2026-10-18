<template>
  <div class="admin-semantic-stack">
    <IndexingActivityCard />

    <HealthStrip
      :overview="overview"
      :loading="loading"
      :error="error ? errorMessage(error) : null"
      :can-edit="canEdit"
      :warming-up="warmingUp"
      @refresh="loadAll"
      @warmup="warmup"
    />

    <EngineStatusCard ref="engineCard" />

    <div class="semantic-section-title">What is searchable</div>

    <v-card elevation="1" rounded="lg">
      <v-card-text class="d-flex align-center flex-wrap ga-4 py-3">
        <span class="font-weight-medium">Studies</span>
        <v-progress-circular v-if="loadingTypeBreakdown && !typeBreakdown.length" indeterminate size="18" width="2" />
        <span v-else-if="typeBreakdownError" class="text-error text-body-2">
          {{ typeBreakdownErrorMessage(typeBreakdownError) }}
        </span>
        <template v-else>
          <span class="text-body-2">
            <strong>{{ formatCount(studies.indexed) }}</strong> of {{ formatCount(studies.catalog_total) }} searchable
          </span>
          <v-chip size="small" variant="tonal" :color="studiesVerdict.color" :prepend-icon="studiesVerdict.icon">
            {{ studiesVerdict.text }}
          </v-chip>
        </template>
        <v-spacer />
        <router-link :to="{ name: 'indexing', hash: '#studies' }" class="text-body-2 text-decoration-none">
          Manage →
        </router-link>
      </v-card-text>
    </v-card>

    <VariablesCoverageCard ref="variablesCard" manage />
    <CitationsCoverageCard ref="citationsCard" manage />

    <div class="semantic-overview-body">
      <v-card elevation="1" rounded="lg" class="semantic-overview-main">
        <v-card-title class="semantic-card-title d-flex align-center">
          Recent activity
          <v-spacer />
          <router-link :to="{ name: 'activity', query: { tab: 'jobs' } }" class="text-caption text-decoration-none">
            View all →
          </router-link>
        </v-card-title>
        <v-divider />
        <v-list density="compact" class="py-1">
          <template v-for="(job, i) in recentJobs" :key="job.id">
            <v-list-item class="py-2">
              <div class="d-flex align-center justify-space-between ga-2">
                <span class="text-body-2 text-truncate">{{ humanizeKey(job.key) }}</span>
                <v-chip size="x-small" :color="jobOutcome(job).color">{{ jobOutcome(job).text }}</v-chip>
              </div>
              <div v-if="job.result" class="text-caption mt-1">
                <span v-if="jobProblems(job)" class="text-warning">{{ formatCount(jobProblems(job)) }} could not be indexed</span>
                <span v-if="jobProblems(job) && job.result.indexed"> · </span>
                <span v-if="job.result.indexed">{{ formatCount(job.result.indexed) }} indexed</span>
              </div>
              <div class="text-caption text-medium-emphasis mt-1">{{ formatTime(job.created_at) }}</div>
            </v-list-item>
            <v-divider v-if="i < recentJobs.length - 1" />
          </template>
          <v-list-item v-if="!loadingRecentJobs && recentJobs.length === 0">
            <span class="text-body-2 text-medium-emphasis">Nothing has run yet. Indexing starts from the Indexing page.</span>
          </v-list-item>
        </v-list>
      </v-card>

      <v-card elevation="1" rounded="lg" class="semantic-overview-side">
        <v-card-title class="semantic-card-title d-flex align-center">
          Waiting to be applied
          <v-spacer />
          <router-link :to="{ name: 'activity', query: { tab: 'queue' } }" class="text-caption text-decoration-none">
            View →
          </router-link>
        </v-card-title>
        <v-divider />
        <v-card-text>
          <p class="text-caption text-medium-emphasis mb-4">
            Catalog edits not yet in the search index. “Update all” on the Indexing page applies them.
          </p>
          <div class="d-flex" style="gap: 32px">
            <div>
              <div class="text-caption text-medium-emphasis">Waiting</div>
              <div class="text-h6 font-weight-bold mt-1">{{ formatCount(syncStatus?.queue?.pending) }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Failed</div>
              <div class="text-h6 font-weight-bold mt-1" :class="(syncStatus?.queue?.failed ?? 0) > 0 ? 'text-error' : ''">
                {{ formatCount(syncStatus?.queue?.failed) }}
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import IndexingActivityCard from '../components/IndexingActivityCard.vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { useCoverageBreakdown } from '../composables/useCoverageBreakdown.js';
import { useSyncHealth } from '../composables/useSyncHealth.js';
import { formatCount, humanizeKey } from '../typeLabels.js';
import HealthStrip from '../components/HealthStrip.vue';
import VariablesCoverageCard from '../components/VariablesCoverageCard.vue';
import CitationsCoverageCard from '../components/CitationsCoverageCard.vue';
import EngineStatusCard from '../components/EngineStatusCard.vue';

defineOptions({ name: 'SemanticOverviewPage' });

const { canEdit } = useAppConfig();
const { loading, error, errorMessage, getOverview, warmupEmbeddings } = useSemanticApi();
const { loading: loadingRecentJobs, listJobs } = useSemanticApi();
const { getSyncStatus } = useSemanticApi();

const {
  loadingTypeBreakdown,
  typeBreakdownError,
  typeBreakdownErrorMessage,
  typeBreakdown,
  typeBreakdownTotal: studies,
  loadTypeBreakdown,
} = useCoverageBreakdown();
// the coverage follows the indexing while NADA-AI works through the queue
useSyncHealth().onProgress(() => loadTypeBreakdown());

const overview = ref(null);
const variablesCard = ref(null);
const citationsCard = ref(null);
const engineCard = ref(null);
const warmingUp = ref(false);
const recentJobs = ref([]);
const syncStatus = ref(null);

/** The Studies chip: what is left to do, or that nothing is. Errors are counted in "to add". */
const studiesVerdict = computed(() => {
  const s = studies.value;
  const parts = [];
  if (s.missing) parts.push(`${formatCount(s.missing)} to add`);
  if (s.stale) parts.push(`${formatCount(s.stale)} to remove`);
  if (s.errors) parts.push(`${formatCount(s.errors)} with errors`);
  if (!parts.length) return { text: 'Up to date', color: 'success', icon: 'mdi-check-circle-outline' };
  return { text: parts.join(' · '), color: s.errors ? 'error' : 'warning', icon: 'mdi-alert-outline' };
});

/**
 * Studies a finished job could not index. Index jobs list them (load_errors, empty_docs, write errors); update
 * and clean-up jobs count them in `failed`.
 */
function jobProblems(job) {
  const r = job?.result;
  if (!r) return 0;
  if (typeof r.failed === 'number') return r.failed;
  return (r.load_errors?.length || 0) + (r.empty_docs?.length || 0) + (r.errors?.length || 0);
}

/** A job that ran to the end but could not index some studies did not simply succeed. */
function jobOutcome(job) {
  if (job.status === 'succeeded' && jobProblems(job) > 0) return { text: 'finished with problems', color: 'warning' };
  const colors = { pending: 'grey', running: 'primary', succeeded: 'success', failed: 'error', cancelled: 'warning' };
  return { text: job.status, color: colors[job.status] || 'grey' };
}

function formatTime(iso) {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return iso;
  }
}

async function loadRecentJobs() {
  try {
    const data = await listJobs({ limit: 4 });
    recentJobs.value = data?.jobs || [];
  } catch {
    // the Activity page has the full view
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
  await Promise.all([
    loadOverview(),
    loadTypeBreakdown(),
    loadRecentJobs(),
    loadSyncStatus(),
    variablesCard.value?.load(),
    citationsCard.value?.load(),
    engineCard.value?.load(),
  ]);
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
.semantic-section-title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.7);
  margin: 8px 0 -4px;
}

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
    width: 320px;
    flex: none;
  }
}
</style>
