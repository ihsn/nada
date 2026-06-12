/**
 * @param {string} siteUrl
 * @param {string} path
 * @returns {string}
 */
export function joinSiteUrl(siteUrl, path) {
  const base = String(siteUrl ?? '').replace(/\/+$/, '');
  const segment = String(path ?? '').replace(/^\/+/, '');
  if (!base) return segment ? `/${segment}` : '';
  return segment ? `${base}/${segment}` : base;
}
