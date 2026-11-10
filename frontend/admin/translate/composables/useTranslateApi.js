import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * api/admin/translate
 */
export function useTranslateApi() {
  const { config, csrfToken, csrfTokenName } = useAppConfig();

  function base(path = '') {
    return String(config.value.translateApiUrl || '').replace(/\/?$/, '/') + path;
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    return tok ? { [name]: tok } : {};
  }

  function apiError(e, fallback) {
    return e?.response?.data?.message || e?.message || fallback;
  }

  async function get(endpoint, fallback) {
    try {
      const { data } = await axios.get(endpoint);
      if (data.status !== 'success') throw new Error(data.message || fallback);
      return data;
    } catch (e) {
      throw new Error(apiError(e, fallback), { cause: e });
    }
  }

  async function post(endpoint, payload, fallback, extraHeaders = {}) {
    try {
      const { data } = await axios.post(endpoint, payload ?? null, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders(), ...extraHeaders },
      });
      if (data.status !== 'success') throw new Error(data.message || fallback);
      return data;
    } catch (e) {
      throw new Error(apiError(e, fallback), { cause: e });
    }
  }

  const id = (v) => encodeURIComponent(String(v));

  return {
    listLanguages: () => get(base(), 'Failed to load languages'),
    createLanguage: (language_name) =>
      post(base(), { language_name }, 'Create failed'),
    deleteLanguage: (lang) =>
      post(base(`${id(lang)}/delete`), null, 'Delete failed'),
    listFiles: (lang) => get(base(`${id(lang)}/files`), 'Failed to load files'),
    getFile: (lang, file) =>
      get(base(`${id(lang)}/files/${id(file)}`), 'Failed to load file'),
    saveFile: (lang, file, translations) =>
      post(base(`${id(lang)}/files/${id(file)}`), { translations }, 'Save failed'),
    resetFile: (lang, file) =>
      post(base(`${id(lang)}/files/${id(file)}/reset`), null, 'Reset failed'),
    search: (lang, { q = '', missing = false, limit = 500 } = {}) => {
      const params = new URLSearchParams();
      if (q) params.set('q', q);
      if (missing) params.set('missing', '1');
      if (limit) params.set('limit', String(limit));
      const qs = params.toString();
      return get(base(`${id(lang)}/search${qs ? `?${qs}` : ''}`), 'Search failed');
    },
    exportUrl: (lang) => base(`${id(lang)}/export`),
    async importZip(file) {
      const form = new FormData();
      form.append('lang_zip', file);
      try {
        const { data } = await axios.post(base('import'), form, {
          headers: { ...csrfHeaders() },
        });
        if (data.status !== 'success') {
          const err = new Error(data.message || 'Import failed');
          err.skipped = data.skipped || [];
          throw err;
        }
        return data;
      } catch (e) {
        if (e.skipped) throw e;
        const err = new Error(apiError(e, 'Import failed'));
        err.skipped = e?.response?.data?.skipped || [];
        throw err;
      }
    },
  };
}
