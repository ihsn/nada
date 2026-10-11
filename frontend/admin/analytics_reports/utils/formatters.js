export function fmtNum(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return '0';
  return n.toLocaleString();
}

export function catalogStudyUrl(siteUrl, row) {
  const id = row?.idno || row?.study_id;
  if (!id) return '#';
  const base = String(siteUrl || '').replace(/\/+$/, '');
  return `${base}/catalog/${id}`;
}

export function timeAgo(dateString) {
  if (!dateString) return 'never';
  const diff = (Date.now() - new Date(dateString).getTime()) / 1000;
  if (diff < 60) return 'just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return `${Math.floor(diff / 86400)}d ago`;
}

export function formatDateTime(dateString, timeZone) {
  if (!dateString) return '—';
  try {
    return new Date(dateString).toLocaleString('en-US', timeZone ? { timeZone } : undefined);
  } catch {
    return new Date(dateString).toLocaleString();
  }
}

export function formatTimeOnly(dateString, timeZone) {
  if (!dateString) return '—';
  try {
    return new Date(dateString).toLocaleTimeString('en-US', {
      timeZone,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  } catch {
    return new Date(dateString).toLocaleTimeString();
  }
}
