import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Admin site configurations API (session cookie auth; CSRF excluded for api/*).
 */
export function useSiteConfigurationsApi() {
  const { apiBaseUrl } = useAppConfig();

  function base() {
    const b = apiBaseUrl.value || '';
    return b.endsWith('/') ? b : `${b}/`;
  }

  async function fetchSettings() {
    const { data } = await axios.get(base());
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_FAILED');
    return data.settings || {};
  }

  async function fetchMeta() {
    const { data } = await axios.get(`${base()}meta`);
    if (data.status !== 'success') throw new Error(data.message || 'META_FAILED');
    return data.meta || {};
  }

  /**
   * Batch save (POST alias when PUT blocked).
   * @param {Record<string, unknown>} settings flat key/value map
   */
  async function saveSettings(settings) {
    const { data } = await axios.post(
      `${base()}save`,
      { settings },
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'SAVE_FAILED');
    return data;
  }

  async function fetchTestEmailForm() {
    const { data } = await axios.get(`${base()}test_email`);
    if (data.status !== 'success') throw new Error(data.message || 'TEST_EMAIL_LOAD_FAILED');
    return data.form || {};
  }

  /** @returns {Promise<{ status: string, sent: boolean, debugger: string }>} */
  async function sendTestEmail(payload) {
    const { data } = await axios.post(`${base()}test_email_send`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'TEST_EMAIL_SEND_FAILED');
    return data;
  }

  return { fetchSettings, fetchMeta, saveSettings, fetchTestEmailForm, sendTestEmail };
}
