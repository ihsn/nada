/** Sentinel stored in layout axes for the DSD time period dimension. */
export const TIME_PERIOD_KEY = '_time_period';

/** Implicit row when no row dimensions are assigned. */
export const SINGLE_ROW_KEY = '__single__';

/** Implicit value column when no column dimensions are assigned. */
export const SINGLE_COL_KEY = '__value__';

const LAYOUT_ELIGIBLE_COLUMN_TYPES = new Set([
  'dimension',
  'time_period',
  'geography',
  'periodicity',
]);

const NON_AXIS_COLUMN_TYPES = new Set([
  'observation_value',
  'measure',
  'indicator_id',
  'indicator_name',
  'attribute',
  'annotation',
]);

function componentPropString(obj, key) {
  if (!obj || typeof obj !== 'object') return '';
  const v = obj[key];
  if (v == null) return '';
  const s = String(v).trim();
  return s;
}

export function parseComponentMetadata(metadata) {
  if (metadata == null || metadata === '') return null;
  if (typeof metadata === 'object' && !Array.isArray(metadata)) {
    return metadata;
  }
  if (typeof metadata === 'string') {
    try {
      const o = JSON.parse(metadata);
      return typeof o === 'object' && o !== null && !Array.isArray(o) ? o : null;
    } catch {
      return null;
    }
  }
  return null;
}

/** Human-facing label from a DSD component row. */
export function facetLabel(component) {
  if (!component) return '';
  const directKeys = ['label', 'title', 'field_label', 'field_title', 'display_name', 'displayName', 'caption'];
  for (const k of directKeys) {
    const v = componentPropString(component, k);
    if (v) return v;
  }
  const meta = parseComponentMetadata(component.metadata);
  if (meta) {
    const metaKeys = [
      'label',
      'title',
      'field_title',
      'field_label',
      'display_name',
      'displayName',
      'concept_title',
      'conceptTitle',
    ];
    for (const k of metaKeys) {
      const v = componentPropString(meta, k);
      if (v) return v;
    }
  }
  return componentPropString(component, 'name');
}

/**
 * @param {Array<object>} components
 * @param {{ observationValueKey?: string; timePeriodKey?: string }} [opts]
 * @returns {Array<{ key: string; component: object | null; role: string }>}
 */
export function getLayoutEligibleDimensions(components, opts = {}) {
  const ov = String(opts.observationValueKey ?? '').trim();
  const tp = String(opts.timePeriodKey ?? '').trim();
  const list = Array.isArray(components) ? components : [];
  const out = [];

  for (const c of list) {
    if (!c?.name) continue;
    const name = String(c.name);
    if (ov && name === ov) continue;
    if (NON_AXIS_COLUMN_TYPES.has(c.column_type)) continue;
    if (!LAYOUT_ELIGIBLE_COLUMN_TYPES.has(c.column_type)) continue;

    if (c.column_type === 'time_period' || (tp && name === tp)) {
      out.push({ key: TIME_PERIOD_KEY, component: c, role: 'time_period' });
      continue;
    }
    out.push({ key: name, component: c, role: c.column_type });
  }

  const seen = new Set();
  return out.filter((d) => {
    if (seen.has(d.key)) return false;
    seen.add(d.key);
    return true;
  });
}

/**
 * @typedef {Object} TableLayoutSpec
 * @property {string[]} rows
 * @property {string[]} columns
 * @property {boolean} [group_headers]
 * @property {boolean} [flatten_labels]
 * @property {'asc'|'desc'} [time_order]
 * @property {string[]} [dimension_order]
 * @property {string[]} [hidden_dimensions]
 * @property {boolean} [hide_fixed_dimensions] @deprecated use hidden_dimensions
 * @property {string[]} [include_fixed] @deprecated use hidden_dimensions
 */

export function createEmptyTableLayout() {
  return {
    rows: [],
    columns: [],
    group_headers: true,
    flatten_labels: false,
    time_order: 'asc',
    dimension_order: [],
    hidden_dimensions: [],
  };
}

/** Normalize legacy layouts that used row_sections. */
export function normalizeTableLayout(layout) {
  const base = layout && typeof layout === 'object' ? layout : createEmptyTableLayout();
  const rows = [...(base.rows || [])];
  const legacySections = [...(base.row_sections || [])];
  if (legacySections.length) {
    const merged = [...legacySections.filter((k) => !rows.includes(k)), ...rows];
    rows.splice(0, rows.length, ...merged);
  }
  const hidden = Array.isArray(base.hidden_dimensions)
    ? [...base.hidden_dimensions].map((k) => String(k ?? '').trim()).filter(Boolean)
    : [];

  // Legacy: hide_fixed + include_fixed → hidden_dimensions (inverse of include)
  if (!hidden.length && base.hide_fixed_dimensions === true && Array.isArray(base.include_fixed)) {
    // Cannot reconstruct full hidden set without eligible dims; leave empty
  }

  const dimension_order = Array.isArray(base.dimension_order)
    ? [...base.dimension_order].map((k) => String(k ?? '').trim()).filter(Boolean)
    : [];

  return {
    rows,
    columns: [...(base.columns || [])],
    group_headers: base.flatten_labels === true ? false : base.group_headers !== false,
    flatten_labels: base.flatten_labels === true,
    time_order: base.time_order === 'desc' ? 'desc' : 'asc',
    dimension_order,
    hidden_dimensions: hidden,
  };
}

/**
 * Dimensions pinned to a single code by active API filters.
 *
 * @param {Record<string, unknown> | null | undefined} filters
 * @param {{ geographyName?: string; periodicityName?: string }} [names]
 * @returns {Map<string, string>}
 */
export function getFixedDimensionsFromFilters(filters, names = {}) {
  const fixed = new Map();
  if (!filters || typeof filters !== 'object') return fixed;

  const d = filters.d && typeof filters.d === 'object' ? filters.d : {};
  const geoName = names.geographyName;
  if (geoName && Array.isArray(d.geography) && d.geography.length === 1) {
    fixed.set(geoName, String(d.geography[0]));
  }
  const perName = names.periodicityName;
  if (perName && Array.isArray(d.periodicity) && d.periodicity.length === 1) {
    fixed.set(perName, String(d.periodicity[0]));
  }
  const c = filters.c && typeof filters.c === 'object' ? filters.c : {};
  for (const [name, vals] of Object.entries(c)) {
    if (Array.isArray(vals) && vals.length === 1) {
      fixed.set(name, String(vals[0]));
    }
  }
  return fixed;
}

/**
 * @param {string} dimKey
 * @param {TableLayoutSpec} layout
 */
export function isDimensionHiddenInLayout(dimKey, layout) {
  const norm = normalizeTableLayout(layout);
  return (norm.hidden_dimensions || []).includes(String(dimKey ?? '').trim());
}

/** @deprecated Use isDimensionHiddenInLayout */
export function isDimensionHiddenFromLayoutPool(dimKey, layout) {
  return isDimensionHiddenInLayout(dimKey, layout);
}

/**
 * @param {TableLayoutSpec | null | undefined} layout
 * @returns {{ valid: boolean; message?: string }}
 */
export function validateTableLayout(layout) {
  const norm = normalizeTableLayout(layout);
  const rows = norm.rows;
  const columns = norm.columns;
  if (!rows.length && !columns.length) {
    return { valid: false, message: 'Assign at least one dimension to Rows and one to Columns.' };
  }
  if (!rows.length) {
    return { valid: false, message: 'Assign at least one dimension to Rows.' };
  }
  if (!columns.length) {
    return { valid: false, message: 'Assign at least one dimension to Columns.' };
  }
  const all = [...rows, ...columns];
  const seen = new Set();
  for (const k of all) {
    const key = String(k ?? '').trim();
    if (!key) {
      return { valid: false, message: 'Layout contains an empty dimension.' };
    }
    if (seen.has(key)) {
      return { valid: false, message: 'Each dimension can appear on only one axis.' };
    }
    seen.add(key);
  }
  return { valid: true };
}

export function layoutIncludesTime(layout) {
  const norm = normalizeTableLayout(layout);
  const axes = [...norm.rows, ...norm.columns];
  return axes.includes(TIME_PERIOD_KEY);
}

export function layoutUsesGroupedHeaders(layout) {
  const norm = normalizeTableLayout(layout);
  if (norm.flatten_labels || norm.group_headers === false) return false;
  return norm.rows.length > 1 || norm.columns.length > 1;
}
