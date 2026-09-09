/**
 * Lightweight JSON GET via fetch (credentials included).
 * Used by public catalog paths so we do not pull axios into the critical bundle.
 *
 * @param {string} url
 * @param {{ params?: Record<string, string|number|boolean|null|undefined> }} [options]
 * @returns {Promise<any>}
 */
export async function fetchJson(url, { params } = {}) {
  let finalUrl = url;
  if (params && typeof params === 'object') {
    const search = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
      if (value === undefined || value === null || value === '') continue;
      search.set(key, String(value));
    }
    const qs = search.toString();
    if (qs) {
      finalUrl += (finalUrl.includes('?') ? '&' : '?') + qs;
    }
  }

  const res = await fetch(finalUrl, { credentials: 'same-origin' });
  if (!res.ok) {
    throw new Error(`HTTP ${res.status}`);
  }
  return res.json();
}
