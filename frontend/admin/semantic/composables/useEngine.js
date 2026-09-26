import { ref } from 'vue';
import { useSemanticApi } from './useSemanticApi.js';

/**
 * Which engine nada-ai is running (backend: 'opensearch' | 'qdrant'), for pages that need to know before
 * deciding whether a Qdrant-only action even applies (the Collection tab, Danger Zone's drop collection) —
 * see docs on GET /admin/qdrant/collection and DELETE /admin/qdrant/collection being Qdrant-only.
 *
 * Module-level cache, fetched once per page load and shared by every caller: unlike useCoverageBreakdown()
 * (each call re-fetches its own copy, fine for a tab someone opens once), a page can call this on every mount
 * while navigating tabs, and the engine does not change during a session — refetching it each time would be
 * exactly the kind of avoidable request this session's nada-ai work has been about not doing.
 */
const engine = ref(null);
const engineLoading = ref(false);
const engineError = ref(null);
let inFlight = null;

export function useEngine() {
  const { getHealth } = useSemanticApi();

  async function ensureEngine() {
    if (engine.value !== null || inFlight) return inFlight;
    engineLoading.value = true;
    engineError.value = null;
    inFlight = (async () => {
      try {
        const health = await getHealth();
        engine.value = health?.backend || null;
      } catch (e) {
        engineError.value = e;
      } finally {
        engineLoading.value = false;
        inFlight = null;
      }
    })();
    return inFlight;
  }

  return { engine, engineLoading, engineError, ensureEngine };
}
