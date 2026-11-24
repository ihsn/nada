/**
 * Query helpers for the public data tables API page (/api/tables).
 */

/** Columns shown by default when the table has more than this many. */
export const DEFAULT_VISIBLE_COLUMNS = 20;

export const PAGE_SIZE = 15;

/** Pagination shows only the first pages; filters narrow larger results. */
export const MAX_PAGES = 20;

const NUMERIC_TYPES = ['integer', 'int', 'float', 'decimal', 'double'];

export function isNumericType(dataType) {
  return NUMERIC_TYPES.includes(String(dataType || '').toLowerCase());
}

/** Text for a grid cell: null → '', lists joined with |, objects as JSON. */
export function cellText(value) {
  if (value === null || value === undefined) return '';
  if (typeof value === 'boolean') return value ? 'true' : 'false';
  if (Array.isArray(value)) {
    if (value.every((v) => v === null || typeof v !== 'object')) {
      return value.map((v) => cellText(v)).join('|');
    }
    return JSON.stringify(value);
  }
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
}

/**
 * Filters → { column: "v1,v2" }. Rows without a column or value are skipped; several rows on the
 * same column are combined (any of), instead of the last one silently winning.
 */
export function filtersToParams(filters) {
  const byColumn = {};
  for (const f of filters || []) {
    const column = String(f?.column || '').trim();
    const raw = Array.isArray(f?.value) ? f.value.join(',') : String(f?.value ?? '');
    const value = raw.trim();
    if (!column || value === '') continue;
    byColumn[column] = byColumn[column] ? `${byColumn[column]},${value}` : value;
  }
  return byColumn;
}

/**
 * API query parameters for the data endpoint.
 *
 * Filters are sent as c[column]=value so a column named like a reserved parameter (limit, fields…)
 * cannot be mistaken for one. `fields` is omitted when every column is selected (same result,
 * and keeps the URL short: wide tables have 1,000+ columns).
 */
export function buildDataParams({ columns, allColumnsSelected, filters, limit, offset }) {
  const params = new URLSearchParams();
  params.set('limit', String(limit));
  params.set('offset', String(offset));
  if (!allColumnsSelected && columns?.length) {
    params.set('fields', columns.join(','));
  }
  const byColumn = filtersToParams(filters);
  for (const [column, value] of Object.entries(byColumn)) {
    params.set(`c[${column}]`, value);
  }
  return params;
}

/** Readable query string (brackets and commas unescaped) for display and copy. */
export function displayQuery(params) {
  return params
    .toString()
    .replace(/%5B/gi, '[')
    .replace(/%5D/gi, ']')
    .replace(/%2C/gi, ',');
}

/**
 * Page URL state (?view=…): columns, filters, page and page size, so a view can be shared.
 */
export function readViewState() {
  try {
    const raw = new URLSearchParams(window.location.search).get('view');
    if (!raw) return null;
    const state = JSON.parse(raw);
    return state && typeof state === 'object' ? state : null;
  } catch {
    return null;
  }
}

export function writeViewState(state) {
  try {
    const url = new URL(window.location.href);
    const isDefault =
      !state.columns && !state.all && (!state.filters || !state.filters.length) && state.page === 1;
    if (isDefault) {
      url.searchParams.delete('view');
    } else {
      url.searchParams.set('view', JSON.stringify(state));
    }
    window.history.replaceState(window.history.state, '', url.toString());
  } catch {
    // URL state is a convenience only
  }
}

/** Replace {name} placeholders. */
export function fmt(template, values) {
  return String(template || '').replace(/\{(\w+)\}/g, (m, k) =>
    values && values[k] !== undefined ? String(values[k]) : m
  );
}
