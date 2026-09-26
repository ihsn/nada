import { computed, ref } from 'vue';
import { useSemanticApi } from './useSemanticApi.js';

/**
 * What serves catalog search right now, and whether nada-ai is answering (GET engine_status).
 *
 * Module-level state shared by the banner shown on every page and the Overview card. Refreshes are throttled: the
 * banner asks on every page change, and the answer (an outage breaker, an hour's counters) does not change that fast.
 */
const status = ref(null);
const error = ref(null);
const loading = ref(false);
let lastLoaded = 0;
let inFlight = null;

const THROTTLE_MS = 10000;

export const ENGINE_LABELS = {
  database: 'Database',
  solr: 'Solr',
  opensearch: "OpenSearch (NADA's built-in)",
  nada_ai_opensearch: 'nada-ai with OpenSearch',
  nada_ai_qdrant: 'nada-ai with Qdrant',
};

/** What a serving engine is called in the "serving now" line (provider_for() answers database | solr | opensearch | nada_ai). */
export const SERVING_LABELS = {
  database: 'database',
  solr: 'Solr',
  opensearch: 'OpenSearch',
  nada_ai: 'nada-ai',
};

const FALLBACK_KIND_LABELS = { outage: 'nada-ai failed', breaker_open: 'nada-ai paused after a failure' };

function clock(unixSeconds) {
  return unixSeconds ? new Date(unixSeconds * 1000).toLocaleTimeString() : '';
}

export function useEngineStatus() {
  const { getEngineStatus } = useSemanticApi();

  async function refresh({ force = false } = {}) {
    if (inFlight) return inFlight;
    if (!force && status.value && Date.now() - lastLoaded < THROTTLE_MS) return null;
    loading.value = true;
    inFlight = (async () => {
      try {
        status.value = await getEngineStatus();
        error.value = null;
        lastLoaded = Date.now();
      } catch (e) {
        error.value = e;
      } finally {
        loading.value = false;
        inFlight = null;
      }
    })();
    return inFlight;
  }

  const engine = computed(() => status.value?.engine || null);
  const engineLabel = computed(() => ENGINE_LABELS[engine.value] || engine.value || '—');
  const usesNadaAi = computed(() => String(engine.value || '').startsWith('nada_ai_'));
  const breaker = computed(() => status.value?.breaker || null);
  const breakerOpen = computed(() => usesNadaAi.value && !!breaker.value?.open);
  const mismatch = computed(() => (usesNadaAi.value ? status.value?.backend_mismatch || null : null));

  /** Fallbacks counted this hour: [{kind, label, count}]. PHP sends an empty list where the counts are empty. */
  const fallbacks = computed(() => {
    const counts = breaker.value?.fallbacks_this_hour;
    if (!counts || Array.isArray(counts)) return [];
    return Object.entries(counts).map(([kind, count]) => ({ kind, label: FALLBACK_KIND_LABELS[kind] || kind, count }));
  });
  const fallbackTotal = computed(() => fallbacks.value.reduce((sum, row) => sum + row.count, 0));

  /** Problems worth an admin's attention, worst first: [{key, type, text}]. Empty when everything is fine. */
  const problems = computed(() => {
    const out = [];
    if (!usesNadaAi.value || !status.value) return out;
    if (mismatch.value) {
      out.push({
        key: 'mismatch',
        type: 'warning',
        text: `The search engine is set to ${engineLabel.value}, but nada-ai is running ${mismatch.value.actual}. Catalog searches fail until they match: change the search engine in Site configurations > Search, or restart nada-ai with the matching backend.`,
      });
    }
    if (breakerOpen.value) {
      const b = breaker.value;
      const serves =
        status.value.policy === 'error'
          ? 'Searches fail with an error'
          : 'Searches are served from the catalog database (no semantic ranking; variable and citation searches use the database)';
      out.push({
        key: 'breaker',
        type: status.value.policy === 'error' ? 'error' : 'warning',
        text: `nada-ai is not answering${b.reason ? ` (${b.reason})` : ''}${b.since ? ` since ${clock(b.since)}` : ''}. ${serves} until ${clock(b.until)}, when nada-ai is tried again.`,
      });
    } else if (fallbackTotal.value > 0) {
      out.push({
        key: 'fallbacks',
        type: 'info',
        text: `${fallbackTotal.value} search${fallbackTotal.value === 1 ? ' was' : 'es were'} served by the database this hour because nada-ai did not answer.`,
      });
    }
    return out;
  });

  return {
    status,
    error,
    loading,
    refresh,
    engine,
    engineLabel,
    usesNadaAi,
    breaker,
    breakerOpen,
    mismatch,
    fallbacks,
    fallbackTotal,
    problems,
  };
}
