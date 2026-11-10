export function formatNumber(n) {
  if (n === null || n === undefined || n === '') return '0';
  return new Intl.NumberFormat().format(Number(n));
}

/** Local date string (YYYY-MM-DD) to a Unix timestamp at the start or end of that day. */
export function dateToUnix(dateStr, endOfDay = false) {
  if (!dateStr) return undefined;
  const d = new Date(`${dateStr}T00:00:00`);
  if (Number.isNaN(d.getTime())) return undefined;
  if (endOfDay) d.setHours(23, 59, 59, 999);
  return Math.floor(d.getTime() / 1000);
}
