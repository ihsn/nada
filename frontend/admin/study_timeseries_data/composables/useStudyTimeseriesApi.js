import { ref, computed, unref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useResumableUpload } from '@/shared/composables/useResumableUpload';

/** Best-effort message from API JSON or axios error (handles non-JSON error bodies). */
function messageFromApiError(err) {
  const d = err?.response?.data;
  if (d && typeof d === 'object' && d.message != null && d.message !== '') {
    return String(d.message);
  }
  if (typeof d === 'string') {
    const t = d.trim();
    if (t.startsWith('{')) {
      try {
        const j = JSON.parse(t);
        if (j?.message) return String(j.message);
      } catch {
        /* ignore */
      }
    }
    if (t.length > 0 && t.length < 500) return t;
  }
  if (err?.message) return String(err.message);
  return 'Request failed';
}

/**
 * Admin timeseries Mongo API for one study (numeric surveys.id + id_format=id).
 * Base: /api/admin/timeseries/data/{sid}/…
 */
export function useStudyTimeseriesApi(studySid) {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const resumable = useResumableUpload();

  const sidEncoded = computed(() => encodeURIComponent(String(unref(studySid) ?? '').trim()));

  function dataPath() {
    const base = (apiBaseUrl.value || '').replace(/\/$/, '');
    return `${base}/data/${sidEncoded.value}`;
  }

  function noCacheParams(extra = {}) {
    return { _nocache: Date.now(), id_format: 'id', ...extra };
  }

  async function fetchSchema() {
    const { data } = await axios.get(`${dataPath()}/schema`, {
      params: noCacheParams(),
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'Schema request failed');
    return data.result ?? null;
  }

  async function fetchObservationCount() {
    const { data } = await axios.get(`${dataPath()}/count`, {
      params: noCacheParams(),
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'Count request failed');
    return data.result?.count ?? 0;
  }

  async function fetchValueCountsSummary() {
    const { data } = await axios.get(`${dataPath()}/value-counts-summary`, {
      params: noCacheParams(),
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'Dimension summaries request failed');
    return data.result?.summary ?? { total_rows: 0, total_distinct_codes: 0, total_observations: 0, components: [] };
  }

  async function syncValueCounts() {
    try {
      const { data } = await axios.post(
        `${dataPath()}/sync-counts`,
        {},
        { params: { id_format: 'id' }, headers: { 'Content-Type': 'application/json' }, withCredentials: true }
      );
      if (data.status !== 'success') {
        throw new Error(data.message || 'Sync dimension summaries failed');
      }
      return data.result ?? {};
    } catch (err) {
      throw new Error(messageFromApiError(err));
    }
  }

  /**
   * @param {{ limit?: number; offset?: number; sort?: 'asc'|'desc' }} [opts]
   */
  async function fetchData(opts = {}) {
    const offset = opts.offset ?? opts.skip ?? 0;
    const params = noCacheParams({
      limit: opts.limit ?? 25,
      offset,
      sort: opts.sort === 'desc' ? 'desc' : 'asc',
    });
    const { data } = await axios.get(`${dataPath()}`, { params, withCredentials: true });
    if (data.status !== 'success') throw new Error(data.message || 'Data request failed');
    const r = data.result ?? {};
    return {
      data: r.data ?? [],
      limit: r.limit ?? params.limit,
      offset: r.offset ?? offset,
      total: typeof r.total === 'number' ? r.total : 0,
      found: typeof r.found === 'number' ? r.found : (r.data?.length ?? 0),
    };
  }

  /**
   * POST …/data/{sid}/rehash — optional JSON { "limit": n }
   * @param {{ limit?: number }} [opts]
   */
  async function rehashData(opts = {}) {
    const body = {};
    if (opts.limit != null && Number(opts.limit) > 0) {
      body.limit = Number(opts.limit);
    }
    try {
      const { data } = await axios.post(`${dataPath()}/rehash`, body, {
        params: { id_format: 'id' },
        headers: { 'Content-Type': 'application/json' },
        withCredentials: true,
      });
      if (data.status !== 'success') {
        throw new Error(data.message || 'Rehash failed');
      }
      return data.result ?? {};
    } catch (err) {
      throw new Error(messageFromApiError(err));
    }
  }

  function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  /** Map final chunked-import progress to the result shape the page shows. */
  function importResultFromProgress(p) {
    return {
      inserted: p?.inserted ?? 0,
      lines_read: p?.lines_read ?? 0,
      ignored_columns: Array.isArray(p?.ignored_columns) ? p.ignored_columns : [],
      unparsed_time_periods: p?.unparsed_time_periods ?? null,
      resource_saved: p?.resource_saved !== false,
    };
  }

  /** GET …/data/{sid}/import-status — chunked import progress, or null when none. */
  async function fetchImportStatus() {
    try {
      const { data } = await axios.get(`${dataPath()}/import-status`, {
        params: noCacheParams(),
        withCredentials: true,
      });
      if (data.status !== 'success') throw new Error(data.message || 'Import status request failed');
      return data.result ?? null;
    } catch (err) {
      throw new Error(messageFromApiError(err));
    }
  }

  /**
   * Repeat POST …/data/{sid}/import-process until the staged import finishes. Each call works for a
   * bounded time on the server, so large files never hit request timeouts.
   * @param {(p: object) => void} [onProgress]
   */
  async function runStagedImport(onProgress) {
    let failures = 0;
    for (;;) {
      let progress;
      try {
        const { data } = await axios.post(
          `${dataPath()}/import-process`,
          { max_time: 30 },
          {
            params: { id_format: 'id' },
            headers: { 'Content-Type': 'application/json' },
            withCredentials: true,
            timeout: 10 * 60 * 1000,
          }
        );
        if (data.status !== 'success') throw new Error(data.message || 'Import failed');
        progress = data.result ?? {};
        failures = 0;
      } catch (err) {
        const status = err?.response?.status;
        // 409: a previous call is still running server-side; gateway/network errors: the call may
        // still be running. Progress is saved per batch, so waiting and retrying is safe.
        const retryable = status === 409 || status === 502 || status === 503 || status === 504 || !err?.response;
        if (retryable && failures < 30) {
          failures += 1;
          const p = err?.response?.data?.result;
          if (p && onProgress) onProgress({ phase: 'processing', ...p });
          await sleep(Math.min(2000 * failures, 15000));
          continue;
        }
        throw new Error(messageFromApiError(err));
      }
      if (onProgress) onProgress({ phase: 'processing', ...progress });
      if (progress.status === 'failed') {
        throw new Error(progress.error || 'Import failed');
      }
      if (!progress.has_more) {
        return importResultFromProgress(progress);
      }
    }
  }

  /**
   * Chunked CSV import: resumable upload → POST …/data/upload (stage + validate header) → repeated
   * import-process calls. Optional `dsdIdno` links that DSD before staging.
   * @param {{ file: File; delimiter: string; mapping?: Record<string, string>; ensureUniqueIndex?: boolean; dsdIdno?: string; onProgress?: (p: object) => void }} opts
   */
  async function importCsvData(opts) {
    const onProgress = opts.onProgress;
    let uploadId;
    try {
      const completed = await resumable.uploadFile(opts.file, {
        metadata: { source: 'indicator_timeseries', allowed_types: 'csv' },
        onProgress: (ev) => {
          if (onProgress) onProgress({ phase: 'uploading', loaded: ev.loaded, total: ev.total });
        },
      });
      uploadId = completed.upload_id;
    } catch (err) {
      throw new Error(messageFromApiError(err));
    }

    const form = new FormData();
    form.append('idno', String(unref(studySid) ?? '').trim());
    form.append('upload_id', uploadId);
    form.append('delimiter', opts.delimiter ?? ',');
    const dsdIdno = opts.dsdIdno != null ? String(opts.dsdIdno).trim() : '';
    if (dsdIdno !== '') {
      form.append('dsd_idno', dsdIdno);
    }
    const mapping = opts.mapping;
    if (mapping && typeof mapping === 'object' && Object.keys(mapping).length > 0) {
      form.append('mapping', JSON.stringify(mapping));
    }
    form.append('ensure_unique_index', opts.ensureUniqueIndex === false ? '0' : '1');
    if (onProgress) onProgress({ phase: 'staging' });
    try {
      const base = (apiBaseUrl.value || '').replace(/\/$/, '');
      const { data } = await axios.post(`${base}/data/upload`, form, {
        params: { id_format: 'id' },
        withCredentials: true,
      });
      if (data.status !== 'success') {
        throw new Error(data.message || 'Upload failed');
      }
    } catch (err) {
      throw new Error(messageFromApiError(err));
    }

    return runStagedImport(onProgress);
  }

  /**
   * POST …/data/{sid}/clear-data — remove all Mongo observations, dimension summaries, and canonical CSV (DSD link unchanged).
   */
  async function clearIndicatorData() {
    try {
      const { data } = await axios.post(
        `${dataPath()}/clear-data`,
        {},
        { params: { id_format: 'id' }, headers: { 'Content-Type': 'application/json' }, withCredentials: true }
      );
      if (data.status !== 'success') {
        throw new Error(data.message || 'Clear failed');
      }
      return data.result ?? {};
    } catch (err) {
      throw new Error(messageFromApiError(err));
    }
  }

  /** GET …/data/{idno}/presentation — default view settings plus observed code options per column. */
  async function fetchPresentation() {
    try {
      const { data } = await axios.get(`${dataPath()}/presentation`, {
        params: noCacheParams(),
        withCredentials: true,
      });
      if (data.status !== 'success') throw new Error(data.message || 'Default view request failed');
      return data.result ?? {};
    } catch (err) {
      throw new Error(messageFromApiError(err), { cause: err });
    }
  }

  /** POST …/data/{idno}/presentation — replace the default view settings (null removes them). */
  async function savePresentation(presentation) {
    // Schema errors come back as { message, errors: [{ property, message }] }; list them after the message.
    const withSchemaErrors = (d, fallback) => {
      const errs = Array.isArray(d?.errors) ? d.errors : [];
      const detail = errs.map((e) => [e?.property, e?.message].filter(Boolean).join(' ')).join('; ');
      const msg = String(d?.message || fallback).replace(/:\s*$/, '');
      return detail ? `${msg}: ${detail}` : msg;
    };
    try {
      const { data } = await axios.post(
        `${dataPath()}/presentation`,
        { data_presentation: presentation },
        { headers: { 'Content-Type': 'application/json' }, withCredentials: true }
      );
      if (data.status !== 'success') throw new Error(withSchemaErrors(data, 'Save failed'));
      return data.result ?? {};
    } catch (err) {
      const d = err?.response?.data;
      if (d && typeof d === 'object') throw new Error(withSchemaErrors(d, messageFromApiError(err)), { cause: err });
      throw err instanceof Error ? err : new Error(messageFromApiError(err), { cause: err });
    }
  }

  async function wrap(promise) {
    loading.value = true;
    try {
      return await promise;
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    dataPath,
    fetchSchema: () => wrap(fetchSchema()),
    fetchObservationCount: () => wrap(fetchObservationCount()),
    fetchValueCountsSummary: () => wrap(fetchValueCountsSummary()),
    fetchData: (opts) => wrap(fetchData(opts)),
    syncValueCounts,
    importCsvData,
    runStagedImport,
    fetchImportStatus,
    rehashData,
    clearIndicatorData,
    fetchPresentation,
    savePresentation,
  };
}
