import { ref, computed, unref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

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
 * Admin timeseries Mongo API for one study (catalogue idno).
 * Base: /api/admin/timeseries/data/{idno}/…
 */
export function useStudyTimeseriesApi(studyIdno) {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);

  const idnoEncoded = computed(() => encodeURIComponent(String(unref(studyIdno) ?? '').trim()));

  function dataPath() {
    const base = (apiBaseUrl.value || '').replace(/\/$/, '');
    return `${base}/data/${idnoEncoded.value}`;
  }

  function noCacheParams(extra = {}) {
    return { _nocache: Date.now(), ...extra };
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
    if (data.status !== 'success') throw new Error(data.message || 'Value counts summary request failed');
    return data.result?.summary ?? { total_rows: 0, total_distinct_codes: 0, total_observations: 0, components: [] };
  }

  async function syncValueCounts() {
    try {
      const { data } = await axios.post(
        `${dataPath()}/sync-counts`,
        {},
        { headers: { 'Content-Type': 'application/json' }, withCredentials: true }
      );
      if (data.status !== 'success') {
        throw new Error(data.message || 'Sync value counts failed');
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
   * Multipart CSV import — matches POST …/data/{idno}/import
   * @param {{ file: File; delimiter: string; mapping?: Record<string, string>; ensureUniqueIndex?: boolean }} opts
   */
  /**
   * Full study rehash: POST …/data/{idno}/rehash — recomputes key_hash / key_spec_rev from Mongo + current DSD.
   * @param {{ limit?: number }} [opts] omit limit for full rehash (clears ts_sync_required on success)
   */
  async function rehashData(opts = {}) {
    const body = {};
    if (opts.limit != null && Number(opts.limit) > 0) {
      body.limit = Number(opts.limit);
    }
    try {
      const { data } = await axios.post(`${dataPath()}/rehash`, body, {
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

  async function importCsvData(opts) {
    const form = new FormData();
    form.append('file', opts.file);
    form.append('delimiter', opts.delimiter ?? ',');
    const mapping = opts.mapping;
    if (mapping && typeof mapping === 'object' && Object.keys(mapping).length > 0) {
      form.append('mapping', JSON.stringify(mapping));
    }
    form.append('ensure_unique_index', opts.ensureUniqueIndex === false ? '0' : '1');
    try {
      const { data } = await axios.post(`${dataPath()}/import`, form, {
        withCredentials: true,
      });
      if (data.status !== 'success') {
        throw new Error(data.message || 'Import failed');
      }
      return data.result ?? {};
    } catch (err) {
      throw new Error(messageFromApiError(err));
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
    rehashData,
  };
}
