import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useCatalogStudySidebarApi() {
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

  async function fetchWarnings() {
    const { data } = await axios.get(`${catalogBase()}${studyRef()}/warnings`, {
      params: { ...studyParams() },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'WARNINGS_FAILED');
    return data;
  }

  async function setPublished(published) {
    const { data } = await axios.post(
      `${catalogBase()}options/${studyRef()}`,
      { published: published ? 1 : 0 },
      {
        params: studyParams(),
        withCredentials: true,
        headers: { ...csrfHeaders(), 'Content-Type': 'application/json' },
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'OPTIONS_FAILED');
    return data;
  }

  async function deleteStudy() {
    const { data } = await axios.post(
      `${catalogBase()}delete/${studyRef()}`,
      {},
      {
        params: studyParams(),
        withCredentials: true,
        headers: { ...csrfHeaders(), 'Content-Type': 'application/json' },
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'DELETE_FAILED');
    return data;
  }

  async function uploadThumbnail(file) {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await axios.post(`${catalogBase()}${studyRef()}/thumbnail`, fd, {
      params: studyParams(),
      withCredentials: true,
      headers: csrfHeaders(),
    });
    if (data.status !== 'success') throw new Error(data.message || 'UPLOAD_FAILED');
    return data;
  }

  /** POST alias for DELETE …/{idno}/thumbnail (same server behaviour as axios.delete thumbnail). */
  async function removeThumbnail() {
    const { data } = await axios.post(
      `${catalogBase()}thumbnail_delete/${studyRef()}`,
      {},
      {
        params: studyParams(),
        withCredentials: true,
        headers: { ...csrfHeaders(), 'Content-Type': 'application/json' },
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'REMOVE_THUMB_FAILED');
    return data;
  }

  async function generateDdi() {
    const { data } = await axios.post(
      `${catalogBase()}${studyRef()}/generate_ddi`,
      {},
      {
        params: studyParams(),
        withCredentials: true,
        headers: { ...csrfHeaders(), 'Content-Type': 'application/json' },
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'GENERATE_DDI_FAILED');
    return data;
  }

  return {
    fetchWarnings,
    setPublished,
    deleteStudy,
    uploadThumbnail,
    removeThumbnail,
    generateDdi,
  };
}
