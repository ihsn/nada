import { createEmptyTableLayout, TIME_PERIOD_KEY } from './indicatorSchemaUtils.js';
import {
  defaultDimensionOrder,
  defaultHiddenForStudySingletons,
} from './tableLayoutMatrix.js';

/**
 * Build the default table layout.
 *
 * - Columns: time period
 * - Rows: geography first when the study has multiple geographies, then all other
 *   eligible dimensions (excluding study singletons)
 * - hidden_dimensions: study-wide single-value dimensions (never geography)
 *
 * @param {Array<{ key: string; component?: { name?: string }; role?: string }>} eligibleDimensions
 * @param {{
 *   geographyKey?: string;
 *   studyGeographyCount?: number;
 *   studySingletonKeys?: Set<string>;
 * }} [options]
 */
export function buildDefaultTableLayout(eligibleDimensions, options = {}) {
  const layout = createEmptyTableLayout();
  layout.columns = [TIME_PERIOD_KEY];

  const geographyKey = String(options.geographyKey ?? '').trim();
  const studyGeographyCount = Number(options.studyGeographyCount) || 0;
  const studySingletonKeys = options.studySingletonKeys || new Set();

  const rowKeys = [];
  if (geographyKey && studyGeographyCount > 1) {
    rowKeys.push(geographyKey);
  }

  for (const dim of eligibleDimensions || []) {
    if (!dim?.key || dim.key === TIME_PERIOD_KEY) continue;
    if (dim.key === geographyKey) continue;
    if (studySingletonKeys.has(dim.key)) continue;
    rowKeys.push(dim.key);
  }

  layout.rows = rowKeys;

  const allKeys = defaultDimensionOrder(eligibleDimensions);
  if (geographyKey && studyGeographyCount > 1) {
    layout.dimension_order = [geographyKey, ...allKeys.filter((k) => k !== geographyKey)];
  } else {
    layout.dimension_order = allKeys;
  }

  layout.hidden_dimensions = defaultHiddenForStudySingletons(studySingletonKeys);
  return layout;
}

/**
 * Table-tab default filters: first geography, all other facet codes, full year range handled separately.
 *
 * @param {object} filterDraft
 * @param {{
 *   geographyComponent?: { name?: string } | null;
 *   periodicityComponent?: { name?: string } | null;
 *   facetComponentsForC?: Array<{ name?: string }>;
 *   codelistSelectItems?: Record<string, Array<{ value?: string }>>;
 * }} ctx
 */
export function applyDefaultTableFilters(filterDraft, ctx) {
  const itemsByName = ctx.codelistSelectItems || {};

  const geo = ctx.geographyComponent;
  if (geo?.name) {
    const items = itemsByName[geo.name] || [];
    if (items.length && (!filterDraft.dGeography || filterDraft.dGeography.length === 0)) {
      filterDraft.dGeography.splice(0, filterDraft.dGeography.length, String(items[0].value));
    }
  }

  const per = ctx.periodicityComponent;
  if (per?.name) {
    const items = itemsByName[per.name] || [];
    if (items.length && (!filterDraft.dPeriodicity || filterDraft.dPeriodicity.length === 0)) {
      filterDraft.dPeriodicity.splice(
        0,
        filterDraft.dPeriodicity.length,
        ...items.map((it) => String(it.value))
      );
    }
  }

  for (const col of ctx.facetComponentsForC || []) {
    if (!col?.name) continue;
    const items = itemsByName[col.name] || [];
    if (!items.length) continue;
    if (!(col.name in filterDraft.c)) filterDraft.c[col.name] = [];
    filterDraft.c[col.name].splice(
      0,
      filterDraft.c[col.name].length,
      ...items.map((it) => String(it.value))
    );
  }
}
