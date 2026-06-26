import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** @param {unknown} e */
export function isCollectionsAccessDenied(e) {
  if (e?.response?.status === 403) return true;
  const msg = e?.response?.data?.message ?? e?.message ?? '';
  return msg === 'ACCESS-DENIED' || msg === 'ACCESS_DENIED';
}

/**
 * Composable for admin collections API (CRUD).
 * Maps to application/controllers/api/admin/Collections.php
 */
export function useCollectionsApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  function base() {
    return apiBaseUrl.value || '';
  }

  /** Build FormData when a thumbnail file is present, otherwise send JSON */
  function buildPayload(payload) {
    if (payload.thumbnailFile instanceof File) {
      const fd = new FormData();
      fd.append('thumbnail', payload.thumbnailFile);
      const { thumbnailFile, ...rest } = payload;
      Object.entries(rest).forEach(([k, v]) => v != null && fd.append(k, v));
      return { data: fd, headers: {} }; // let browser set Content-Type with boundary
    }
    return { data: payload, headers: { 'Content-Type': 'application/json' } };
  }

  /** GET {apiBase} — list all collections, optional ?published=0|1 */
  async function listCollections(params = {}) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(base(), { params });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load collections');
      return data.collections || [];
    } catch (e) {
      error.value = e;
      console.error('Collections list error:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** GET {apiBase}history/{repo_id}?page=1&ps=25 — paginated studies in a collection */
  async function getHistory(repositoryId, params = {}) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${base()}history/${encodeURIComponent(repositoryId)}`, { params });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load history');
      return data;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** GET {apiBase}sections — list repository sections for dropdown */
  async function getSections() {
    try {
      const { data } = await axios.get(`${base()}sections`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load sections');
      return data.sections || [];
    } catch (e) {
      console.error('Sections error:', e);
      return [];
    }
  }

  /** GET {apiBase}{repo_id} — fetch a single collection by repositoryid */
  async function getCollection(repositoryId) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${base()}${encodeURIComponent(repositoryId)}`);
      if (data.status !== 'success') throw new Error(data.message || 'Collection not found');
      return data.collection;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** POST {apiBase} — create a new collection */
  async function createCollection(payload) {
    loading.value = true;
    error.value = null;
    try {
      const { data: body, headers } = buildPayload(payload);
      const { data } = await axios.post(base(), body, { headers });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to create collection');
      return data.collection;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** POST {apiBase}update — update an existing collection (repositoryid required in payload) */
  async function updateCollection(payload) {
    loading.value = true;
    error.value = null;
    try {
      const { data: body, headers } = buildPayload(payload);
      const { data } = await axios.post(`${base()}update`, body, { headers });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to update collection');
      return data.collection;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** POST {apiBase}rename — rename a collection's repositoryid */
  async function renameCollection(oldId, newId) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.post(`${base()}rename`, {
        old_repositoryid: oldId,
        new_repositoryid: newId,
      }, { headers: { 'Content-Type': 'application/json' } });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to rename collection');
      return data.collection;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /** DELETE {apiBase}delete/{repo_id} — delete a collection */
  async function deleteCollection(repositoryId) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.delete(`${base()}delete/${encodeURIComponent(repositoryId)}`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to delete collection');
      return true;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  return { loading, error, listCollections, getSections, getCollection, getHistory, createCollection, updateCollection, renameCollection, deleteCollection };
}
