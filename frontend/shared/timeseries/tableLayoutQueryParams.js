import { normalizeTableLayout, validateTableLayout } from './indicatorSchemaUtils.js';
import { joinCommaEncodedDimValues, readCodesFromQueryParam } from './indicatorFilterQueryParams.js';

export const Q_TABLE_ROWS = 'table_rows';
export const Q_TABLE_COLS = 'table_cols';
export const Q_TABLE_TIME_ORDER = 'table_time_order';
export const Q_TABLE_FLATTEN = 'table_flatten';

/**
 * @param {URLSearchParams} q
 * @param {{ eligibleKeys?: string[] }} [options]
 * @returns {import('./indicatorSchemaUtils.js').TableLayoutSpec | null}
 */
export function readTableLayoutFromQuery(q, options = {}) {
  if (!q.has(Q_TABLE_ROWS) && !q.has(Q_TABLE_COLS)) return null;
  const eligible = new Set((options.eligibleKeys || []).map((k) => String(k ?? '').trim()).filter(Boolean));
  const filterEligible = (arr) => {
    const list = (arr || []).map((k) => String(k ?? '').trim()).filter(Boolean);
    if (!eligible.size) return list;
    return list.filter((k) => eligible.has(k));
  };

  const layout = normalizeTableLayout({
    rows: filterEligible(readCodesFromQueryParam(q, Q_TABLE_ROWS)),
    columns: filterEligible(readCodesFromQueryParam(q, Q_TABLE_COLS)),
    time_order: q.get(Q_TABLE_TIME_ORDER) === 'desc' ? 'desc' : 'asc',
    flatten_labels: q.get(Q_TABLE_FLATTEN) === '1',
  });

  const validation = validateTableLayout(layout);
  if (!validation.valid) return null;
  return layout;
}

/** @param {URLSearchParams} q */
export function urlHasTableLayoutParams(q) {
  return q.has(Q_TABLE_ROWS) || q.has(Q_TABLE_COLS);
}

/**
 * @param {import('./indicatorSchemaUtils.js').TableLayoutSpec | null | undefined} layout
 */
export function buildTableLayoutQueryParams(layout) {
  const p = new URLSearchParams();
  if (!layout) return p;
  const norm = normalizeTableLayout(layout);
  const rowsJoined = joinCommaEncodedDimValues(norm.rows);
  const colsJoined = joinCommaEncodedDimValues(norm.columns);
  if (rowsJoined) p.set(Q_TABLE_ROWS, rowsJoined);
  if (colsJoined) p.set(Q_TABLE_COLS, colsJoined);
  if (norm.time_order === 'desc') p.set(Q_TABLE_TIME_ORDER, 'desc');
  if (norm.flatten_labels) p.set(Q_TABLE_FLATTEN, '1');
  return p;
}

/** Remove table layout keys from search params. */
export function stripTableLayoutQueryKeys(sp) {
  for (const key of [Q_TABLE_ROWS, Q_TABLE_COLS, Q_TABLE_TIME_ORDER, Q_TABLE_FLATTEN]) {
    sp.delete(key);
  }
}

/** Remove chart-only keys when syncing the table tab URL. */
export function stripChartOnlyQueryKeys(sp) {
  for (const key of ['chart', 'chart_bg']) {
    sp.delete(key);
  }
}
