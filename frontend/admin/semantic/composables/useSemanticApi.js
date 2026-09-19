import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Talks to application/controllers/api/admin/Semantic.php, a thin server-side
 * proxy in front of nada-ai's own admin/ingest/jobs HTTP API. Response bodies
 * on success are nada-ai's own JSON shapes, relayed as-is (not a NADA-style
 * {status:'success', ...} envelope) — errors come back the same way: either
 * nada-ai's own error body (e.g. FastAPI's {detail: "..."}) or, for access
 * checks handled in PHP before ever reaching nada-ai, {message: "ACCESS_DENIED"}.
 */
export function useSemanticApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  function base() {
    return apiBaseUrl.value || '';
  }

  /** Best-effort message extraction from either error shape described above. */
  function errorMessage(e) {
    return e?.response?.data?.detail || e?.response?.data?.message || e?.message || 'Request failed';
  }

  async function _run(fn) {
    loading.value = true;
    error.value = null;
    try {
      return await fn();
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  function getOverview() {
    return _run(async () => (await axios.get(`${base()}overview`)).data);
  }

  /** @param {{query:string, mode?:string, filters?:object, size?:number, include_facets?:boolean, facet_fields?:string[]}} body */
  function search(body) {
    return _run(async () => (await axios.post(`${base()}search`, body)).data);
  }

  function getCollection() {
    return _run(async () => (await axios.get(`${base()}collection`)).data);
  }

  /** Per catalog_type: {catalog_total, indexed_documents}. */
  function getTypeCounts() {
    return _run(async () => (await axios.get(`${base()}type_counts`)).data);
  }

  /** Drop the Qdrant collection with no reindex attached — distinct from indexAll({recreate_index: true}). */
  function deleteCollection() {
    return _run(async () => (await axios.delete(`${base()}collection`, { params: { confirm: 'true' } })).data);
  }

  /** Loads the embedding model into memory now instead of on first search/ingest. */
  function warmupEmbeddings() {
    return _run(async () => (await axios.post(`${base()}warmup`)).data);
  }

  /** @param {{catalog_type:string, ps?:number, limit?:number, force?:boolean, recreate_index?:boolean}} body */
  function indexOne(body) {
    return _run(async () => (await axios.post(`${base()}index`, body)).data);
  }

  /** Index one catalog idno. `type` is NADA's surveys.type; `force` re-fetches a failed/cached load. */
  function indexByIdno({ idno, type, force = false } = {}) {
    return _run(async () => (await axios.post(`${base()}index_idno`, { idno, type, force })).data);
  }

  /** @param {{ps?:number, limit?:number, force?:boolean, recreate_index?:boolean}} body */
  function indexAll(body = {}) {
    return _run(async () => (await axios.post(`${base()}index_all`, body)).data);
  }

  /** @param {{status?:string, limit?:number}} params */
  function listJobs(params = {}) {
    return _run(async () => (await axios.get(`${base()}jobs`, { params })).data);
  }

  function getJob(id) {
    return _run(async () => (await axios.get(`${base()}job/${encodeURIComponent(id)}`)).data);
  }

  function cancelJob(id) {
    return _run(async () => (await axios.delete(`${base()}job/${encodeURIComponent(id)}`)).data);
  }

  function getSyncStatus() {
    return _run(async () => (await axios.get(`${base()}search_index_status`)).data);
  }

  function listChangeQueue({ status = 'pending', limit = 100 } = {}) {
    return _run(async () => (await axios.get(`${base()}search_index_queue`, { params: { status, limit } })).data);
  }

  function reconcileNow() {
    return _run(async () => (await axios.post(`${base()}search_index_reconcile`)).data);
  }

  /** Reset every failed queue row back to pending, or one object via {object_type, object_id}. */
  function requeueFailed() {
    return _run(async () => (await axios.post(`${base()}search_index_requeue`, { status: 'failed' })).data);
  }

  /** How many catalog entries aren't indexed / how many indexed entries are no longer in the catalog. */
  function getDiffSummary(objectType = 'survey') {
    return _run(
      async () => (await axios.get(`${base()}search_index_diff_summary`, { params: { object_type: objectType } })).data
    );
  }

  /**
   * Index everything missing, delete everything stale — submits a background job.
   * `dataType`, when given, narrows the run to one surveys.type value (e.g. 'geospatial')
   * instead of all of objectType at once.
   */
  function reconcileDiffNow(objectType = 'survey', dataType = null) {
    const params = { object_type: objectType };
    if (dataType) params.data_type = dataType;
    return _run(async () => (await axios.post(`${base()}search_index_reconcile_diff`, null, { params })).data);
  }

  /**
   * @returns {Promise<{items: {idno:string, type:string|null, last_error:string|null}[], total:number}>}
   * `hasError`, when true, narrows to rows with a recorded last_error (genuinely
   * attempted and failed) rather than every currently-unindexed row.
   */
  function getDiffMissing(objectType = 'survey', limit = 50, offset = 0, dataType = null, hasError = false) {
    const params = { object_type: objectType, limit, offset };
    if (dataType) params.data_type = dataType;
    if (hasError) params.has_error = 'true';
    return _run(async () => (await axios.get(`${base()}search_index_diff_missing`, { params })).data);
  }

  /** @returns {Promise<{items: {idno:string, type:string|null}[], total:number}>} */
  function getDiffStale(objectType = 'survey', limit = 50, offset = 0, dataType = null) {
    const params = { object_type: objectType, limit, offset };
    if (dataType) params.data_type = dataType;
    return _run(async () => (await axios.get(`${base()}search_index_diff_stale`, { params })).data);
  }

  /**
   * Per-data-type (microdata/geospatial/document/timeseries/...) catalog vs. index coverage.
   * @returns {Promise<{object_type:string, items: {data_type:string, catalog_total:number, indexed:number, missing:number, stale:number}[]}>}
   */
  function getTypeBreakdown(objectType = 'survey') {
    return _run(
      async () => (await axios.get(`${base()}search_index_type_breakdown`, { params: { object_type: objectType } })).data
    );
  }

  return {
    loading,
    error,
    errorMessage,
    getOverview,
    search,
    getCollection,
    getTypeCounts,
    deleteCollection,
    warmupEmbeddings,
    indexOne,
    indexByIdno,
    indexAll,
    listJobs,
    getJob,
    cancelJob,
    getSyncStatus,
    listChangeQueue,
    reconcileNow,
    requeueFailed,
    getDiffSummary,
    reconcileDiffNow,
    getDiffMissing,
    getDiffStale,
    getTypeBreakdown,
  };
}
