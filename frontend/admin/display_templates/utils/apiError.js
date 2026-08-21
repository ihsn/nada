/**
 * Extract a user-facing message from axios / NADA API errors.
 * @param {unknown} err
 * @param {string} [fallback]
 */
export function formatApiError(err, fallback = 'Request failed') {
  const data = err?.response?.data;
  if (data && typeof data === 'object') {
    if (data.message) return String(data.message);
    if (data.error) return String(data.error);
  }
  const msg = err?.message ? String(err.message) : '';
  if (msg && !/^Request failed with status code \d+$/i.test(msg)) return msg;
  return fallback;
}
