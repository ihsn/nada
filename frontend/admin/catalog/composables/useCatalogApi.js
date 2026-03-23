import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Composable for admin catalog API: search and filter_options.
 * Uses apiBaseUrl from app config; returns reactive state and fetch helpers.
 */
export function useCatalogApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  async function search(params) {
    loading.value = true;
    error.value = null;
    try {
      const base = apiBaseUrl.value || '';
      const { data } = await axios.get(`${base}search`, { params });
      if (data.status !== 'success') throw new Error(data.message || 'Search failed');
      return data.result;
    } catch (e) {
      error.value = e;
      console.error('Catalog search error:', e);
      return { rows: [], total: 0, page: 1, page_size: 15 };
    } finally {
      loading.value = false;
    }
  }

  /**
   * @param {Object} [params] - optional query params, e.g. { owner_repo: 'repo-id' }
   */
  async function fetchFilterOptions(params = {}) {
    error.value = null;
    try {
      const base = apiBaseUrl.value || '';
      const { data } = await axios.get(`${base}filter_options`, { params });
      return {
        countries: data.countries || [],
        collections: data.collections || [],
        tags: data.tags || [],
        dataAccess: data.data_access || [],
        dataTypes: data.dataset_types || [],
      };
    } catch (e) {
      error.value = e;
      console.error('Filter options error:', e);
      return { countries: [], collections: [], tags: [], dataAccess: [], dataTypes: [] };
    }
  }

  /**
   * Update study options (e.g. published) via POST api/admin/catalog/options/{id}?id_format=id
   * @param {number|string} id - study id (surveys.id)
   * @param {Object} payload - e.g. { published: 0|1 }
   */
  async function updateOptions(id, payload) {
    if (id == null || id === '') throw new Error('id required');
    const base = apiBaseUrl.value || '';
    const url = `${base}options/${encodeURIComponent(String(id))}`;
    const { data } = await axios.post(url, payload, {
      params: { id_format: 'id' },
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Update failed');
    return data;
  }

  /**
   * Delete study by id via POST api/admin/catalog/delete/{id}?id_format=id
   * @param {number|string} id - study id (surveys.id)
   */
  async function deleteStudy(id) {
    if (id == null || id === '') throw new Error('id required');
    const base = apiBaseUrl.value || '';
    const url = `${base}delete/${encodeURIComponent(String(id))}`;
    const { data } = await axios.post(url, {}, {
      params: { id_format: 'id' },
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
    return data;
  }

  return { loading, error, search, fetchFilterOptions, updateOptions, deleteStudy };
}
