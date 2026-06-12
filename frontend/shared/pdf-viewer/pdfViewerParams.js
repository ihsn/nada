/** @typedef {{ source: string, sid: number|null, idno: string, resourceId: number|null, page: number, pageChips: number[] }} ViewerParams */

const MAX_PAGE_CHIPS = 20;

/**
 * Parse comma-separated 1-based page numbers (dedupe, preserve order).
 * @param {string} raw
 * @returns {number[]}
 */
export function parsePageList(raw) {
  if (!raw || typeof raw !== 'string') return [];
  const seen = new Set();
  const out = [];
  for (const part of raw.split(',')) {
    const n = parseInt(part.trim(), 10);
    if (!Number.isFinite(n) || n < 1) continue;
    if (seen.has(n)) continue;
    seen.add(n);
    out.push(n);
    if (out.length >= MAX_PAGE_CHIPS) break;
  }
  return out;
}

/**
 * Parse /viewer/pdf query string.
 * @param {string} [search]
 * @returns {ViewerParams}
 */
export function parseViewerParams(search = '') {
  const qs = search.startsWith('?') ? search.slice(1) : search;
  const params = new URLSearchParams(qs);

  const source = (params.get('source') || 'resource').toLowerCase();
  const sidRaw = params.get('sid');
  const idno = (params.get('idno') || '').trim();
  const resourceRaw = params.get('resource_id');
  const pagesRaw = params.get('pages') || params.get('bookmarks') || '';

  const pageChips = parsePageList(pagesRaw);
  let page = parseInt(params.get('page') || '', 10);
  if (!Number.isFinite(page) || page < 1) {
    page = pageChips[0] || 1;
  }

  return {
    source,
    sid: sidRaw && /^\d+$/.test(sidRaw) ? parseInt(sidRaw, 10) : null,
    idno,
    resourceId: resourceRaw && /^\d+$/.test(resourceRaw) ? parseInt(resourceRaw, 10) : null,
    page,
    pageChips,
  };
}

/**
 * @param {ViewerParams} parsed
 * @returns {string|null} Error message or null if valid.
 */
export function validateViewerParams(parsed) {
  if (parsed.source !== 'resource') {
    return 'Unsupported PDF source';
  }
  if (!parsed.resourceId) {
    return 'resource_id is required';
  }
  if (!parsed.sid && !parsed.idno) {
    return 'sid or idno is required';
  }
  return null;
}
