import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function apiErrorMessage(err, fallback = 'REQUEST_FAILED') {
  const body = err?.response?.data;
  if (body?.message) return String(body.message);
  if (err?.message) return String(err.message);
  return fallback;
}

export function useAnalyticsApi() {
  const { siteUrl, csrfToken, csrfTokenName } = useAppConfig();

  function apiPrefix() {
    const base = String(siteUrl.value || '').replace(/\/+$/, '');
    return `${base}/api/admin/analytics`;
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    if (!tok) return {};
    return { [name]: tok };
  }

  function buildUrl(path, params = {}) {
    const qs = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        qs.set(key, String(value));
      }
    });
    const query = qs.toString();
    const base = `${apiPrefix()}${path}`;
    return query ? `${base}?${query}` : base;
  }

  async function getJson(path, params = {}) {
    try {
      const { data } = await axios.get(`${apiPrefix()}${path}`, {
        params,
        withCredentials: true,
      });
      if (data?.status !== 'success') {
        throw new Error(data?.message || 'REQUEST_FAILED');
      }
      return data;
    } catch (err) {
      throw new Error(apiErrorMessage(err));
    }
  }

  async function postJson(path, body = {}) {
    try {
      const { data } = await axios.post(`${apiPrefix()}${path}`, body, {
        withCredentials: true,
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      });
      if (data?.status === 'error' || data?.error) {
        throw new Error(data?.message || 'REQUEST_FAILED');
      }
      return data;
    } catch (err) {
      if (err instanceof Error && err.message && !err.response) {
        throw err;
      }
      throw new Error(apiErrorMessage(err));
    }
  }

  return {
    apiPrefix,
    buildUrl,
    fetchOverview: (params) => getJson('/overview', params),
    fetchMeta: () => getJson('/meta'),
    fetchDailyTotals: (params) => getJson('/daily/totals', params),
    fetchMonthlyTotals: (params) => getJson('/monthly/totals', params),
    fetchRawPageviews: (params) => getJson('/raw/pageviews', params),
    fetchRawDownloads: (params) => getJson('/raw/downloads', params),
    fetchDailyStudies: (params) => getJson('/daily/studies', params),
    fetchDailyFiles: (params) => getJson('/daily/files', params),
    fetchMonthlyStudies: (params) => getJson('/monthly/studies', params),
    fetchMonthlyFiles: (params) => getJson('/monthly/files', params),
    fetchAggregationStatus: () => getJson('/aggregate/status'),
    runAggregationStep: () => postJson('/aggregate/run'),
    stopAggregation: () => postJson('/aggregate/stop'),
    exportUrl: (path, params) => buildUrl(path, params),
  };
}
