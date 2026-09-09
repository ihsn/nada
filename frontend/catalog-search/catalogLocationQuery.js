/**
 * Read/write catalog search query via the History API (no vue-router).
 * Query values are plain strings (first value wins if a key is repeated,
 * except PHP-style key[]=a&key[]=b which are joined as comma-separated).
 */

import { normalizeQueryKey } from './catalogQuery';

/** @returns {Record<string, string>} */
export function readLocationQuery() {
  const params = new URLSearchParams(window.location.search);
  const out = {};
  for (const [rawKey, value] of params.entries()) {
    const key = normalizeQueryKey(rawKey);
    if (!(key in out)) {
      out[key] = value;
      continue;
    }
    if (out[key] !== value) {
      out[key] = `${out[key]},${value}`;
    }
  }
  return out;
}

/**
 * Same-origin History URL. Never pass a path starting with // — browsers treat
 * that as a protocol-relative URL (//catalog/?sk=… → https://catalog/?sk=…).
 * @param {Record<string, string>} query
 * @returns {string}
 */
export function buildLocationUrl(query) {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query)) {
    if (value !== '' && value != null) {
      params.set(normalizeQueryKey(key), String(value));
    }
  }
  const next = new URL(window.location.href);
  next.pathname = next.pathname.replace(/\/{2,}/g, '/');
  next.search = params.toString();
  return next.href;
}

/**
 * @param {Record<string, string>} query
 * @param {{ replace?: boolean }} [options]
 */
export function writeLocationQuery(query, { replace = false } = {}) {
  const url = buildLocationUrl(query);
  if (replace) {
    history.replaceState(history.state, '', url);
  } else {
    history.pushState(history.state, '', url);
  }
}
