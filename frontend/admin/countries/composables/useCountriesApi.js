import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * api/admin/countries and api/admin/regions.
 */
export function useCountriesApi() {
  const { config, csrfToken, csrfTokenName } = useAppConfig();

  function url(base, path = '') {
    return String(base || '').replace(/\/?$/, '/') + path;
  }

  const countriesUrl = (path) => url(config.value.countriesApiUrl, path);
  const regionsUrl = (path) => url(config.value.regionsApiUrl, path);

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    return tok ? { [name]: tok } : {};
  }

  function apiError(e, fallback) {
    return e?.response?.data?.message || e?.message || fallback;
  }

  async function get(endpoint, key, fallback) {
    try {
      const { data } = await axios.get(endpoint);
      if (data.status !== 'success') throw new Error(data.message || fallback);
      return data[key];
    } catch (e) {
      throw new Error(apiError(e, fallback), { cause: e });
    }
  }

  async function post(endpoint, payload, key, fallback) {
    try {
      const { data } = await axios.post(endpoint, payload ?? null, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      });
      if (data.status !== 'success') throw new Error(data.message || fallback);
      return key ? data[key] : true;
    } catch (e) {
      throw new Error(apiError(e, fallback), { cause: e });
    }
  }

  const id = (v) => encodeURIComponent(String(v));

  return {
    listCountries: () => get(countriesUrl(), 'countries', 'Failed to load countries'),
    getCountry: (cid) => get(countriesUrl(id(cid)), 'country', 'Failed to load country'),
    createCountry: (payload) => post(countriesUrl(), payload, 'country', 'Create failed'),
    updateCountry: (cid, payload) => post(countriesUrl(id(cid)), payload, 'country', 'Update failed'),
    deleteCountry: (cid) => post(countriesUrl(`${id(cid)}/delete`), null, null, 'Delete failed'),
    listMappings: () => get(countriesUrl('mappings'), 'mappings', 'Failed to load mappings'),
    assignMapping: (name, cid) =>
      post(countriesUrl('mappings'), { name, cid }, 'country', 'Update failed'),

    listRegions: () => get(regionsUrl(), 'regions', 'Failed to load regions'),
    listRegionCountries: () => get(regionsUrl('countries'), 'countries', 'Failed to load countries'),
    getRegion: (rid) => get(regionsUrl(id(rid)), 'region', 'Failed to load region'),
    createRegion: (payload) => post(regionsUrl(), payload, 'region', 'Create failed'),
    updateRegion: (rid, payload) => post(regionsUrl(id(rid)), payload, 'region', 'Update failed'),
    deleteRegion: (rid) => post(regionsUrl(`${id(rid)}/delete`), null, null, 'Delete failed'),
  };
}
