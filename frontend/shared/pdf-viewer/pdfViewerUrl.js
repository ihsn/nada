import { joinSiteUrl } from './siteUrl';

/**
 * Build global viewer URL with page / pages params.
 * @param {string} siteUrl
 * @param {{
 *   source?: string,
 *   sid?: number|null,
 *   idno?: string,
 *   resourceId: number,
 *   page?: number,
 *   pages?: number[],
 *   embed?: boolean,
 * }} opts
 * @returns {string}
 */
export function buildPdfViewerUrl(siteUrl, opts) {
  const q = new URLSearchParams();
  q.set('source', opts.source || 'resource');

  if (opts.sid) {
    q.set('sid', String(opts.sid));
  } else if (opts.idno) {
    q.set('idno', opts.idno);
  }

  q.set('resource_id', String(opts.resourceId));

  if (opts.page) {
    q.set('page', String(opts.page));
  }

  const pages = Array.isArray(opts.pages) ? opts.pages.filter((n) => n > 0) : [];
  if (pages.length) {
    q.set('pages', pages.join(','));
  }

  if (opts.embed) {
    q.set('embed', '1');
  }

  return joinSiteUrl(siteUrl, `viewer/pdf?${q.toString()}`);
}
