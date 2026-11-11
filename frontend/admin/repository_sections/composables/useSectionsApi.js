import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** Admin repository sections against api/admin/repository_sections. */
export function useSectionsApi() {
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

  async function list(params = {}) {
    try {
      const { data } = await axios.get(base(), { params });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load sections');
      return data.sections || [];
    } catch (e) {
      throw new Error(apiError(e, 'Failed to load sections'), { cause: e });
    }
  }

  async function get(id) {
    try {
      const { data } = await axios.get(`${base()}/${encodeURIComponent(id)}`);
      if (data.status !== 'success') throw new Error(data.message || 'Section not found');
      return data.section;
    } catch (e) {
      throw new Error(apiError(e, 'Section not found'), { cause: e });
    }
  }

  async function create(payload) {
    try {
      const { data } = await axios.post(base(), payload, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Create failed');
      return data.section;
    } catch (e) {
      throw new Error(apiError(e, 'Create failed'), { cause: e });
    }
  }

  async function update(id, payload) {
    try {
      const { data } = await axios.post(`${base()}/${encodeURIComponent(id)}`, payload, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Update failed');
      return data.section;
    } catch (e) {
      throw new Error(apiError(e, 'Update failed'), { cause: e });
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

  async function batchDelete(ids) {
    try {
      const { data } = await axios.post(
        `${base()}/batch_delete`,
        { ids },
        { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
      return true;
    } catch (e) {
      throw new Error(apiError(e, 'Delete failed'), { cause: e });
    }
  }

  return { list, get, create, update, remove, batchDelete };
}
