/**
 * @param {string} serverDate Y-m-d
 */
function parseServerDate(serverDate) {
  const s = String(serverDate || '').trim();
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
    return new Date(`${s}T12:00:00`);
  }
  return new Date();
}

function formatYmd(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

/** Single-day range (raw logs default). */
export function defaultRawDateRange(serverDate) {
  const day = formatYmd(parseServerDate(serverDate));
  return { date_from: day, date_to: day, study_id: '' };
}

/** Last 30 calendar days inclusive (daily aggregates default). */
export function defaultDailyDateRange(serverDate) {
  const end = parseServerDate(serverDate);
  const start = new Date(end);
  start.setDate(start.getDate() - 29);
  return {
    date_from: formatYmd(start),
    date_to: formatYmd(end),
    study_id: '',
  };
}

/** Current calendar month (monthly aggregates default). */
export function defaultMonthlyFilters(serverDate) {
  const d = parseServerDate(serverDate);
  return {
    year: d.getFullYear(),
    month: d.getMonth() + 1,
    study_id: '',
  };
}
