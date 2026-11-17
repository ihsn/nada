import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Client for api/admin/site_layout: the header, footer and styles parts, and package export and import.
 * Every POST carries the session CSRF token in X-CSRF-TOKEN.
 */
export function useSiteLayoutApi() {
  const { config, csrfToken } = useAppConfig();

  function url(path = '') {
    const base = config.value?.layoutApiBaseUrl || '';
    return `${String(base).replace(/\/?$/, '/')}${path}`;
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

  /** A failed blob request carries the JSON error as a Blob */
  async function blobError(e) {
    const data = e?.response?.data;
    if (data instanceof Blob) {
      try {
        e.response.data = JSON.parse(await data.text());
      } catch {
        /* not JSON */
      }
    }
    return e;
  }

  async function upload(path, file, fields = {}) {
    const form = new FormData();
    form.append('file', file);
    Object.entries(fields).forEach(([k, v]) => form.append(k, v));
    const { data } = await axios.post(url(path), form, { headers: headers() });
    return data;
  }

  /** Calls for one part: 'header', 'footer' or 'styles' */
  function part(name) {
    return {
      load: () => get(`state/${name}`),
      saveDraft: (settings) => post(`draft/${name}`, { settings }),
      render: (settings, asGuest) => post(`render/${name}`, { settings, as_guest: !!asGuest }),
      publish: (settings) => post(`publish/${name}`, settings ? { settings } : {}),
      discard: () => post(`discard/${name}`),
      reset: () => post(`reset/${name}`),
      republish: () => post(`republish/${name}`),
      /** Header images: kind is 'logo' or 'background' */
      upload: (kind, file) => upload(`upload/${kind}`, file),
    };
  }

  return {
    part,
    /** Download the package; resolves to { blob, filename } */
    async exportPackage(parts, state) {
      try {
        const res = await axios.get(url('export'), {
          params: { parts: parts.join(','), state },
          responseType: 'blob',
        });
        const disposition = String(res.headers['content-disposition'] || '');
        const match = disposition.match(/filename="?([^";]+)"?/i);
        return { blob: res.data, filename: match ? match[1] : 'site-layout.zip' };
      } catch (e) {
        throw await blobError(e);
      }
    },
    inspect: (file) => upload('inspect', file),
    importPackage: (file, parts) => upload('import', file, { parts: parts.join(',') }),
  };
}
