/**
 * Parse surveys.ts_dimensions CSV for timeseries study cards (classic surveys.php).
 * @param {{ type?: string, ts_dimensions?: string }} row
 * @returns {string[]}
 */
export function parseTimeseriesDimensions(row) {
  if (row?.type !== 'timeseries') return [];
  const raw = row?.ts_dimensions;
  if (raw == null || String(raw).trim() === '') return [];
  return String(raw)
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean);
}

/**
 * Parse surveys.ts_frequency for timeseries study cards.
 * @param {{ type?: string, ts_frequency?: string }} row
 * @returns {string} e.g. "Annual" or "Annual, Quarterly" or ""
 */
export function parseTimeseriesFrequency(row) {
  if (row?.type !== 'timeseries') return '';
  const raw = row?.ts_frequency;
  if (raw == null || String(raw).trim() === '') return '';
  return String(raw).trim();
}
