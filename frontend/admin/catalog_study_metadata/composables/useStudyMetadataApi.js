import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { extractApiError } from './apiErrors';

export function useStudyMetadataApi() {
  const { config, csrfToken, csrfTokenName } = useAppConfig();

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    if (!tok) return {};
    return { [name]: tok };
  }

  function metadataApiUrl() {
    return String(config.value?.metadataApiUrl || '').replace(/\/+$/, '');
  }

  function updateUrl() {
    return String(config.value?.updateUrl || '').replace(/\/+$/, '');
  }

  function throwApiError(errOrData, fallbackMessage) {
    const extracted = extractApiError(
      errOrData?.isAxiosError || errOrData?.response
        ? errOrData
        : { responseData: errOrData, message: errOrData?.message || fallbackMessage }
    );
    const err = new Error(extracted.message || fallbackMessage);
    err.errors = extracted.errors;
    err.responseData = extracted.raw;
    throw err;
  }

  async function fetchMetadata() {
    const url = metadataApiUrl();
    if (!url) throw new Error('metadataApiUrl missing');
    try {
      const { data } = await axios.get(url, {
        params: { id_format: 'id' },
        withCredentials: true,
      });
      if (data.status !== 'success') {
        throwApiError(data, data.message || 'LOAD_METADATA_FAILED');
      }
      return data.dataset?.metadata || {};
    } catch (e) {
      if (e?.errors) throw e;
      throwApiError(e, 'Failed to load metadata');
    }
  }

  async function saveMetadata(payload) {
    const url = updateUrl();
    if (!url) throw new Error('updateUrl missing');
    // Default server behavior merges partial metadata (legacy inline editor); do not use replace.
    const body = { ...(payload || {}) };
    try {
      const { data } = await axios.post(url, body, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
        withCredentials: true,
      });
      if (data.status === 'failed' || data.status === 'error') {
        throwApiError(data, data.message || 'SAVE_FAILED');
      }
      return data;
    } catch (e) {
      if (e?.errors) throw e;
      throwApiError(e, 'Save failed');
    }
  }

  return { fetchMetadata, saveMetadata };
}
