/** Standard catalog search URL query keys and defaults. */

export const STANDARD_QUERY_KEYS = new Set([
  'sk', 'tab_type', 'type', 'collection', 'region', 'dtype', 'data_class',
  'country', 'tag', 'from', 'to', 'sort_by', 'sort_order', 'page', 'ps',
  'view', 'vk', 'vf', 'image_view',
]);

/** Tabs where study/variable view toggle is offered (legacy search_nav_bar.php + microdata code). */
export function isVariableViewTab(tabType) {
  const tab = tabType || '';
  return tab === '' || tab === 'survey' || tab === 'microdata' || tab === 'geospatial';
}

export function isCatalogVariableViewEnabled(siteConfig) {
  const cfg = siteConfig?.catalog_variable_view;
  return cfg === 'yes' || cfg === true;
}

export const DEFAULT_QUERY = {
  sk: '',
  tab_type: '',
  type: '',
  collection: '',
  region: '',
  dtype: '',
  data_class: '',
  database: '',
  country: '',
  tag: '',
  from: '',
  to: '',
  sort_by: '',
  sort_order: '',
  page: 1,
  ps: 15,
  view: '',
  vk: '',
  vf: '',
  image_view: '',
};

/**
 * Ensure year from ≤ to when both are set.
 * @param {string|number} from
 * @param {string|number} to
 * @returns {{ from: string, to: string }}
 */
export function normalizeYearRange(from, to) {
  const fromStr = from != null && from !== '' ? String(from) : '';
  const toStr = to != null && to !== '' ? String(to) : '';
  const fromNum = parseInt(fromStr, 10);
  const toNum = parseInt(toStr, 10);

  if (!fromStr || !toStr || Number.isNaN(fromNum) || Number.isNaN(toNum)) {
    return { from: fromStr, to: toStr };
  }

  if (fromNum > toNum) {
    return { from: String(toNum), to: String(fromNum) };
  }

  return { from: fromStr, to: toStr };
}

/**
 * @param {Record<string, unknown>} query
 * @returns {Record<string, unknown>}
 */
export function normalizeYearQuery(query) {
  const { from, to } = normalizeYearRange(query.from, query.to);
  if (from === query.from && to === query.to) {
    return query;
  }
  return { ...query, from, to };
}

/**
 * Parse vue-router query object into normalized catalog search state.
 * @param {import('vue-router').LocationQuery} routeQuery
 * @returns {Record<string, string | number>}
 */
export function parseRouteQuery(routeQuery) {
  const out = { ...DEFAULT_QUERY };

  for (const [key, raw] of Object.entries(routeQuery)) {
    if (raw == null) continue;
    const val = Array.isArray(raw) ? raw[0] : raw;
    if (val === '' || val == null) continue;

    if (key === 'page') {
      out.page = Math.max(1, parseInt(String(val), 10) || 1);
    } else if (key === 'ps') {
      out.ps = parseInt(String(val), 10) || 15;
    } else if (Object.prototype.hasOwnProperty.call(DEFAULT_QUERY, key)) {
      out[key] = String(val);
    } else {
      out[key] = String(val);
    }
  }

  const years = normalizeYearRange(out.from, out.to);
  out.from = years.from;
  out.to = years.to;

  return out;
}

/**
 * Build vue-router query from catalog search state (omit defaults).
 * @param {Record<string, unknown>} query
 * @returns {Record<string, string>}
 */
export function serializeRouteQuery(query) {
  const out = {};

  for (const [key, def] of Object.entries(DEFAULT_QUERY)) {
    const val = query[key];
    if (val !== def && val !== '' && val != null) {
      out[key] = String(val);
    }
  }

  for (const key of Object.keys(query)) {
    if (!STANDARD_QUERY_KEYS.has(key) && query[key] !== '' && query[key] != null) {
      out[key] = String(query[key]);
    }
  }

  return out;
}

/**
 * Stable fingerprint for matching SSR bootstrap to the current route query.
 * @param {import('vue-router').LocationQuery} routeQuery
 * @returns {string}
 */
export function catalogQueryFingerprint(routeQuery) {
  const parsed = parseRouteQuery(routeQuery);
  const serialized = serializeRouteQuery(parsed);
  const keys = Object.keys(serialized).sort();
  const sorted = {};
  for (const key of keys) {
    sorted[key] = serialized[key];
  }
  return JSON.stringify(sorted);
}
