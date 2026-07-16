/** Shared catalog indicator filter query keys (chart + table tabs). */

export const Q_FROM = 'from';
export const Q_TO = 'to';
export const Q_GEO = 'geo';
export const Q_PERIOD = 'period';

/** Query key for a dimension column, e.g. `c[Sex_Code]`. */
export function dimQueryKey(colName) {
  return `c[${String(colName || '')}]`;
}

/** Split one query param value into codes (comma-separated, each segment URI-encoded). */
export function splitCommaEncodedDimValues(raw) {
  if (raw == null || String(raw).trim() === '') return [];
  return String(raw)
    .split(',')
    .map((x) => x.trim())
    .filter(Boolean)
    .map((x) => {
      try {
        return decodeURIComponent(x.replace(/\+/g, ' '));
      } catch {
        return x;
      }
    });
}

/** Join selected codes for a single dimension query param. */
export function joinCommaEncodedDimValues(values) {
  return values
    .map((v) => String(v ?? '').trim())
    .filter(Boolean)
    .map((v) => encodeURIComponent(v))
    .join(',');
}

/** Unique codes from `key` (comma-separated values and/or repeated `key=` entries). */
export function readCodesFromQueryParam(q, key) {
  if (!q.has(key)) return [];
  const seen = new Set();
  const out = [];
  for (const segment of q.getAll(key)) {
    for (const code of splitCommaEncodedDimValues(segment)) {
      const s = String(code ?? '').trim();
      if (!s || seen.has(s)) continue;
      seen.add(s);
      out.push(s);
    }
  }
  return out;
}

/**
 * @param {URLSearchParams} q
 * @param {{
 *   filterDraft: { from: string; to: string; dGeography: string[]; dPeriodicity: string[]; c: Record<string, string[]> };
 *   geographyComponent?: { name?: string } | null;
 *   periodicityComponent?: { name?: string } | null;
 *   facetComponentsForC?: Array<{ name?: string }>;
 * }} ctx
 */
export function readIndicatorFiltersFromQuery(q, ctx) {
  const { filterDraft, geographyComponent, periodicityComponent, facetComponentsForC = [] } = ctx;
  if (q.has(Q_FROM)) {
    filterDraft.from = String(q.get(Q_FROM) ?? '').trim();
  }
  if (q.has(Q_TO)) {
    filterDraft.to = String(q.get(Q_TO) ?? '').trim();
  }
  if (q.has(Q_GEO) && geographyComponent) {
    filterDraft.dGeography.splice(0, filterDraft.dGeography.length, ...readCodesFromQueryParam(q, Q_GEO));
  }
  if (q.has(Q_PERIOD) && periodicityComponent) {
    filterDraft.dPeriodicity.splice(0, filterDraft.dPeriodicity.length, ...readCodesFromQueryParam(q, Q_PERIOD));
  }
  for (const col of facetComponentsForC) {
    const k = dimQueryKey(col.name);
    if (q.has(k) && col.name in filterDraft.c) {
      filterDraft.c[col.name] = readCodesFromQueryParam(q, k);
    }
  }
}

/** @param {URLSearchParams} q */
export function urlHasIndicatorFilterParams(q) {
  if (q.has(Q_FROM) && String(q.get(Q_FROM) ?? '').trim() !== '') return true;
  if (q.has(Q_TO) && String(q.get(Q_TO) ?? '').trim() !== '') return true;
  if (readCodesFromQueryParam(q, Q_GEO).length) return true;
  if (readCodesFromQueryParam(q, Q_PERIOD).length) return true;
  for (const key of q.keys()) {
    if (/^c\[[^\]]+\]$/.test(key) && splitCommaEncodedDimValues(q.get(key)).length) return true;
  }
  return false;
}

/**
 * @param {{
 *   filterDraft: { from: string; to: string; dGeography: string[]; dPeriodicity: string[]; c: Record<string, string[]> };
 *   geographyComponent?: { name?: string } | null;
 *   periodicityComponent?: { name?: string } | null;
 *   facetComponentsForC?: Array<{ name?: string }>;
 *   facetShowsFilter?: (componentName: string) => boolean;
 * }} ctx
 */
export function buildIndicatorFilterQueryParams(ctx) {
  const {
    filterDraft,
    geographyComponent,
    periodicityComponent,
    facetComponentsForC = [],
    facetShowsFilter = () => true,
  } = ctx;
  const p = new URLSearchParams();
  const fr = String(filterDraft.from || '').trim();
  const to = String(filterDraft.to || '').trim();
  if (fr) p.set(Q_FROM, fr);
  if (to) p.set(Q_TO, to);
  const geo = geographyComponent;
  if (geo && facetShowsFilter(geo.name)) {
    const geoCodes = [...new Set(filterDraft.dGeography.map((v) => String(v ?? '').trim()).filter(Boolean))];
    const geoJoined = joinCommaEncodedDimValues(geoCodes);
    if (geoJoined) p.set(Q_GEO, geoJoined);
  }
  const per = periodicityComponent;
  if (per && facetShowsFilter(per.name)) {
    const periodCodes = [...new Set(filterDraft.dPeriodicity.map((v) => String(v ?? '').trim()).filter(Boolean))];
    const periodJoined = joinCommaEncodedDimValues(periodCodes);
    if (periodJoined) p.set(Q_PERIOD, periodJoined);
  }
  for (const col of facetComponentsForC) {
    if (!facetShowsFilter(col.name)) continue;
    const arr = filterDraft.c[col.name];
    if (!Array.isArray(arr) || !arr.length) continue;
    const k = dimQueryKey(col.name);
    const joined = joinCommaEncodedDimValues(arr);
    if (joined) p.set(k, joined);
  }
  return p;
}

/** Remove chart/table shared filter keys from search params. */
export function stripIndicatorFilterQueryKeys(sp) {
  for (const key of [...sp.keys()]) {
    if (
      key === Q_FROM ||
      key === Q_TO ||
      key === Q_GEO ||
      key === Q_PERIOD ||
      key.startsWith('c_') ||
      /^c\[[^\]]+\]$/.test(key)
    ) {
      sp.delete(key);
    }
  }
}
