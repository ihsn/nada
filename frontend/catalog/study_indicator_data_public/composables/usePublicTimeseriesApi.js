import { ref, computed, unref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * @typedef {Object} ObservationFilters
 * @property {string} [from] reporting year lower bound (Mongo _ts_year, inclusive); year or ISO date
 * @property {string} [to] reporting year upper bound (Mongo _ts_year, inclusive)
 * @property {Record<string, string[]>} [d] semantic roles: geography, time_period, periodicity, observation_value (catalog chart UI uses from/to for reporting-year range on _ts_year, not d[time_period])
 * @property {Record<string, string[]>} [c] DSD component name -> codes (comma-serialized for API)
 */

/**
 * Serialize filters for GET .../data and .../data/count.
 * PHP expects d[role]=comma,codes and c[COMPONENT]=comma,codes (see Timeseries_mongo_model::build_observation_query_filter).
 *
 * @param {Record<string, string|number|undefined>} params
 * @param {ObservationFilters} [filters]
 */
export function mergeObservationFiltersIntoParams(params, filters) {
  if (!filters) {
    return params;
  }
  if (filters.from) {
    params.from = filters.from;
  }
  if (filters.to) {
    params.to = filters.to;
  }
  if (filters.d) {
    for (const [role, vals] of Object.entries(filters.d)) {
      if (vals && vals.length) {
        params[`d[${role}]`] = vals.join(',');
      }
    }
  }
  if (filters.c) {
    for (const [name, vals] of Object.entries(filters.c)) {
      if (vals && vals.length) {
        params[`c[${name}]`] = vals.join(',');
      }
    }
  }
  return params;
}

/**
 * Public read-only timeseries API: /api/timeseries/data/{idno}/…
 */
export function usePublicTimeseriesApi(studyIdno) {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);

  const idnoEncoded = computed(() => encodeURIComponent(String(unref(studyIdno) ?? '').trim()));

  function dataPath() {
    const base = (apiBaseUrl.value || '').replace(/\/$/, '');
    return `${base}/data/${idnoEncoded.value}`;
  }

  function timeseriesApiRoot() {
    const base = (apiBaseUrl.value || '').replace(/\/$/, '');
    return base;
  }

  function noCacheParams(extra = {}) {
    return { _nocache: Date.now(), ...extra };
  }

  async function fetchSchema() {
    const { data } = await axios.get(`${dataPath()}/schema`, {
      params: noCacheParams(),
    });
    if (data.status !== 'success') throw new Error(data.message || 'Schema request failed');
    return data.result ?? null;
  }

  async function fetchFilterOptions() {
    const { data } = await axios.get(`${dataPath()}/filter-options`, {
      params: noCacheParams(),
    });
    if (data.status !== 'success') throw new Error(data.message || 'Filter options request failed');
    return data.result ?? { filters: [] };
  }

  /**
   * @param {ObservationFilters} [filters]
   */
  async function fetchObservationCount(filters) {
    const params = noCacheParams({});
    mergeObservationFiltersIntoParams(params, filters);
    const { data } = await axios.get(`${dataPath()}/count`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Count request failed');
    return data.result?.count ?? 0;
  }

  /**
   * @param {{
   *   limit?: number;
   *   offset?: number;
   *   sort?: 'asc' | 'desc';
   *   sort_by?: string;
   *   filters?: ObservationFilters;
   * }} [opts]
   */
  /**
   * Server-side chart rows (time_period, observation_value, series_key, plus series-dimension fields).
   *
   * @param {{ filters?: ObservationFilters; limit?: number }} [opts]
   */
  async function fetchChartData(opts = {}) {
    const params = noCacheParams({});
    if (opts.limit != null && opts.limit > 0) {
      params.limit = opts.limit;
    }
    mergeObservationFiltersIntoParams(params, opts.filters);
    const { data } = await axios.get(`${dataPath()}/chart`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Chart data request failed');
    const r = data.result ?? {};
    return {
      records: r.records ?? [],
      metadata: r.metadata ?? {},
    };
  }

  async function fetchObservations(opts = {}) {
    const offset = opts.offset ?? opts.skip ?? 0;
    const params = noCacheParams({
      limit: opts.limit ?? 25,
      offset,
      sort: opts.sort === 'desc' ? 'desc' : 'asc',
    });
    const sortBy = String(opts.sort_by ?? '').trim();
    if (sortBy) {
      params.sort_by = sortBy;
    }
    mergeObservationFiltersIntoParams(params, opts.filters);
    const { data } = await axios.get(`${dataPath()}`, { params });
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
   * @param {number|string} codelistId
   * @returns {Promise<{ id: number; idno?: string; items?: Array<{ code?: string; title?: string; label?: string; translations?: Record<string, string> }> } | null>}
   */
  async function fetchCodelistById(codelistId) {
    const id = String(codelistId).trim();
    if (!id) return null;
    const { data } = await axios.get(`${timeseriesApiRoot()}/codelists/item/${encodeURIComponent(id)}`, {
      params: noCacheParams(),
    });
    if (data.status !== 'success') throw new Error(data.message || 'Codelist request failed');
    return data.result?.codelist ?? null;
  }

  /**
   * Server-side paginated items for a single codelist. Paging matches data list:
   * `limit`, `offset` (or `skip`), response `total`, `found`, `limit`, `offset`.
   *
   * @param {{ id: number|string; offset?: number; limit?: number; search?: string; signal?: AbortSignal }} opts
   * @returns {Promise<{ codelistId: number; items: Array<{ id?: number|string; code?: string; title?: string; sort_order?: number }>; limit: number; offset: number; total: number; found: number }>}
   */
  async function fetchCodelistItemsPaged(opts) {
    const id = String(opts?.id ?? '').trim();
    if (!id) {
      return { codelistId: 0, items: [], limit: 0, offset: 0, total: 0, found: 0 };
    }
    const offset = Math.max(0, Number(opts?.offset) || 0);
    const limit = Number(opts?.limit);
    const params = {
      ...noCacheParams(),
      offset,
      ...(Number.isFinite(limit) && limit > 0 ? { limit } : {}),
    };
    const search = String(opts?.search ?? '').trim();
    if (search) params.search = search;
    const { data } = await axios.get(
      `${timeseriesApiRoot()}/codelists/item/${encodeURIComponent(id)}/items`,
      { params, signal: opts?.signal }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Codelist items request failed');
    const r = data.result ?? {};
    const items = Array.isArray(r.items) ? r.items : [];
    return {
      codelistId: Number(r.codelist_id ?? id) || 0,
      items,
      limit: typeof r.limit === 'number' ? r.limit : 0,
      offset: typeof r.offset === 'number' ? r.offset : offset,
      total: typeof r.total === 'number' ? r.total : 0,
      found: typeof r.found === 'number' ? r.found : items.length,
    };
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
    fetchFilterOptions: () => wrap(fetchFilterOptions()),
    fetchObservationCount: (filters) => wrap(fetchObservationCount(filters)),
    /** Does not toggle global `loading` (catalog page uses local chartLoading). */
    fetchChartData,
    fetchObservations: (opts) => wrap(fetchObservations(opts)),
    /** Does not toggle global `loading` (safe for parallel batch fetches). */
    fetchCodelistById,
    /** Server-paginated codelist items (use for large codelists). */
    fetchCodelistItemsPaged,
  };
}
