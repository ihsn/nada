import { TIME_PERIOD_KEY } from './indicatorSchemaUtils.js';

/** @typedef {'row' | 'column' | null} LayoutAxis */

/**
 * Match chart filter sidebar: show a facet only when the study has >1 observed codelist option.
 * While options are undefined (not loaded yet), treat as visible.
 *
 * @param {string} componentName
 * @param {Record<string, Array<{ value?: string }>>} codelistSelectItems
 */
export function facetShowsFilter(componentName, codelistSelectItems) {
  const key = String(componentName ?? '').trim();
  const items = codelistSelectItems?.[key];
  if (items === undefined) return true;
  return Array.isArray(items) && items.length > 1;
}

/**
 * @param {Array<{ key: string; component?: { name?: string }; role?: string }>} eligibleDimensions
 * @param {Record<string, Array<{ value?: string }>>} codelistSelectItems
 * @param {{ geographyLayoutKey?: string }} [opts]
 * @returns {Set<string>}
 */
export function getStudySingletonDimensionKeys(eligibleDimensions, codelistSelectItems, opts = {}) {
  const geoKey = String(opts.geographyLayoutKey ?? '').trim();
  const singletons = new Set();
  for (const dim of eligibleDimensions || []) {
    if (!dim?.key) continue;
    if (dim.role === 'geography' || dim.key === geoKey) continue;
    const codelistKey = dim.component?.name || dim.key;
    const items = codelistSelectItems?.[codelistKey];
    if (items === undefined) continue;
    if (!facetShowsFilter(codelistKey, codelistSelectItems)) {
      singletons.add(dim.key);
    }
  }
  return singletons;
}

/**
 * Default list order: schema-eligible dimension keys.
 * @param {Array<{ key: string }>} eligibleDimensions
 */
export function defaultDimensionOrder(eligibleDimensions) {
  return (eligibleDimensions || []).map((d) => d.key).filter(Boolean);
}

/**
 * @param {import('./indicatorSchemaUtils.js').TableLayoutSpec} layout
 * @param {string[]} eligibleKeys
 */
export function resolveDimensionOrder(layout, eligibleKeys) {
  const norm = layout || {};
  const stored = Array.isArray(norm.dimension_order) ? norm.dimension_order.map(String) : [];
  const eligible = new Set(eligibleKeys);
  const order = stored.filter((k) => eligible.has(k));
  for (const k of eligibleKeys) {
    if (!order.includes(k)) order.push(k);
  }
  return order;
}

/**
 * @param {string[]} dimensionOrder
 * @param {string[]} rows
 * @param {string[]} columns
 */
export function axesOrderedByDimensionOrder(dimensionOrder, rows, columns) {
  const rowSet = new Set(rows || []);
  const colSet = new Set(columns || []);
  return {
    rows: dimensionOrder.filter((k) => rowSet.has(k)),
    columns: dimensionOrder.filter((k) => colSet.has(k)),
  };
}

/**
 * @param {string} key
 * @param {string[]} dimensionOrder
 * @param {string[]} axisKeys
 */
export function axisBadgeIndex(key, dimensionOrder, axisKeys) {
  const set = new Set(axisKeys || []);
  if (!set.has(key)) return 0;
  let n = 0;
  for (const k of dimensionOrder) {
    if (!set.has(k)) continue;
    n += 1;
    if (k === key) return n;
  }
  return 0;
}

/**
 * @param {string[]} dimensionOrder
 * @param {string} key
 * @param {number} delta
 */
export function moveKeyInOrder(dimensionOrder, key, delta) {
  const idx = dimensionOrder.indexOf(key);
  if (idx < 0) return dimensionOrder;
  const next = idx + delta;
  if (next < 0 || next >= dimensionOrder.length) return dimensionOrder;
  const copy = [...dimensionOrder];
  copy.splice(idx, 1);
  copy.splice(next, 0, key);
  return copy;
}

/**
 * @param {string[]} rows
 * @param {string[]} columns
 */
export function swapLayoutAxes(rows, columns) {
  return { rows: [...columns], columns: [...rows] };
}

/**
 * @param {Set<string>} singletonKeys
 * @param {string[]} hiddenDimensions
 */
export function defaultHiddenForStudySingletons(singletonKeys, hiddenDimensions = []) {
  const hidden = new Set(hiddenDimensions || []);
  for (const k of singletonKeys || []) {
    hidden.add(k);
  }
  return [...hidden];
}

/**
 * @param {string} key
 * @param {string[]} hiddenDimensions
 */
export function isDimensionHidden(key, hiddenDimensions) {
  return (hiddenDimensions || []).includes(key);
}

export function isTimePeriodKey(key) {
  return key === TIME_PERIOD_KEY;
}

/**
 * Reorder visible (non-singleton) keys and merge back into full dimension_order.
 * @param {string[]} dimensionOrder
 * @param {Set<string>} studySingletonKeys
 * @param {string[]} nextVisibleOrder
 */
export function applyVisibleOrderToDimensionOrder(dimensionOrder, studySingletonKeys, nextVisibleOrder) {
  const singletons = studySingletonKeys || new Set();
  let vi = 0;
  return (dimensionOrder || []).map((k) => (singletons.has(k) ? k : nextVisibleOrder[vi++]));
}

/**
 * @param {string[]} dimensionOrder
 * @param {Set<string>} studySingletonKeys
 */
export function matrixVisibleDimensionOrder(dimensionOrder, studySingletonKeys) {
  const singletons = studySingletonKeys || new Set();
  return (dimensionOrder || []).filter((k) => !singletons.has(k));
}
