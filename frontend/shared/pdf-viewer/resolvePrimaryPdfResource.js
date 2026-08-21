import { fetchJson } from '@/shared/http/fetchJson';

/**
 * @param {object} resource
 * @returns {boolean}
 */
export function isLocalPdfResource(resource) {
  if (!resource || typeof resource !== 'object') return false;

  if (resource.is_url === 1 || resource.is_url === '1' || resource.external_link === true) {
    return false;
  }

  const filename = String(resource.filename || '').trim();
  if (/^https?:\/\//i.test(filename)) return false;

  const fmt = String(resource.dcformat || '').toLowerCase();
  if (fmt === 'application/pdf') return true;

  return filename.toLowerCase().endsWith('.pdf');
}

/**
 * First local PDF resource for a study (heuristic for semantic document hits).
 * @param {string} idno
 * @param {string} apiBaseUrl e.g. siteUrl + '/api/catalog/'
 * @returns {Promise<number|null>}
 */
export async function resolvePrimaryPdfResourceId(idno, apiBaseUrl) {
  const base = String(apiBaseUrl || '').replace(/\/?$/, '/');
  const idnoSeg = encodeURIComponent(String(idno || '').trim());
  if (!idnoSeg) return null;

  const data = await fetchJson(`${base}${idnoSeg}/resources`);

  const resources = data?.resources;
  if (!Array.isArray(resources)) return null;

  for (const resource of resources) {
    if (isLocalPdfResource(resource)) {
      const id = Number(resource.resource_id);
      if (Number.isFinite(id) && id > 0) return id;
    }
  }

  return null;
}
