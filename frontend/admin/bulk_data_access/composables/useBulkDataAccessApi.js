import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useBulkDataAccessApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName } = useAppConfig();
  const loading = ref(false);

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    return tok ? { [name]: tok } : {};
  }

  function jsonHeaders() {
    return { 'Content-Type': 'application/json', ...csrfHeaders() };
  }

  async function fetchCollections() {
    loading.value = true;
    try {
      const base = apiBaseUrl.value || '';
      const { data } = await axios.get(`${base}collections`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed');
      return data.result.rows;
    } finally {
      loading.value = false;
    }
  }

  async function fetchCollection(id) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.get(`${base}collection/${encodeURIComponent(String(id))}`);
    if (data.status !== 'success') throw new Error(data.message || 'Not found');
    return data.result.collection;
  }

  async function createCollection(payload) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.post(`${base}collection`, payload, { headers: jsonHeaders() });
    if (data.status !== 'success') throw new Error(data.message || 'Create failed');
    return data.result.collection;
  }

  /** POST alias for PATCH */
  async function updateCollection(id, payload) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.post(
      `${base}collection_update/${encodeURIComponent(String(id))}`,
      payload,
      { headers: jsonHeaders() }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Update failed');
    return data.result.collection;
  }

  /** POST alias for DELETE */
  async function deleteCollections(ids) {
    loading.value = true;
    try {
      const base = apiBaseUrl.value || '';
      const { data } = await axios.post(`${base}collections_delete`, { ids }, { headers: jsonHeaders() });
      if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
      return data.result.deleted_ids;
    } finally {
      loading.value = false;
    }
  }

  async function searchStudies(collectionId, params) {
    loading.value = true;
    try {
      const base = apiBaseUrl.value || '';
      const { data } = await axios.get(
        `${base}studies_search/${encodeURIComponent(String(collectionId))}`,
        { params }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Search failed');
      return data.result;
    } finally {
      loading.value = false;
    }
  }

  async function setStudyLink(collectionId, sid, linked) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.post(
      `${base}study_link`,
      { collection_id: collectionId, sid, linked: linked ? 1 : 0 },
      { headers: jsonHeaders() }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Request failed');
    return data.result;
  }

  return {
    loading,
    fetchCollections,
    fetchCollection,
    createCollection,
    updateCollection,
    deleteCollections,
    searchStudies,
    setStudyLink,
  };
}
