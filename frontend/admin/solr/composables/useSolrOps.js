import { computed, reactive, ref } from 'vue';
import { useSolrApi } from './useSolrApi.js';

/**
 * Shared Solr admin state: connection health, counts, and in-flight indexing flags
 * so Stop All / Commit / Clear work from the sidebar across pages.
 */
const ping = reactive({ loading: false, status: null, message: '', lastAt: null });
const counts = reactive({
  loading: false,
  db: null,
  index: null,
  error: '',
});
const core = reactive({
  loading: false,
  name: '',
  exists: false,
  status: null,
  message: '',
});
const system = reactive({ loading: false, data: null, error: '' });

const indexing = reactive({
  studies: false,
  variables: false,
  bySurvey: false,
  citations: false,
  schema: false,
});

const toast = reactive({ open: false, message: '', color: 'success' });
const busyAction = ref('');

let apiSingleton = null;

function api() {
  if (!apiSingleton) apiSingleton = useSolrApi();
  return apiSingleton;
}

export function useSolrOps() {
  const isAnyIndexing = computed(
    () =>
      indexing.studies ||
      indexing.variables ||
      indexing.bySurvey ||
      indexing.citations ||
      indexing.schema
  );

  function showToast(message, color = 'success') {
    toast.message = message;
    toast.color = color;
    toast.open = true;
  }

  function stopAll() {
    indexing.studies = false;
    indexing.variables = false;
    indexing.bySurvey = false;
    indexing.citations = false;
    indexing.schema = false;
    showToast('Stopped in-flight indexing loops', 'warning');
  }

  async function refreshPing() {
    ping.loading = true;
    try {
      const data = await api().ping();
      const result = data.result || {};
      ping.status = result.status || (data.status === 'success' ? 'OK' : 'ERROR');
      ping.message = result.message || result.note || '';
      ping.lastAt = new Date();
    } catch (e) {
      ping.status = 'ERROR';
      ping.message = e.message;
      ping.lastAt = new Date();
    } finally {
      ping.loading = false;
    }
  }

  async function refreshCounts() {
    counts.loading = true;
    counts.error = '';
    try {
      const [db, index] = await Promise.all([api().dbCounts(), api().indexCounts()]);
      counts.db = db.result || null;
      counts.index = index.result || null;
    } catch (e) {
      counts.error = e.message;
    } finally {
      counts.loading = false;
    }
  }

  async function refreshCore() {
    core.loading = true;
    core.message = '';
    try {
      const data = await api().coreCheckConfigured();
      core.name = data.configured_core || '';
      core.exists = !!data.exists;
      core.status = data.core_status || null;
      if (data.status === 'error' || (data.message && !core.exists)) {
        core.message = data.message || '';
      }
    } catch (e) {
      core.message = e.message;
      core.exists = false;
      core.status = null;
    } finally {
      core.loading = false;
    }
  }

  async function refreshSystem() {
    system.loading = true;
    system.error = '';
    try {
      const data = await api().systemInfo();
      system.data = data.result || null;
    } catch (e) {
      system.error = e.message;
      system.data = null;
    } finally {
      system.loading = false;
    }
  }

  async function refreshDashboard() {
    await Promise.all([refreshPing(), refreshCounts(), refreshCore(), refreshSystem()]);
  }

  async function commit() {
    busyAction.value = 'commit';
    try {
      await api().commit();
      showToast('Solr commit completed');
      await refreshCounts();
    } catch (e) {
      showToast(e.message, 'error');
    } finally {
      busyAction.value = '';
    }
  }

  async function clearIndex() {
    busyAction.value = 'clear';
    try {
      await api().clearIndex();
      showToast('Solr index cleared', 'warning');
      await refreshCounts();
    } catch (e) {
      showToast(e.message, 'error');
    } finally {
      busyAction.value = '';
    }
  }

  return {
    ping,
    counts,
    core,
    system,
    indexing,
    toast,
    busyAction,
    isAnyIndexing,
    showToast,
    stopAll,
    refreshPing,
    refreshCounts,
    refreshCore,
    refreshSystem,
    refreshDashboard,
    commit,
    clearIndex,
    api,
  };
}
