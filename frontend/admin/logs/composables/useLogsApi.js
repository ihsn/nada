import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Site logs, row counts and cleanup for both tables: api/admin/db_logs (api_logs/* for the API log table).
 * API logs list: api/admin/api_logs.
 */
export function useLogsApi() {
  const { config, csrfToken, csrfTokenName } = useAppConfig();

  function dbLogs(path = '') {
    return `${String(config.value?.dbLogsApiUrl || '').replace(/\/?$/, '/')}${path}`;
  }

  function apiLogs(path = '') {
    return `${String(config.value?.apiLogsApiUrl || '').replace(/\/?$/, '/')}${path}`;
  }

  function csrfHeaders() {
    const tok = csrfToken.value || '';
    return tok ? { [csrfTokenName.value || 'ncsrf']: tok } : {};
  }

  function apiError(e, fallback) {
    return e?.response?.data?.message || e?.message || fallback;
  }

  async function getJson(url, params, fallback) {
    try {
      const { data } = await axios.get(url, { params });
      if (data.status !== 'success') throw new Error(data.message || fallback);
      return data;
    } catch (e) {
      const err = new Error(apiError(e, fallback), { cause: e });
      err.status = e?.response?.status;
      throw err;
    }
  }

  /** @param {'sitelogs'|'api_logs'} table */
  function cleanupPrefix(table) {
    return table === 'api_logs' ? 'api_logs/' : '';
  }

  return {
    searchSiteLogs: (params) => getJson(dbLogs(), params, 'Failed to load site logs'),
    searchApiLogs: (params) => getJson(apiLogs(), params, 'Failed to load API logs'),
    rowCounts: async () => (await getJson(dbLogs('row_counts'), null, 'Failed to load row counts')).data,

    cleanupStats: async (table) =>
      (await getJson(dbLogs(`${cleanupPrefix(table)}stats`), null, 'Failed to load statistics')).data,
    cleanupFiles: async (table) =>
      (await getJson(dbLogs(`${cleanupPrefix(table)}files`), null, 'Failed to load files')).data || [],

    async cleanupChunk(table, timeLimit) {
      try {
        const { data } = await axios.post(
          dbLogs(`${cleanupPrefix(table)}cleanup/chunk`),
          { time_limit: timeLimit },
          { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
        );
        if (data.status === 'failed') throw new Error(data.message || 'Cleanup failed');
        return data;
      } catch (e) {
        throw new Error(apiError(e, 'Cleanup failed'), { cause: e });
      }
    },
  };
}
