import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Admin study external resources — api/admin/resources/{studyRef}/resources/…
 * Uses study IDNO in the URL when present; otherwise numeric surveys.id + id_format=id.
 */
export function useStudyResourcesApi() {
  const { csrfToken, csrfTokenName, config } = useAppConfig();

  function resourcesPrefix() {
    const u = config.value?.resourcesApiBase;
    if (u) return String(u).replace(/\/+$/, '') + '/';
    const site = String(config.value?.siteUrl || '').replace(/\/+$/, '');
    return `${site}/api/admin/resources/`;
  }

  /** Path segment after …/resources/ — encoded IDNO or numeric sid */
  function studyRefSegment() {
    const idno = config.value?.studyIdno;
    if (idno != null && String(idno).trim() !== '') {
      return encodeURIComponent(String(idno).trim());
    }
    const sid = config.value?.studySid;
    if (sid == null || sid === '') throw new Error('studySid missing');
    return encodeURIComponent(String(sid));
  }

  /** Query params required so get_sid_from_idno resolves numeric URL segment */
  function studyRefParams() {
    const idno = config.value?.studyIdno;
    if (idno != null && String(idno).trim() !== '') return {};
    return { id_format: 'id' };
  }

  function csrfHeaders(extra = {}) {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    if (!tok) return { ...extra };
    return {
      [name]: tok,
      'X-CSRF-TOKEN': tok,
      'X-Requested-With': 'XMLHttpRequest',
      ...extra,
    };
  }

  function apiError(e, fallback) {
    const d = e?.response?.data;
    if (d == null) return e?.message || fallback;
    if (typeof d === 'string') return d;
    if (d.errors && typeof d.errors === 'object' && !Array.isArray(d.errors)) {
      return Object.values(d.errors).flat().join(' ') || d.message || fallback;
    }
    return d.message || d.errors || e?.message || fallback;
  }

  /**
   * @param {{ sort_by?: string, sort_order?: string }} [opts]
   */
  async function fetchResources(opts = {}) {
    const params = {
      ...studyRefParams(),
      ...(opts.sort_by ? { sort_by: opts.sort_by } : {}),
      ...(opts.sort_order ? { sort_order: opts.sort_order } : {}),
    };
    const { data } = await axios.get(`${resourcesPrefix()}${studyRefSegment()}/resources`, {
      params,
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.errors || data.message || 'LOAD_RESOURCES_FAILED');
    return data;
  }

  /**
   * @param {number|string} resourceId
   */
  async function getResource(resourceId) {
    const rid = encodeURIComponent(String(resourceId));
    const { data } = await axios.get(
      `${resourcesPrefix()}${studyRefSegment()}/resources/${rid}`,
      { params: studyRefParams(), withCredentials: true }
    );
    if (data.status !== 'success') throw new Error(data.errors || data.message || 'LOAD_RESOURCE_FAILED');
    const list = Array.isArray(data.resources) ? data.resources : [];
    return list[0] || null;
  }

  /**
   * @param {Record<string, any>} payload
   */
  async function createResource(payload) {
    const { data } = await axios.post(
      `${resourcesPrefix()}${studyRefSegment()}/resources`,
      payload,
      {
        params: studyRefParams(),
        withCredentials: true,
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
      }
    );
    if (data.status !== 'success') throw new Error(apiError({ response: { data } }, 'CREATE_FAILED'));
    return data;
  }

  /**
   * @param {number|string} resourceId
   * @param {Record<string, any>} payload
   */
  async function updateResource(resourceId, payload) {
    const rid = encodeURIComponent(String(resourceId));
    const { data } = await axios.post(
      `${resourcesPrefix()}${studyRefSegment()}/resources/update/${rid}`,
      payload,
      {
        params: studyRefParams(),
        withCredentials: true,
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
      }
    );
    if (data.status !== 'success') throw new Error(apiError({ response: { data } }, 'UPDATE_FAILED'));
    return data;
  }

  /**
   * @param {number|string} resourceId
   */
  async function deleteResource(resourceId) {
    const rid = encodeURIComponent(String(resourceId));
    const { data } = await axios.post(
      `${resourcesPrefix()}${studyRefSegment()}/resources/delete/${rid}`,
      {},
      {
        params: studyRefParams(),
        withCredentials: true,
        headers: csrfHeaders(),
      }
    );
    if (data.status !== 'success') throw new Error(data.message || data.errors || 'DELETE_FAILED');
    return data;
  }

  /**
   * @param {File} file
   */
  async function importRdf(file) {
    const body = new FormData();
    body.append('file', file);
    const { data } = await axios.post(
      `${resourcesPrefix()}${studyRefSegment()}/resources/import_rdf`,
      body,
      {
        params: studyRefParams(),
        withCredentials: true,
        headers: csrfHeaders(),
      }
    );
    if (data.status !== 'success') throw new Error(apiError({ response: { data } }, 'IMPORT_FAILED'));
    return data;
  }

  async function fixLinks() {
    const { data } = await axios.post(
      `${resourcesPrefix()}${studyRefSegment()}/resources/fix_links`,
      {},
      {
        params: studyRefParams(),
        withCredentials: true,
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
      }
    );
    return data;
  }

  return {
    fetchResources,
    getResource,
    createResource,
    updateResource,
    deleteResource,
    importRdf,
    fixLinks,
    studyRefSegment,
    studyRefParams,
  };
}
