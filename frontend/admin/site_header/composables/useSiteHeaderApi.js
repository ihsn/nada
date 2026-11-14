import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** Client for api/admin/site_header. Every POST carries the session CSRF token in X-CSRF-TOKEN. */
export function useSiteHeaderApi() {
  const { apiBaseUrl, csrfToken } = useAppConfig();

  function url(path = '') {
    return `${String(apiBaseUrl.value || '').replace(/\/?$/, '/')}${path}`;
  }

  function headers(extra = {}) {
    return { 'X-CSRF-TOKEN': csrfToken.value || '', ...extra };
  }

  async function get(path) {
    const { data } = await axios.get(url(path));
    return data;
  }

  async function post(path, body = {}) {
    const { data } = await axios.post(url(path), body, {
      headers: headers({ 'Content-Type': 'application/json' }),
    });
    return data;
  }

  return {
    load: () => get(''),
    saveDraft: (header) => post('draft', { header }),
    publish: (header) => post('publish', header ? { header } : {}),
    discard: () => post('discard'),
    reset: () => post('reset'),
    async upload(kind, file) {
      const form = new FormData();
      form.append('file', file);
      const { data } = await axios.post(url(`upload/${kind}`), form, { headers: headers() });
      return data;
    },
  };
}
