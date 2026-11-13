/** Helpers for the citations pages: CSL dates and names, filters <-> query string / API params. */

export const MONTHS = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];

/** [year, month?, day?] from a CSL date object; [] when it has none */
export function dateParts(date) {
  const first = date && Array.isArray(date['date-parts']) ? date['date-parts'][0] : null;
  if (!Array.isArray(first)) return [];
  const out = [];
  for (const p of first.slice(0, 3)) {
    const n = Number(p);
    if (!Number.isFinite(n) || n < 1) break;
    out.push(n);
  }
  return out;
}

/** CSL date object from year / month / day; undefined without a valid year */
export function buildDate(year, month, day) {
  const y = Number(year);
  if (!Number.isInteger(y) || y < 1 || y > 9999) return undefined;
  const parts = [y];
  const m = Number(month);
  if (Number.isInteger(m) && m >= 1 && m <= 12) {
    parts.push(m);
    const d = Number(day);
    if (Number.isInteger(d) && d >= 1 && d <= 31) parts.push(d);
  }
  return { 'date-parts': [parts] };
}

/** Text of a date for display: "2015-11-04", "2015", or the raw text */
export function dateText(date) {
  const p = dateParts(date);
  if (p.length) return p.map((n, i) => (i === 0 ? String(n) : String(n).padStart(2, '0'))).join('-');
  return (date && (date.raw || date.literal)) || '';
}

export function isOrganization(name) {
  return !!name && typeof name === 'object' && Object.prototype.hasOwnProperty.call(name, 'literal');
}

/** "Doe, Jane; World Bank" for the duplicate check */
export function authorsText(doc) {
  const names = Array.isArray(doc?.author) ? doc.author : [];
  return names
    .map((n) => (isOrganization(n) ? n.literal : [n.family, n.given].filter(Boolean).join(', ')))
    .filter(Boolean)
    .join('; ');
}

/** Remove empty strings, empty lists and objects, and the id, so the document that is sent is minimal */
export function cleanDoc(value, top = true) {
  if (Array.isArray(value)) {
    const items = value.map((v) => cleanDoc(v, false)).filter((v) => v !== undefined);
    return items.length ? items : undefined;
  }
  if (value && typeof value === 'object') {
    const out = {};
    Object.entries(value).forEach(([k, v]) => {
      if (top && k === 'id') return;
      const c = cleanDoc(v, false);
      if (c !== undefined) out[k] = c;
    });
    return Object.keys(out).length || top ? out : undefined;
  }
  if (typeof value === 'string') {
    const s = value.trim();
    return s === '' ? undefined : s;
  }
  if (value === null || value === undefined) return undefined;
  return value;
}

export function emptyFilters() {
  return {
    keywords: '',
    ctype: [],
    published: '',
    flag: [],
    user: [],
    url_status: [],
    has_notes: false,
    no_studies: false,
    year_from: '',
    year_to: '',
    collection: '',
  };
}

const LIST_KEYS = ['ctype', 'flag', 'user', 'url_status'];
const FLAG_KEYS = ['has_notes', 'no_studies'];

/** Filters from route.query */
export function filtersFromQuery(query) {
  const f = emptyFilters();
  const str = (k) => (typeof query[k] === 'string' ? query[k] : '');
  f.keywords = str('keywords');
  f.published = ['0', '1'].includes(str('published')) ? str('published') : '';
  f.year_from = /^\d{4}$/.test(str('year_from')) ? str('year_from') : '';
  f.year_to = /^\d{4}$/.test(str('year_to')) ? str('year_to') : '';
  f.collection = str('collection');
  LIST_KEYS.forEach((k) => {
    const raw = Array.isArray(query[k]) ? query[k].join(',') : str(k);
    f[k] = raw
      .split(',')
      .map((v) => v.trim())
      .filter(Boolean)
      .map((v) => (k === 'user' || k === 'url_status' ? Number(v) : v))
      .filter((v) => !Number.isNaN(v));
  });
  FLAG_KEYS.forEach((k) => {
    f[k] = str(k) === '1';
  });
  return f;
}

/** Query-string / API parameters for filters (empty ones left out) */
export function filtersToParams(f) {
  const p = {};
  if (f.keywords) p.keywords = f.keywords;
  if (f.published !== '' && f.published != null) p.published = f.published;
  if (f.year_from) p.year_from = f.year_from;
  if (f.year_to) p.year_to = f.year_to;
  if (f.collection) p.collection = f.collection;
  LIST_KEYS.forEach((k) => {
    if (f[k] && f[k].length) p[k] = f[k].join(',');
  });
  FLAG_KEYS.forEach((k) => {
    if (f[k]) p[k] = 1;
  });
  return p;
}

export function hasFilters(f) {
  return Object.keys(filtersToParams(f)).length > 0;
}
