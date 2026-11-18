import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** Admin data-access whitelist against api/admin/dataaccess_whitelist. */
export function useWhitelistApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName } = useAppConfig();

  function base() {
    return String(apiBaseUrl.value || '').replace(/\/$/, '');
  }

  function csrfHeaders() {
    const tok = csrfToken.value || '';
    return tok ? { [csrfTokenName.value || 'ncsrf']: tok } : {};
  }

  function apiError(e, fallback) {
    return e?.response?.data?.message || e?.message || fallback;
  }

  async function load() {
    try {
      const { data } = await axios.get(base());
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load whitelist');
      return {
        rows: data.rows || [],
        collections: data.collections || [],
      };
    } catch (e) {
      throw new Error(apiError(e, 'Failed to load whitelist'), { cause: e });
    }
  }

  async function create({ email, repositoryId }) {
    try {
      const { data } = await axios.post(
        base(),
        { email, repository_id: repositoryId },
        { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Create failed');
      return data.result;
    } catch (e) {
      throw new Error(apiError(e, 'Create failed'), { cause: e });
    }
  }

  async function remove(id) {
    try {
      const { data } = await axios.post(`${base()}/${encodeURIComponent(id)}/delete`, null, {
        headers: csrfHeaders(),
      });
      if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
      return true;
    } catch (e) {
      throw new Error(apiError(e, 'Delete failed'), { cause: e });
    }
  }

  return { load, create, remove };
}
