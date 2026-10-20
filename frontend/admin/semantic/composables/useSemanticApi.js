import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Talks to application/controllers/api/admin/Semantic.php. Most calls are a thin proxy in front of nada-ai's own
 * admin/ingest/jobs HTTP API: their bodies are nada-ai's JSON shapes, relayed as-is, and errors are nada-ai's own
 * (e.g. FastAPI's {detail: "..."}). The change-tracking calls (search_index_*: status, diffs, coverage, queue, sync,
 * requeue, rebuild) are answered by NADA itself from its tracking tables, as {status:'success', ...} or
 * {status:'error', message: CODE}. Access checks in PHP answer {message: "ACCESS_DENIED"}.
 */
export function useSemanticApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  function base() {
    return apiBaseUrl.value || '';
  }

  /** NADA's change-tracking error codes, as text. */
  const TRACKING_ERRORS = {
    INDEX_ENGINE_NOT_NADA_AI:
      'NADA does not track catalog changes for NADA-AI. Set "Index kept in sync" to NADA-AI in Site configurations › Search.',
    INDEX_GENERATION_MISMATCH: 'NADA tracks changes for another NADA-AI index than the one answering.',
    TRACKING_DISABLED: 'Change tracking is off.',
    SEARCH_INDEX_META_MISSING: 'The search index tables are not set up: run the database migrations.',
  };

  /** Best-effort message extraction from either error shape described above. */
  function errorMessage(e) {
    const raw = e?.response?.data?.detail || e?.response?.data?.message || e?.message || 'Request failed';
    return TRACKING_ERRORS[raw] || raw;
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

  /**
   * Published variables in the database vs. what the variable search index holds — totals only.
   * @returns {Promise<{database:{variables:number,studies:number}, index:{ok:boolean, data?:{exists:boolean,variables:number,studies:number}, error?:string, status?:number|null}}>}
   */
  function getVariablesStats() {
    return _run(async () => (await axios.get(`${base()}variables_stats`)).data);
  }

  /**
   * Which engine serves studies, variables and citations, what nada-ai runs and can serve, and whether nada-ai is
   * being called (the outage breaker and the fallbacks counted this hour).
   */
  function getEngineStatus() {
    return _run(async () => (await axios.get(`${base()}engine_status`)).data);
  }

  /**
   * Citations in the database vs. what the citation search index holds (all and published).
   * @returns {Promise<{database:{citations:number,published:number}, index:{ok:boolean, data?:{exists:boolean,citations:number,published:number}, error?:string, status?:number|null}}>}
   */
  function getCitationsStats() {
    return _run(async () => (await axios.get(`${base()}citations_stats`)).data);
  }

  /** Sync only the citation index, as a background job. Omit `ids` for every citation in the catalog. */
  function syncCitations(ids) {
    const body = Array.isArray(ids) ? { ids } : {};
    return _run(async () => (await axios.post(`${base()}citations_sync`, body)).data);
  }

  /**
   * Which studies' variables the index is missing or holds a different number of (per study, never per variable).
   * `gaps` is capped at 200, worst first; `gap_total` counts them all.
   */
  function getVariablesCoverage() {
    return _run(async () => (await axios.get(`${base()}variables_coverage`)).data);
  }

  /**
   * The site's catalog search through the semantic provider and the configured engine, with debug output.
   * @param {{query:string, type?:string, from?:number, to?:number, limit?:number, offset?:number}} body
   */
  function search(body) {
    return _run(async () => (await axios.post(`${base()}search`, body)).data);
  }

  /** Drop the search index with no reindex attached — distinct from indexAll({recreate_index: true}). */
  function deleteIndex() {
    return _run(async () => (await axios.post(`${base()}index_drop`, null, { params: { confirm: 'true' } })).data);
  }

  /** Empty NADA-AI's study index (new mapping, new generation); the chunk and variable indexes are kept. */
  function recreateStudiesIndex() {
    return _run(async () => (await axios.post(`${base()}studies_index_recreate`, null, { params: { confirm: 'true' } })).data);
  }

  /** Empty NADA-AI's variable index (new mapping) and index every variable again, as one NADA-AI job. */
  function recreateVariablesIndex() {
    return _run(async () => (await axios.post(`${base()}variables_index_recreate`, null, { params: { confirm: 'true' } })).data);
  }

  /** Empty NADA-AI's citation index (new mapping) and index every citation again, as one NADA-AI job. */
  function recreateCitationsIndex() {
    return _run(async () => (await axios.post(`${base()}citations_index_recreate`, null, { params: { confirm: 'true' } })).data);
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

  /** Sync only the variable index, as a background job. Omit `idnos` for every variable in the catalog. */
  function syncVariables(idnos) {
    const body = Array.isArray(idnos) ? { idnos } : {};
    return _run(async () => (await axios.post(`${base()}variables_sync`, body)).data);
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
    return _run(async () => (await axios.post(`${base()}job_cancel/${encodeURIComponent(id)}`)).data);
  }

  /** Pause NADA-AI's scheduler: it finishes the item in hand, then starts nothing until resumed. */
  function pauseScheduler() {
    return _run(async () => (await axios.post(`${base()}scheduler_pause`)).data);
  }

  /** Resume NADA-AI's scheduler: it carries on from the change queue. */
  function resumeScheduler() {
    return _run(async () => (await axios.post(`${base()}scheduler_resume`)).data);
  }

  function getSyncStatus() {
    return _run(async () => (await axios.get(`${base()}search_index_status`)).data);
  }

  function listChangeQueue({ status = 'pending', limit = 100 } = {}) {
    return _run(async () => (await axios.get(`${base()}search_index_queue`, { params: { status, limit } })).data);
  }

  /**
   * Queue in NADA what the index needs to match the catalog: deletes for studies gone from it, full indexing for
   * the ones not indexed and, with refreshIndexed, a partial update (published status, filters) of the indexed ones.
   * dataType narrows studies to one type; removeOnly queues the deletes only. NADA-AI then works through the queue.
   * @returns {Promise<{queued: Object<string, {delete:number, upsert_full:number, upsert_partial:number}>}>}
   */
  function syncIndex({ objectType = 'all', dataType = null, refreshIndexed = false, removeOnly = false } = {}) {
    const params = { object_type: objectType };
    if (dataType) params.data_type = dataType;
    if (refreshIndexed) params.refresh_indexed = '1';
    if (removeOnly) params.remove_only = '1';
    return _run(async () => (await axios.post(`${base()}search_index_sync`, null, { params })).data);
  }

  /** After the index was emptied: clear NADA's tracking and, unless queue is false, queue everything. */
  function rebuildTracking({ queue = true } = {}) {
    return _run(
      async () => (await axios.post(`${base()}search_index_rebuild`, null, { params: { queue: queue ? '1' : '0' } })).data
    );
  }

  /** Queue one study or citation for full indexing (a deleted one for removal). */
  function requeueObject(objectType, objectId) {
    return _run(
      async () => (await axios.post(`${base()}search_index_requeue`, { object_type: objectType, object_id: objectId })).data
    );
  }

  /**
   * Catalog changes waiting to be indexed (newest first) and the latest results, each with the study's or citation's
   * title. @returns {Promise<{waiting: object[], sync_waiting: number, results: object[]}>}
   */
  function getRecentChanges(limit = 10) {
    return _run(async () => (await axios.get(`${base()}search_index_recent`, { params: { limit } })).data);
  }

  /** Queue a variables-only update of these studies (by sid). @returns {Promise<{queued: number}>} */
  function queueVariables(sids) {
    return _run(async () => (await axios.post(`${base()}search_index_queue_variables`, { sids })).data);
  }

  /** NADA-AI reports what its index already holds (a job), then NADA queues the rest — no rebuild. */
  function importIndex() {
    return _run(async () => (await axios.post(`${base()}search_index_import`)).data);
  }

  /** NADA-AI's queue scheduler as it reports it: state, pause reason, current item, heartbeat. */
  function getSchedulerStatus() {
    return _run(async () => (await axios.get(`${base()}scheduler_status`)).data);
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
   * instead of all of objectType at once. `removeOnly` only deletes (Clean up).
   */
  function reconcileDiffNow(objectType = 'survey', dataType = null, { removeOnly = false } = {}) {
    const params = { object_type: objectType };
    if (dataType) params.data_type = dataType;
    if (removeOnly) params.remove_only = '1';
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
    getVariablesStats,
    getEngineStatus,
    getVariablesCoverage,
    getCitationsStats,
    syncCitations,
    search,
    deleteIndex,
    recreateStudiesIndex,
    recreateVariablesIndex,
    recreateCitationsIndex,
    warmupEmbeddings,
    indexOne,
    indexByIdno,
    indexAll,
    syncVariables,
    listJobs,
    getJob,
    cancelJob,
    getSyncStatus,
    listChangeQueue,
    reconcileNow,
    requeueFailed,
    syncIndex,
    rebuildTracking,
    requeueObject,
    importIndex,
    getRecentChanges,
    queueVariables,
    getSchedulerStatus,
    pauseScheduler,
    resumeScheduler,
    getDiffSummary,
    reconcileDiffNow,
    getDiffMissing,
    getDiffStale,
    getTypeBreakdown,
  };
}
