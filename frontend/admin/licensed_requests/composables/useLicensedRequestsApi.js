import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useLicensedRequestsApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    return tok ? { [name]: tok } : {};
  }

  async function fetchBootstrap() {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.get(`${base}bootstrap`);
    if (data.status !== 'success') throw new Error(data.message || 'Bootstrap failed');
    return data;
  }

  /**
   * @param {Record<string, string|number|undefined>} params
   */
  async function search(params) {
    loading.value = true;
    error.value = null;
    try {
      const base = apiBaseUrl.value || '';
      const { data } = await axios.get(`${base}search`, { params });
      if (data.status !== 'success') throw new Error(data.message || 'Search failed');
      return data.result;
    } catch (e) {
      error.value = e;
      console.error('Licensed requests search error:', e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Full edit payload (GET item/:id)
   * @param {number|string} id
   */
  async function fetchDetail(id) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.get(`${base}item/${encodeURIComponent(String(id))}`);
    if (data.status !== 'success') throw new Error(data.message || 'Not found');
    return data.data;
  }

  /**
   * @param {number|string} id
   * @param {object} payload — status, comments, ip_limit, notify, files[]
   */
  async function patchDetail(id, payload) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.patch(`${base}item/${encodeURIComponent(String(id))}`, payload, {
      headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Update failed');
    return data.data;
  }

  /**
   * @param {number|string} id
   * @param {{ to: string, cc?: string, subject: string, body: string }} payload
   */
  async function sendMail(id, payload) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.post(
      `${base}send_mail/${encodeURIComponent(String(id))}`,
      payload,
      { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Send failed');
    return data.data;
  }

  /**
   * @param {number|string} id
   * @param {{ to: string, cc?: string, subject: string, body: string }} payload
   */
  async function forwardMail(id, payload) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.post(
      `${base}forward/${encodeURIComponent(String(id))}`,
      payload,
      { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Forward failed');
    return data.data;
  }

  return {
    loading,
    error,
    fetchBootstrap,
    search,
    fetchDetail,
    patchDetail,
    sendMail,
    forwardMail,
  };
}
