export function clamp(n, lo, hi) {
  return Math.min(Math.max(n, lo), hi);
}

/** First calendar year in a filter or bound string (ISO or leading digits). */
export function parseLeadingCalendarYear(s) {
  if (s == null || String(s).trim() === '') return null;
  const m = String(s).trim().match(/^(\d{4})\b/);
  if (!m) return null;
  const y = Number(m[1]);
  return Number.isFinite(y) ? y : null;
}

/** Min/max reporting years from schema `reporting_year_bounds`. */
export function reportingYearBoundsFromSchema(sch) {
  const rb = sch?.reporting_year_bounds;
  if (!rb || typeof rb !== 'object') return null;
  if (rb.min == null || rb.max == null) return null;
  const mn = typeof rb.min === 'string' && /^\d+$/.test(rb.min.trim()) ? Number(rb.min.trim()) : Number(rb.min);
  const mx = typeof rb.max === 'string' && /^\d+$/.test(rb.max.trim()) ? Number(rb.max.trim()) : Number(rb.max);
  if (!Number.isFinite(mn) || !Number.isFinite(mx) || mn <= 0 || mx <= 0 || mn > mx) return null;
  return { min: Math.trunc(mn), max: Math.trunc(mx) };
}

export function resolveTimeBoundsYears(schema, timeBounds) {
  let fromMetadata = null;
  if (timeBounds) {
    const mn = parseLeadingCalendarYear(timeBounds.min);
    const mx = parseLeadingCalendarYear(timeBounds.max);
    if (mn != null && mx != null && mn <= mx) {
      fromMetadata = { min: mn, max: mx };
    }
  }
  const fromSchema = reportingYearBoundsFromSchema(schema);
  if (fromSchema && fromMetadata) {
    return {
      min: Math.min(fromSchema.min, fromMetadata.min),
      max: Math.max(fromSchema.max, fromMetadata.max),
    };
  }
  return fromSchema || fromMetadata || null;
}

export const QUARTER_OPTIONS = [
  { title: 'Q1', value: 1 },
  { title: 'Q2', value: 2 },
  { title: 'Q3', value: 3 },
  { title: 'Q4', value: 4 },
];

export const MONTH_OPTIONS = [
  { title: 'Jan', value: 1 },
  { title: 'Feb', value: 2 },
  { title: 'Mar', value: 3 },
  { title: 'Apr', value: 4 },
  { title: 'May', value: 5 },
  { title: 'Jun', value: 6 },
  { title: 'Jul', value: 7 },
  { title: 'Aug', value: 8 },
  { title: 'Sep', value: 9 },
  { title: 'Oct', value: 10 },
  { title: 'Nov', value: 11 },
  { title: 'Dec', value: 12 },
];

export function parseSubPeriodStr(value, mode) {
  const s = String(value ?? '').trim();
  if (!s) return { year: null, sub: null };
  if (mode === 'quarterly') {
    const m = s.match(/^(\d{4})-Q([1-4])$/i);
    if (m) return { year: Number(m[1]), sub: Number(m[2]) };
  }
  if (mode === 'monthly') {
    const m = s.match(/^(\d{4})-(\d{2})$/);
    if (m) {
      const sub = Number(m[2]);
      if (sub >= 1 && sub <= 12) return { year: Number(m[1]), sub };
    }
  }
  const yr = s.match(/^(\d{4})/);
  if (yr) return { year: Number(yr[1]), sub: null };
  return { year: null, sub: null };
}
