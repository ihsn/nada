/** How many geography labels to show before "+ N more". */
export const FACET_GEOGRAPHY_CONTEXT_PREVIEW = 3;

/**
 * Resolve facet codes to display labels using loaded codelists.
 *
 * @param {Record<string, Array<{ value?: string; title?: string }>>} codelistSelectItems
 * @param {string} componentName
 * @param {string[]} codes
 */
export function facetCodesDisplayLabels(codelistSelectItems, componentName, codes) {
  if (!Array.isArray(codes) || !codes.length) return [];
  const items = codelistSelectItems?.[componentName] || [];
  return codes.map((code) => {
    const c = String(code ?? '').trim();
    const hit = items.find((it) => String(it.value) === c);
    return hit?.title ? String(hit.title) : c;
  });
}

/**
 * @param {{ name?: string; column_type?: string } | null | undefined} component
 */
export function isGeographyLikeComponent(component) {
  if (!component?.name) return false;
  if (component.column_type === 'geography') return true;
  const name = String(component.name).trim().toUpperCase();
  return name === 'REF_AREA' || name === 'GEO' || name === 'GEOGRAPHY';
}

/**
 * Geography slice text for chart subtitle / table context (matches chart title rules).
 *
 * @param {Record<string, Array<{ value?: string; title?: string }>>} codelistSelectItems
 * @param {string} componentName
 * @param {string[]} codes
 */
export function formatGeographyContextValue(codelistSelectItems, componentName, codes) {
  const list = Array.isArray(codes) ? codes.map((c) => String(c ?? '').trim()).filter(Boolean) : [];
  if (!list.length) return '';

  const labels = facetCodesDisplayLabels(codelistSelectItems, componentName, list);
  if (list.length <= FACET_GEOGRAPHY_CONTEXT_PREVIEW) {
    return labels.join(', ');
  }

  const shown = labels.slice(0, FACET_GEOGRAPHY_CONTEXT_PREVIEW);
  const remaining = list.length - FACET_GEOGRAPHY_CONTEXT_PREVIEW;
  return `${shown.join(', ')} + ${remaining} more`;
}

/**
 * @param {{ name?: string; column_type?: string } | null | undefined} component
 * @param {(component: { name?: string }) => string} facetLabel
 */
export function facetContextHeader(component, facetLabel) {
  if (!component) return 'Geography';
  const label = facetLabel(component);
  if (label && label !== component.name) return label;
  if (isGeographyLikeComponent(component)) return 'Country/area code';
  return label || component.name || '';
}

/**
 * @param {object | null | undefined} activeFilters
 * @param {{ name?: string } | null | undefined} geographyComponent
 */
export function resolveActiveGeographyCodes(activeFilters, geographyComponent) {
  const f = activeFilters;
  if (!f) return { codes: [], componentName: '' };

  const geoName = String(geographyComponent?.name ?? '').trim();
  const d = f.d && typeof f.d === 'object' ? f.d : {};
  const c = f.c && typeof f.c === 'object' ? f.c : {};

  if (Array.isArray(d.geography) && d.geography.length) {
    return { codes: d.geography.map(String), componentName: geoName || 'geography' };
  }

  if (geoName && Array.isArray(c[geoName]) && c[geoName].length) {
    return { codes: c[geoName].map(String), componentName: geoName };
  }

  return { codes: [], componentName: geoName };
}

/**
 * One-line summary of applied indicator filters (chart subtitle / table context).
 * Pass facetShowsFilter to omit study singletons; default includes all applied facets.
 *
 * @param {{
 *   activeFilters: object | null;
 *   geographyComponent?: { name?: string; column_type?: string } | null;
 *   periodicityComponent?: { name?: string } | null;
 *   facetComponentsForC?: Array<{ name?: string; column_type?: string }>;
 *   codelistSelectItems?: Record<string, Array<{ value?: string; title?: string }>>;
 *   facetLabel: (component: { name?: string }) => string;
 *   facetShowsFilter?: (componentName: string) => boolean;
 *   timeBoundsFallback?: { min?: unknown; max?: unknown } | null;
 * }} ctx
 */
export function buildIndicatorFilterContextLine(ctx) {
  const f = ctx.activeFilters;
  if (!f) return '';

  const parts = [];

  const from = typeof f.from === 'string' ? f.from.trim() : '';
  const to = typeof f.to === 'string' ? f.to.trim() : '';
  if (from && to) parts.push(`${from}–${to}`);
  else if (from) parts.push(`From ${from}`);
  else if (to) parts.push(`To ${to}`);
  else {
    const tb = ctx.timeBoundsFallback;
    if (tb?.min != null && tb?.max != null) {
      const a = String(tb.min).trim();
      const b = String(tb.max).trim();
      if (a && b) parts.push(`${a}–${b}`);
    }
  }

  const itemsByName = ctx.codelistSelectItems || {};
  const facetShowsFilter = ctx.facetShowsFilter || (() => true);
  const facetLabel = ctx.facetLabel || ((c) => c?.name || '');

  const geo = ctx.geographyComponent;
  const geoName = String(geo?.name ?? '').trim();
  const { codes: geoCodes, componentName: geoComponentName } = resolveActiveGeographyCodes(f, geo);
  const geoFilterName = geoComponentName || geoName;

  if (geoFilterName && facetShowsFilter(geoFilterName) && geoCodes.length) {
    const value = formatGeographyContextValue(itemsByName, geoFilterName, geoCodes);
    const head = facetContextHeader(geo || { name: geoFilterName }, facetLabel);
    parts.push(`${head}: ${value}`);
  }

  const d = f.d && typeof f.d === 'object' ? f.d : {};
  const per = ctx.periodicityComponent;
  if (per?.name && facetShowsFilter(per.name) && Array.isArray(d.periodicity) && d.periodicity.length) {
    const labels = facetCodesDisplayLabels(itemsByName, per.name, d.periodicity);
    const head = facetLabel(per) || 'Periodicity';
    parts.push(`${head}: ${labels.join(', ')}`);
  }

  const c = f.c && typeof f.c === 'object' ? f.c : {};
  const shownInGeographyBlock = new Set(
    [geoName, geoFilterName, geoComponentName].map((n) => String(n ?? '').trim()).filter(Boolean)
  );

  for (const col of ctx.facetComponentsForC || []) {
    if (!col?.name || !facetShowsFilter(col.name)) continue;
    if (shownInGeographyBlock.has(col.name)) continue;
    const arr = c[col.name];
    if (!Array.isArray(arr) || !arr.length) continue;

    const head = facetContextHeader(col, facetLabel);
    const value = isGeographyLikeComponent(col)
      ? formatGeographyContextValue(itemsByName, col.name, arr)
      : facetCodesDisplayLabels(itemsByName, col.name, arr).join(', ');
    parts.push(`${head}: ${value}`);
  }

  return parts.join(' · ');
}
