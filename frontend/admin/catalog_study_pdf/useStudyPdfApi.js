import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Study PDF options, generate, and delete against api/admin/catalog.
 */
export function useStudyPdfApi() {
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

  async function fetchPdfOptions() {
    const { data } = await axios.get(`${catalogBase()}${studyRef()}/pdf_options`, {
      params: studyParams(),
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'PDF_OPTIONS_FAILED');
    return data;
  }

  async function generatePdf(payload) {
    const { data } = await axios.post(`${catalogBase()}${studyRef()}/generate_pdf`, payload, {
      params: studyParams(),
      withCredentials: true,
      headers: { ...csrfHeaders(), 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'GENERATE_PDF_FAILED');
    return data;
  }

  async function deletePdf() {
    const { data } = await axios.post(
      `${catalogBase()}delete_pdf/${studyRef()}`,
      {},
      {
        params: studyParams(),
        withCredentials: true,
        headers: { ...csrfHeaders(), 'Content-Type': 'application/json' },
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'DELETE_PDF_FAILED');
    return data;
  }

  return { fetchPdfOptions, generatePdf, deletePdf };
}
