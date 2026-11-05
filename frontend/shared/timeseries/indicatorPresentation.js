import { TIME_PERIOD_KEY } from './indicatorSchemaUtils.js';

/**
 * Default view of an indicator's chart and table pages: catalog settings (not metadata), returned
 * as `data_presentation` with the data schema. Every default is optional: codes, periods and columns
 * without data are skipped so callers fall back to their own defaults.
 */

export const PRESENTATION_CHART_TYPES = ['auto', 'line', 'column', 'hbar'];

/** `data_presentation` from the schema response, or null. */
export function presentationFromSchema(schema) {
  const p = schema?.data_presentation;
  return p && typeof p === 'object' && !Array.isArray(p) ? p : null;
}

/** Listed codes that have data (present in the column's codelist items), in listed order. */
export function availablePresentationCodes(wanted, items) {
  if (!Array.isArray(wanted) || !wanted.length) return [];
  const available = new Set((items || []).map((it) => String(it?.value ?? '')));
  const out = [];
  for (const raw of wanted) {
    const code = String(raw ?? '').trim();
    if (code && available.has(code) && !out.includes(code)) out.push(code);
  }
  return out;
}

/**
 * Default codes per column: `geography` for the geography column, `dimensions[]` for the others
 * (frequency included). Columns whose listed codes all lack data are left out.
 *
 * @param {object | null} presentation
 * @param {{ geographyName?: string; codelistSelectItems?: Record<string, Array<{ value?: string }>> }} ctx
 * @returns {Record<string, string[]>}
 */
export function presentationDefaultCodes(presentation, ctx = {}) {
  const out = {};
  if (!presentation) return out;
  const items = ctx.codelistSelectItems || {};
  const geoName = String(ctx.geographyName ?? '').trim();

  if (geoName) {
    const codes = availablePresentationCodes(presentation.geography, items[geoName]);
    if (codes.length) out[geoName] = codes;
  }
  for (const dim of Array.isArray(presentation.dimensions) ? presentation.dimensions : []) {
    const col = String(dim?.column ?? '').trim();
    if (!col || col in out) continue;
    const codes = availablePresentationCodes(dim.values, items[col]);
    if (codes.length) out[col] = codes;
  }
  return out;
}

/**
 * `{ from, to }` from `time_period`; a missing end falls back to `bounds` (`{ min, max }`),
 * so an empty `to` always reaches the latest data. Null when no time period is set.
 */
export function presentationTimeRange(presentation, bounds) {
  const tp = presentation?.time_period;
  const from = String(tp?.from ?? '').trim();
  const to = String(tp?.to ?? '').trim();
  if (!from && !to) return null;
  return {
    from: from || String(bounds?.min ?? ''),
    to: to || String(bounds?.max ?? ''),
  };
}

/** Supported `chart_type`, or '' when unset. */
export function presentationChartType(presentation) {
  const t = String(presentation?.chart_type ?? '').trim();
  return PRESENTATION_CHART_TYPES.includes(t) ? t : '';
}

/**
 * Apply `table_layout` on top of `baseLayout` (the page's own default). Column names map to layout keys
 * (the time period column becomes TIME_PERIOD_KEY) and unknown names are dropped. Eligible columns left on
 * neither axis are appended to the rows: the table keeps one value per row and column, so an unplaced
 * column with several selected values would otherwise be collapsed. Returns `baseLayout` when nothing applies.
 *
 * @param {object | null} presentation
 * @param {import('./indicatorSchemaUtils.js').TableLayoutSpec} baseLayout
 * @param {{ eligibleKeys?: string[]; timePeriodName?: string }} ctx
 */
export function presentationTableLayout(presentation, baseLayout, ctx = {}) {
  const tl = presentation?.table_layout;
  if (!tl || typeof tl !== 'object' || Array.isArray(tl)) return baseLayout;

  const eligible = new Set(ctx.eligibleKeys || []);
  const timeName = String(ctx.timePeriodName ?? '').trim();
  const toKeys = (names) => {
    const out = [];
    for (const raw of Array.isArray(names) ? names : []) {
      const name = String(raw ?? '').trim();
      const key = name && name === timeName ? TIME_PERIOD_KEY : name;
      if (key && eligible.has(key) && !out.includes(key)) out.push(key);
    }
    return out;
  };

  let rows = toKeys(tl.rows);
  let columns = toKeys(tl.columns).filter((k) => !rows.includes(k));
  const placed = rows.length > 0 || columns.length > 0;
  let hidden = [...(baseLayout.hidden_dimensions || [])];

  if (placed) {
    if (!Array.isArray(tl.rows)) rows = (baseLayout.rows || []).filter((k) => !columns.includes(k));
    if (!Array.isArray(tl.columns)) columns = (baseLayout.columns || []).filter((k) => !rows.includes(k));
    hidden = hidden.filter((k) => !rows.includes(k) && !columns.includes(k));
    for (const key of ctx.eligibleKeys || []) {
      if (!rows.includes(key) && !columns.includes(key) && !hidden.includes(key)) rows.push(key);
    }
  } else {
    rows = [...(baseLayout.rows || [])];
    columns = [...(baseLayout.columns || [])];
  }

  const flatten =
    tl.headers === 'flat' ? true : tl.headers === 'grouped' ? false : baseLayout.flatten_labels === true;
  // The layout panel orders each axis by dimension_order, so it must follow the placed rows and columns.
  const axisKeys = [...rows, ...columns];
  const dimensionOrder = [...axisKeys, ...(baseLayout.dimension_order || []).filter((k) => !axisKeys.includes(k))];

  return {
    ...baseLayout,
    rows,
    columns,
    dimension_order: dimensionOrder,
    hidden_dimensions: hidden,
    time_order: tl.time_order === 'desc' || tl.time_order === 'asc' ? tl.time_order : baseLayout.time_order,
    flatten_labels: flatten,
    group_headers: !flatten,
  };
}
