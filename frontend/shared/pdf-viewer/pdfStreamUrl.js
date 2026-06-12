import { joinSiteUrl } from './siteUrl';

/**
 * Build same-origin URL for inline PDF bytes.
 * @param {string} siteUrl
 * @param {{ source?: string, sid?: number|null, idno?: string, resourceId: number }} opts
 * @returns {string}
 */
export function buildPdfStreamUrl(siteUrl, opts) {
  const source = (opts.source || 'resource').toLowerCase();
  const resourceId = Number(opts.resourceId);

  if (source !== 'resource') {
    throw new Error('Unsupported PDF source');
  }
  if (!Number.isFinite(resourceId) || resourceId <= 0) {
    throw new Error('resource_id is required');
  }

  if (opts.sid) {
    return joinSiteUrl(siteUrl, `catalog/${opts.sid}/pdf-stream/${resourceId}`);
  }

  const idno = String(opts.idno || '').trim();
  if (idno) {
    return joinSiteUrl(
      siteUrl,
      `api/catalog/${encodeURIComponent(idno)}/resources/${resourceId}/pdf-stream`
    );
  }

  throw new Error('sid or idno is required');
}
