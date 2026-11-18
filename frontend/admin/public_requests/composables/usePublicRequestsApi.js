import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function usePublicRequestsApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  function baseUrl() {
    return String(apiBaseUrl.value || '').replace(/\/?$/, '/');
  }

  /**
   * @param {Record<string, string|number|undefined>} params
   */
  async function search(params) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${baseUrl()}search`, { params });
      if (data.status !== 'success') throw new Error(data.message || 'Search failed');
      return data.result;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /**
   * @param {number|string} id
   */
  async function fetchDetail(id) {
    const { data } = await axios.get(`${baseUrl()}${encodeURIComponent(String(id))}`);
    if (data.status !== 'success') throw new Error(data.message || 'Not found');
    return data.request;
  }

  /**
   * @param {Record<string, string|number|undefined>} params
   */
  function exportUrl(params = {}) {
    const qs = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && String(v) !== '') qs.set(k, String(v));
    });
    const q = qs.toString();
    return `${baseUrl()}export${q ? `?${q}` : ''}`;
  }

  return { loading, error, search, fetchDetail, exportUrl };
}
