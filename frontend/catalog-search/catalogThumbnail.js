/**
 * Resolve public study thumbnail URL (legacy surveys.php / images.php).
 * DB value is usually a filename; may be a path or driver-encoded array.
 */

function normalizeThumbnailRaw(thumb) {
  if (thumb == null || thumb === '') return '';
  if (Array.isArray(thumb)) {
    return thumb.join('');
  }
  return String(thumb).trim();
}

function appendCacheBust(url, version) {
  if (version == null || version === '') return url;
  const sep = url.includes('?') ? '&' : '?';
  return `${url}${sep}v=${encodeURIComponent(String(version))}`;
}

/**
 * @param {unknown} thumb - surveys.thumbnail from API
 * @param {string} baseUrl - APP_CONFIG.baseUrl (preferred for static files)
 * @param {string} [siteUrl] - fallback root
 * @param {string|number} [version] - optional cache buster (e.g. changed)
 * @returns {string|null}
 */
export function resolveStudyThumbnailUrl(thumb, baseUrl, siteUrl, version) {
  const raw = normalizeThumbnailRaw(thumb);
  if (!raw) return null;

  if (/^https?:\/\//i.test(raw)) {
    return appendCacheBust(raw, version);
  }

  const filename = raw.replace(/\\/g, '/').split('/').filter(Boolean).pop();
  if (!filename) return null;

  const root = (baseUrl || siteUrl || '').replace(/\/+$/, '');
  if (!root) return null;

  return appendCacheBust(`${root}/files/thumbnails/${filename}`, version);
}

/** Default placeholder when a study or collection has no thumbnail. */
export function defaultCatalogIconUrl(baseUrl, siteUrl) {
  const root = (baseUrl || siteUrl || '').replace(/\/+$/, '');
  return root ? `${root}/files/icon-blank.png` : '/files/icon-blank.png';
}

/**
 * Collection/repository thumbnail — stored as a site-relative path, not under files/thumbnails/.
 * @returns {string}
 */
export function resolveCollectionThumbnailUrl(thumb, baseUrl, siteUrl) {
  const raw = normalizeThumbnailRaw(thumb);
  if (!raw) return defaultCatalogIconUrl(baseUrl, siteUrl);

  if (/^https?:\/\//i.test(raw)) {
    return raw;
  }

  const root = (baseUrl || siteUrl || '').replace(/\/+$/, '');
  if (!root) return defaultCatalogIconUrl(baseUrl, siteUrl);

  return `${root}/${raw.replace(/^\//, '')}`;
}
