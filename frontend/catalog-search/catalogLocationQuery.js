/**
 * Read/write catalog search query via the History API (no vue-router).
 * Query values are plain strings (first value wins if a key is repeated).
 */

/** @returns {Record<string, string>} */
export function readLocationQuery() {
  const params = new URLSearchParams(window.location.search);
  const out = {};
  for (const [key, value] of params.entries()) {
    if (!(key in out)) {
      out[key] = value;
    }
  }
  return out;
}

/**
 * @param {Record<string, string>} query
 * @param {{ replace?: boolean }} [options]
 */
export function writeLocationQuery(query, { replace = false } = {}) {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query)) {
    if (value !== '' && value != null) {
      params.set(key, String(value));
    }
  }
  const qs = params.toString();
  const url = `${window.location.pathname}${qs ? `?${qs}` : ''}${window.location.hash || ''}`;
  if (replace) {
    history.replaceState(history.state, '', url);
  } else {
    history.pushState(history.state, '', url);
  }
}
