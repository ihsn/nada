import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Composable for admin catalog API: list/search via GET …/api/admin/catalog and filter_options.
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
      const base = (apiBaseUrl.value || '').replace(/\/+$/, '');
      const { data } = await axios.get(base, { params });
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

  /**
   * @param {Object} params
   * @param {string} [params.owner_repo]
   * @param {number} [params.page]
   * @param {number} [params.ps]
   */
  async function fetchBatchStudiesPage(params = {}) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.get(`${base}batch_studies`, { params, withCredentials: true });
    if (data.status !== 'success') throw new Error(data.message || 'batch_studies failed');
    return data.result;
  }

  /**
   * Load all compact study rows for batch UIs (paginates until complete).
   */
  async function fetchAllBatchStudies(ownerRepo, opts = {}) {
    const ps = opts.ps ?? 500;
    const all = [];
    let page = 1;
    let total = null;
    while (true) {
      const params = { page, ps, dataset_types: 'survey' };
      if (ownerRepo && String(ownerRepo).trim() !== '') {
        params.owner_repo = ownerRepo;
      }
      const result = await fetchBatchStudiesPage(params);
      all.push(...(result.rows || []));
      if (!result.rows?.length || all.length >= result.total) break;
      page += 1;
    }
    return all;
  }

  /**
   * @param {string} repositoryid - ACL context (e.g. central)
   */
  async function fetchBatchImportFiles(repositoryid) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.get(`${base}batch_import_files`, {
      params: { repositoryid: repositoryid || 'central' },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'batch_import_files failed');
    return data;
  }

  /**
   * POST api/admin/catalog/batch_import (session auth; JSON body).
   * @param {AbortSignal} [reqConfig.signal] - cancel in-flight request
   */
  async function postBatchImportFile(fileIdB64, repositoryid, overwrite, reqConfig = {}) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.post(
      `${base}batch_import`,
      {
        id: fileIdB64,
        repositoryid,
        overwrite: !!overwrite,
      },
      {
        withCredentials: true,
        headers: { 'Content-Type': 'application/json' },
        signal: reqConfig.signal,
      }
    );
    return data;
  }

  /**
   * POST api/admin/catalog/batch_refresh/{sid}?id_format=id
   * @param {AbortSignal} [reqConfig.signal]
   */
  async function postRefreshStudy(sid, reqConfig = {}) {
    const base = apiBaseUrl.value || '';
    const url = `${base}batch_refresh/${encodeURIComponent(String(sid))}`;
    const { data } = await axios.post(url, {}, {
      params: { id_format: 'id' },
      withCredentials: true,
      headers: { 'Content-Type': 'application/json' },
      signal: reqConfig.signal,
    });
    return data;
  }

  /**
   * POST api/admin/catalog/batch_generate_ddi/{sid}?id_format=id
   * @param {AbortSignal} [reqConfig.signal]
   */
  async function fetchGenerateDdi(sid, reqConfig = {}) {
    const base = apiBaseUrl.value || '';
    const url = `${base}batch_generate_ddi/${encodeURIComponent(String(sid))}`;
    const { data } = await axios.post(url, {}, {
      params: { id_format: 'id' },
      withCredentials: true,
      headers: { 'Content-Type': 'application/json' },
      signal: reqConfig.signal,
    });
    return data;
  }

  return {
    loading,
    error,
    search,
    fetchFilterOptions,
    updateOptions,
    deleteStudy,
    fetchBatchStudiesPage,
    fetchAllBatchStudies,
    fetchBatchImportFiles,
    postBatchImportFile,
    postRefreshStudy,
    fetchGenerateDdi,
  };
}
