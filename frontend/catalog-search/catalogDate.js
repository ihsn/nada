/**
 * Parse catalog study dates from API rows.
 * Values may be ISO 8601 (after unix_date_to_gmt), unix seconds, or unix ms.
 */

function toDate(value) {
  if (value == null || value === '') return null;

  if (typeof value === 'number' && Number.isFinite(value)) {
    const ms = value < 1e12 ? value * 1000 : value;
    const d = new Date(ms);
    return Number.isNaN(d.getTime()) ? null : d;
  }

  const str = String(value).trim();
  if (!str) return null;

  if (/^\d{10,13}$/.test(str)) {
    const n = Number(str);
    const ms = str.length <= 10 ? n * 1000 : n;
    const d = new Date(ms);
    return Number.isNaN(d.getTime()) ? null : d;
  }

  const d = new Date(str);
  return Number.isNaN(d.getTime()) ? null : d;
}

/**
 * @param {string|number|null|undefined} value
 * @returns {string} Legacy-style "Mar 15, 2024" or empty if unparseable
 */
export function formatCatalogDate(value) {
  const d = toDate(value);
  if (!d) return '';
  return d.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

/**
 * Treat 0 / null / empty as no year (classic surveys.php hides year when 0).
 * @param {string|number|null|undefined} value
 * @returns {number|null}
 */
export function normalizeCatalogYear(value) {
  if (value == null || value === '') return null;
  const n = Number(value);
  if (!Number.isFinite(n) || n <= 0) return null;
  return n;
}

/**
 * @param {string|number|null|undefined} yearStart
 * @param {string|number|null|undefined} yearEnd
 * @returns {string}
 */
export function formatStudyYearRange(yearStart, yearEnd) {
  const start = normalizeCatalogYear(yearStart);
  const end = normalizeCatalogYear(yearEnd);
  if (start == null && end == null) return '';
  if (start != null && end != null && start === end) return String(start);
  if (start != null && end != null) return `${start}–${end}`;
  return String(start ?? end);
}
