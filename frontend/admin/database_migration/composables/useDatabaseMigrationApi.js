import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * api/admin/database_migration
 */
export function useDatabaseMigrationApi() {
  const { config, csrfToken, csrfTokenName } = useAppConfig();

  function base(path = '') {
    return String(config.value.databaseMigrationApiUrl || '').replace(/\/?$/, '/') + path;
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    return tok ? { [name]: tok } : {};
  }

  function apiError(e, fallback) {
    return e?.response?.data?.message || e?.message || fallback;
  }

  async function get(fallback) {
    try {
      const { data } = await axios.get(base());
      if (data.status !== 'success') throw new Error(data.message || fallback);
      return data;
    } catch (e) {
      throw new Error(apiError(e, fallback), { cause: e });
    }
  }

  async function post(path, payload, fallback) {
    try {
      const { data } = await axios.post(base(path), payload ?? null, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      });
      if (data.status !== 'success') {
        const err = new Error(data.message || fallback);
        err.payload = data;
        throw err;
      }
      return data;
    } catch (e) {
      if (e.payload) throw e;
      const err = new Error(apiError(e, fallback));
      err.payload = e?.response?.data || null;
      throw err;
    }
  }

  return {
    getStatus: () => get('Failed to load migration status'),
    run: (version) => post('run', { version }, 'Migration failed'),
    markApplied: (version) => post('mark_applied', { version }, 'Mark applied failed'),
    unmark: (version) => post('unmark', { version }, 'Unmark failed'),
    setVersion: (version) => post('set_version', { version }, 'Set version failed'),
  };
}
