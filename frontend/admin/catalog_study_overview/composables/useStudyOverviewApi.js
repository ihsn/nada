import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Session-backed admin catalog API helpers for the study overview tab (numeric surveys.id via id_format=id).
 */
export function useStudyOverviewApi() {
  const { apiBaseUrl, config, csrfToken, csrfTokenName } = useAppConfig();

  function base() {
    return String(apiBaseUrl.value || '').replace(/\/+$/, '') + '/';
  }

  function sidPart() {
    const s = config.value?.studySid;
    if (s == null || s === '') throw new Error('studySid missing');
    return encodeURIComponent(String(s));
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    if (!tok) return {};
    return { [name]: tok };
  }

  async function fetchStudy() {
    const { data } = await axios.get(`${base()}${sidPart()}`, {
      params: { id_format: 'id', exclude_metadata: true },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_STUDY_FAILED');
    return data.dataset;
  }

  /**
   * GET /api/admin/catalog/{id}/folder-status?id_format=id
   * @returns {{ status: string, exists: boolean, folder_path: string|null, folder_path_full: string|null }}
   */
  async function fetchFolderStatus() {
    const { data } = await axios.get(`${base()}${sidPart()}/folder-status`, {
      params: { id_format: 'id' },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_FOLDER_STATUS_FAILED');
    return data;
  }

  /**
   * POST /api/admin/catalog/{id}/folder-status?id_format=id — create study folder on disk (and dirpath when missing).
   */
  async function postCreateStudyFolder() {
    const { data } = await axios.post(
      `${base()}${sidPart()}/folder-status`,
      {},
      {
        params: { id_format: 'id' },
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
        withCredentials: true,
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'CREATE_FOLDER_FAILED');
    return data;
  }

  async function fetchTags() {
    const { data } = await axios.get(`${base()}${sidPart()}/tags`, {
      params: { id_format: 'id' },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_TAGS_FAILED');
    return data.tags || [];
  }

  async function fetchCollections() {
    const { data } = await axios.get(`${base()}${sidPart()}/collections`, {
      params: { id_format: 'id', limit: 500 },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_COLLECTIONS_FAILED');
    return data.datasets || [];
  }

  /**
   * Facet collections for admin catalog filters (id, name, code, count).
   * GET /api/admin/catalog/filter_options?owner_repo=...
   */
  async function fetchFilterOptions(params = {}) {
    const { data } = await axios.get(`${base()}filter_options`, {
      params,
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_FILTER_OPTIONS_FAILED');
    return data;
  }

  /**
   * Replace linked collections for this study (excluding owner; server stores links in survey_repos).
   * POST /api/admin/catalog/{id}/collections?id_format=id
   */
  async function postLinkedCollectionsReplace(linkCollections, mode = 'replace') {
    const { data } = await axios.post(
      `${base()}${sidPart()}/collections`,
      {
        link_collections: Array.isArray(linkCollections) ? linkCollections : [],
        mode,
      },
      {
        params: { id_format: 'id' },
        headers: { 'Content-Type': 'application/json' },
        withCredentials: true,
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'SAVE_COLLECTIONS_FAILED');
    return data;
  }

  async function fetchDataClassifications() {
    const url = config.value?.dataClassificationsApiUrl;
    if (!url) throw new Error('dataClassificationsApiUrl missing');
    const { data } = await axios.get(url, { withCredentials: true });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_CLASSIFICATIONS_FAILED');
    return data;
  }

  async function fetchDataAccessOptions(classificationCode) {
    const code = String(classificationCode || '').trim();
    if (!code) throw new Error('classification required');
    const { data } = await axios.get(`${base()}data-access-options`, {
      params: { classification: code },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_DA_OPTIONS_FAILED');
    return data.codelist || [];
  }

  async function postOptions(payload) {
    const { data } = await axios.post(`${base()}options/${sidPart()}`, payload, {
      params: { id_format: 'id' },
      headers: { 'Content-Type': 'application/json' },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'SAVE_OPTIONS_FAILED');
    return data;
  }

  async function postDoi(doi) {
    const body = { doi: doi == null || doi === '' ? '' : String(doi) };
    const { data } = await axios.post(`${base()}${sidPart()}/doi`, body, {
      params: { id_format: 'id' },
      headers: { 'Content-Type': 'application/json' },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'SAVE_DOI_FAILED');
    return data;
  }

  async function postTags(addTags) {
    const { data } = await axios.post(
      `${base()}${sidPart()}/tags`,
      { tags: addTags },
      {
        params: { id_format: 'id' },
        headers: { 'Content-Type': 'application/json' },
        withCredentials: true,
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'SAVE_TAGS_FAILED');
    return data.tags || [];
  }

  async function deleteTagRow(tagRow) {
    const id = tagRow?.id;
    if (id != null && id !== '') {
      const { data } = await axios.delete(`${base()}${sidPart()}/tags`, {
        params: { id_format: 'id', id },
        withCredentials: true,
      });
      if (data.status !== 'success') throw new Error(data.message || 'DELETE_TAG_FAILED');
      return data.tags || [];
    }
    const tag = tagRow?.tag;
    if (!tag) throw new Error('TAG_DELETE_EMPTY');
    const { data } = await axios.delete(`${base()}${sidPart()}/tags`, {
      params: { id_format: 'id', tag },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'DELETE_TAG_FAILED');
    return data.tags || [];
  }

  async function postAliases(aliases) {
    const { data } = await axios.post(
      `${base()}${sidPart()}/aliases`,
      { aliases },
      {
        params: { id_format: 'id' },
        headers: { 'Content-Type': 'application/json' },
        withCredentials: true,
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'SAVE_ALIASES_FAILED');
    return data.aliases || [];
  }

  async function deleteAliasRow(row) {
    const id = row?.id;
    const alt = row?.alternate_id;
    const params = { id_format: 'id' };
    if (id != null && id !== '') params.id = id;
    if (alt) params.alternate_id = alt;
    const { data } = await axios.delete(`${base()}${sidPart()}/aliases`, {
      params,
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'DELETE_ALIAS_FAILED');
    return data.aliases || [];
  }

  return {
    fetchStudy,
    fetchFolderStatus,
    postCreateStudyFolder,
    fetchTags,
    fetchCollections,
    fetchFilterOptions,
    postLinkedCollectionsReplace,
    fetchDataClassifications,
    fetchDataAccessOptions,
    postOptions,
    postDoi,
    postTags,
    deleteTagRow,
    postAliases,
    deleteAliasRow,
  };
}
