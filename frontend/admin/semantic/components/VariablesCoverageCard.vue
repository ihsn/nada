<template>
  <!-- One line, since variables are secondary to studies; the studies to sync expand from the chip. -->
  <v-card elevation="1" rounded="lg">
    <v-card-text class="d-flex align-center flex-wrap ga-4 py-3">
      <span class="font-weight-medium">Variables</span>
      <v-progress-circular v-if="loading && !stats" indeterminate size="18" width="2" />
      <span v-else-if="error" class="text-error text-body-2">{{ errorMessage(error) }}</span>
      <template v-else-if="stats">
        <span class="text-body-2">
          Database <strong>{{ formatCount(stats.database.variables) }}</strong>
          <template v-if="indexed">
            · Indexed <strong>{{ formatCount(indexed.variables) }}</strong>
          </template>
        </span>
        <v-chip
          v-if="compactVerdict"
          size="small"
          variant="tonal"
          :color="compactVerdict.color"
          :prepend-icon="compactVerdict.icon"
          :append-icon="canExpand ? (expanded ? 'mdi-chevron-up' : 'mdi-chevron-down') : undefined"
          :style="canExpand ? 'cursor: pointer' : undefined"
          @click="canExpand && (expanded = !expanded)"
        >
          {{ compactVerdict.text }}
        </v-chip>
        <v-spacer />
        <router-link v-if="manage" :to="{ name: 'indexing', hash: '#variables' }" class="text-body-2 text-decoration-none">
          Manage →
        </router-link>
        <template v-else-if="!notApplicable">
          <v-btn
            size="small"
            variant="tonal"
            color="primary"
            prepend-icon="mdi-database-sync-outline"
            :loading="starting"
            :disabled="!canEdit || !hasGaps"
            @click="queueGaps(gapSids)"
          >
            Update
          </v-btn>
          <v-btn size="small" variant="text" :disabled="!canEdit || starting" @click="rebuild">Rebuild…</v-btn>
        </template>
      </template>
    </v-card-text>
    <v-expand-transition>
      <div v-if="expanded && canExpand" class="px-4 pb-4">
        <VariablesGapsTable
          :gaps="coverage.gaps"
          :total="coverage.gap_total"
          :can-edit="canEdit"
          :starting="starting"
          @sync="queueGaps"
        />
      </div>
    </v-expand-transition>
    <div v-if="started || startError" class="px-4 pb-4">
      <v-alert v-if="started" type="success" variant="tonal" density="compact">
        {{ started }}
        <router-link :to="{ name: 'activity', query: { tab: startedTab } }">Follow it in Activity →</router-link>
      </v-alert>
      <v-alert v-if="startError" :type="startConflict ? 'warning' : 'error'" variant="tonal" density="compact">
        {{ startError }}
        <router-link v-if="startConflict" :to="{ name: 'activity', query: { tab: 'jobs' } }">See it in Activity →</router-link>
      </v-alert>
    </div>
    <p v-if="detail && !hasGaps" class="text-caption text-medium-emphasis px-4 pb-3 mb-0">{{ detail }}</p>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { formatCount } from '../typeLabels.js';
import { conflictMessage } from '../jobConflict.js';
import VariablesGapsTable from './VariablesGapsTable.vue';

defineOptions({ name: 'SemanticVariablesCoverageCard' });

const props = defineProps({
  /** Overview: report only, with a link to the Indexing page instead of the actions. */
  manage: { type: Boolean, default: false },
});

const { canEdit } = useAppConfig();
const { loading, error, errorMessage, getVariablesStats, getVariablesCoverage, syncVariables, queueVariables, reconcileNow } =
  useSemanticApi();
const startedTab = ref('queue');
const coverage = ref(null);
const expanded = ref(false);
const stats = ref(null);
const starting = ref(false);
const started = ref(false);
const startError = ref(null);
/** The start was refused because a job holding the same key is running (shown as a warning with a link). */
const startConflict = ref(false);

/** nada-ai answers 501 when it has no variable index. */
const notApplicable = computed(() => stats.value?.index && !stats.value.index.ok && stats.value.index.status === 501);

/** The index side, or null when it is missing, unavailable or not applicable. */
const indexed = computed(() => {
  const idx = stats.value?.index;
  if (!idx?.ok || !idx.data?.exists) return null;
  return idx.data;
});

const difference = computed(() => (indexed.value ? stats.value.database.variables - indexed.value.variables : 0));

const verdict = computed(() => {
  if (!stats.value) return null;
  if (notApplicable.value) return { text: 'Not applicable', color: 'grey', icon: 'mdi-minus-circle-outline' };
  const idx = stats.value.index;
  if (!idx.ok) return { text: 'Index unavailable', color: 'error', icon: 'mdi-alert-circle-outline' };
  if (!idx.data.exists) return { text: 'Not indexed yet', color: 'warning', icon: 'mdi-alert-outline' };
  if (difference.value === 0 && indexed.value.studies === stats.value.database.studies) {
    return { text: 'In sync', color: 'success', icon: 'mdi-check-circle-outline' };
  }
  if (difference.value > 0) {
    return { text: `${formatCount(difference.value)} not indexed`, color: 'warning', icon: 'mdi-alert-outline' };
  }
  if (difference.value < 0) {
    return { text: `${formatCount(-difference.value)} extra in index`, color: 'warning', icon: 'mdi-alert-outline' };
  }
  return { text: 'Studies differ', color: 'warning', icon: 'mdi-alert-outline' };
});

const detail = computed(() => {
  if (!stats.value) return null;
  const idx = stats.value.index;
  if (notApplicable.value) return 'The variable search index is only available with the OpenSearch engine.';
  if (!idx.ok) return idx.error || null;
  if (!idx.data.exists) {
    return 'The variable index has not been created yet. Use Rebuild, or index a microdata study.';
  }
  if (difference.value > 0) {
    return 'Some variables are not indexed. Use Rebuild to index every variable again.';
  }
  if (difference.value < 0) {
    return 'The index holds more variables than the database publishes — usually studies removed or unpublished since they were indexed.';
  }
  return null;
});

const hasGaps = computed(() => (coverage.value?.gap_total || 0) > 0);
/** The studies whose variables Update re-indexes (the listed gaps; a long list is capped by the API). */
const gapSids = computed(() => (coverage.value?.gaps || []).map((g) => g.sid));
/** The list of studies to sync is an Indexing-page detail. */
const canExpand = computed(() => hasGaps.value && !props.manage);

/** Rebuild: every variable of every study again, as a NADA-AI job outside the change queue (a rare, heavy run). */
async function rebuild() {
  if (!window.confirm('Index every variable of every published microdata study again? This runs as a NADA-AI job outside the change queue.')) {
    return;
  }
  await run(async () => {
    await syncVariables(undefined);
    startedTab.value = 'jobs';
    return 'Variable rebuild started as a NADA-AI job.';
  });
}

/** The chip: a study-level gap outranks the totals, which can agree while studies differ. */
const compactVerdict = computed(() => {
  if (hasGaps.value) {
    const n = coverage.value.gap_total;
    return { text: `${formatCount(n)} ${n === 1 ? 'study' : 'studies'} to sync`, color: 'warning', icon: 'mdi-alert-outline' };
  }
  return verdict.value;
});

/** Update: queue a variables-only update of these studies; NADA-AI applies it from the change queue. */
async function queueGaps(sids) {
  await run(async () => {
    const data = await queueVariables(sids || []);
    await reconcileNow().catch(() => {});
    startedTab.value = 'queue';
    const n = Number(data?.queued) || 0;
    return `${formatCount(n)} ${n === 1 ? 'study' : 'studies'} queued for a variables update.`;
  });
}

async function run(fn) {
  starting.value = true;
  started.value = false;
  startError.value = null;
  startConflict.value = false;
  try {
    started.value = await fn();
  } catch (e) {
    const busy = conflictMessage(e);
    startConflict.value = busy !== null;
    startError.value = busy ?? errorMessage(e);
  } finally {
    starting.value = false;
  }
}

async function load() {
  try {
    stats.value = await getVariablesStats();
  } catch {
    stats.value = null;
  }
  try {
    coverage.value = await getVariablesCoverage();
  } catch {
    coverage.value = null;
  }
}

defineExpose({ load });

onMounted(load);
</script>
