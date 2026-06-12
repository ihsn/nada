/**
 * Join APP_CONFIG.siteUrl with a path segment (siteUrl may omit a trailing slash).
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

/**
 * Public catalog browse/search base URL (not study detail pages).
 * Vue and classic modes both use /catalog (and /catalog/{repo}).
 * @param {string} siteUrl
 * @param {string|null|undefined} repositoryid
 */
export function catalogSearchUrl(siteUrl, repositoryid) {
  const repo = String(repositoryid ?? '').trim();
  if (!repo || repo.toLowerCase() === 'central') {
    return joinSiteUrl(siteUrl, 'catalog');
  }
  return joinSiteUrl(siteUrl, `catalog/${encodeURIComponent(repo)}`);
}
