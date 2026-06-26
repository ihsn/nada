import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useFacetsApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error   = ref(null);

  function base() {
    return apiBaseUrl.value || '';
  }

  async function listFacets() {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.get(base());
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load facets');
      return data.facets || [];
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function getFacet(name) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.get(`${base()}get/${encodeURIComponent(name)}`);
      if (data.status !== 'success') throw new Error(data.message || 'Facet not found');
      return data.facet;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function saveFacet(payload) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.post(base(), payload);
      if (data.status !== 'success') throw new Error(data.message || 'Save failed');
      return data;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteFacet(id) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.post(`${base()}delete/${id}`);
      if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
      return true;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function getOrdering() {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.get(`${base()}ordering`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load ordering');
      return { ordering: data.ordering, facets: data.facets };
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function saveOrdering(payload) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.post(`${base()}reorder/`, payload, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Reorder failed');
      return true;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function getTerms(id) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.get(`${base()}terms/${id}`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load terms');
      return { facet: data.facet, terms: data.terms };
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function getStats() {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.get(`${base()}stats`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load stats');
      return { stats: data.stats, studiesCount: data.studies_count };
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function reindex(startRow = 0, limit = 30, signal = null) {
    const { data } = await axios.get(`${base()}reindex/${startRow}/${limit}`, { signal });
    return data.result;
  }

  async function clearIndex() {
    await axios.get(`${base()}clear_index/`);
  }

  return {
    loading, error,
    listFacets, getFacet, saveFacet, deleteFacet,
    getOrdering, saveOrdering,
    getTerms,
    getStats, reindex, clearIndex,
  };
}
