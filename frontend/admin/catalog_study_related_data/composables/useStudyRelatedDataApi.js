import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useStudyRelatedDataApi() {
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

  async function fetchAll() {
    const { data } = await axios.get(`${catalogBase()}${studyRef()}/related-studies`, {
      params: { ...studyParams() },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_FAILED');
    return data;
  }

  async function searchStudies({ field = 'title', keywords = '', offset = 0, limit = 30 } = {}) {
    const { data } = await axios.get(`${catalogBase()}${studyRef()}/related-studies/search`, {
      params: {
        ...studyParams(),
        field,
        keywords,
        offset,
        limit,
      },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'SEARCH_FAILED');
    return data;
  }

  async function attachStudy(relatedSid, relationshipId) {
    const { data } = await axios.post(
      `${catalogBase()}${studyRef()}/related-studies`,
      { related_sid: relatedSid, relationship_id: relationshipId },
      {
        params: studyParams(),
        withCredentials: true,
        headers: { ...csrfHeaders(), 'Content-Type': 'application/json' },
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'ATTACH_FAILED');
    return data;
  }

  async function updateRelationship(relatedSid, relationshipId) {
    const { data } = await axios.patch(
      `${catalogBase()}${studyRef()}/related-studies/${encodeURIComponent(String(relatedSid))}`,
      { relationship_id: relationshipId },
      {
        params: studyParams(),
        withCredentials: true,
        headers: { ...csrfHeaders(), 'Content-Type': 'application/json' },
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'UPDATE_FAILED');
    return data;
  }

  async function detachStudy(relatedSid) {
    const { data } = await axios.delete(
      `${catalogBase()}${studyRef()}/related-studies/${encodeURIComponent(String(relatedSid))}`,
      {
        params: studyParams(),
        withCredentials: true,
        headers: csrfHeaders(),
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'DETACH_FAILED');
    return data;
  }

  return {
    fetchAll,
    searchStudies,
    attachStudy,
    updateRelationship,
    detachStudy,
  };
}
