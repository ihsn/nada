import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useStudyCitationsApi() {
  const { config, csrfToken, csrfTokenName } = useAppConfig();

  function catalogBase() {
    const base = String(config.value?.apiBaseUrl || '').replace(/\/+$/, '');
    return `${base}/`;
  }

  function studyRef() {
    const idno = config.value?.studyIdno;
    if (idno != null && String(idno).trim() !== '') {
      return encodeURIComponent(String(idno).trim());
    }
    const sid = config.value?.studySid;
    if (sid == null || sid === '') throw new Error('studySid missing');
    return encodeURIComponent(String(sid));
  }

  function studyParams() {
    const idno = config.value?.studyIdno;
    if (idno != null && String(idno).trim() !== '') return {};
    return { id_format: 'id' };
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    if (!tok) return {};
    return {
      [name]: tok,
      'X-CSRF-TOKEN': tok,
      'X-Requested-With': 'XMLHttpRequest',
    };
  }

  async function fetchAttached(sortBy = 'title', sortOrder = 'asc') {
    const { data } = await axios.get(`${catalogBase()}${studyRef()}/citations`, {
      params: { ...studyParams(), sort_by: sortBy, sort_order: sortOrder },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_ATTACHED_FAILED');
    return data;
  }

  async function searchCitations({ keywords = '', offset = 0, limit = 30, sortBy = 'changed', sortOrder = 'desc' } = {}) {
    const { data } = await axios.get(`${catalogBase()}${studyRef()}/citations/search`, {
      params: {
        ...studyParams(),
        keywords,
        offset,
        limit,
        sort_by: sortBy,
        sort_order: sortOrder,
      },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'SEARCH_FAILED');
    return data;
  }

  async function attachCitation(citationId) {
    const { data } = await axios.post(
      `${catalogBase()}${studyRef()}/citations/${encodeURIComponent(String(citationId))}`,
      {},
      {
        params: studyParams(),
        withCredentials: true,
        headers: csrfHeaders(),
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'ATTACH_FAILED');
    return data;
  }

  async function detachCitation(citationId) {
    const { data } = await axios.delete(
      `${catalogBase()}${studyRef()}/citations/${encodeURIComponent(String(citationId))}`,
      {
        params: studyParams(),
        withCredentials: true,
        headers: csrfHeaders(),
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'DETACH_FAILED');
    return data;
  }

  return { fetchAttached, searchCitations, attachCitation, detachCitation };
}

