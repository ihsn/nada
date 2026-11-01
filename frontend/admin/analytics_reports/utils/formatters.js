import { useI18n } from '@/shared/composables/useI18n';

const { t } = useI18n();

export function analyticsLocale() {
  return (typeof window !== 'undefined' && window.APP_CONFIG?.analyticsLocale) || 'en-US';
}

export function fmtNum(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return '0';
  return analyticsLocale() === 'pt-PT' ? n.toLocaleString('pt-PT') : n.toLocaleString();
}

/** Admin study editor (surveys.id / study_id), not public catalog. */
export function adminStudyEditUrl(siteUrl, row) {
  const sid = row?.study_id;
  if (sid === null || sid === undefined || sid === '') return '#';
  const base = String(siteUrl || '').replace(/\/+$/, '');
  return `${base}/admin/catalog/edit/${sid}`;
}

export function timeAgo(dateString) {
  if (!dateString) return t('analytics_time_never', 'never');
  const diff = (Date.now() - new Date(dateString).getTime()) / 1000;
  if (diff < 60) return t('analytics_time_now', 'just now');
  if (diff < 3600) return t('analytics_time_minutes', '%sm ago', Math.floor(diff / 60));
  if (diff < 86400) return t('analytics_time_hours', '%sh ago', Math.floor(diff / 3600));
  const days = Math.floor(diff / 86400);
  return t(days === 1 ? 'analytics_time_day' : 'analytics_time_days', '%sd ago', days);
}

export function formatMonthLabel(row) {
  if (analyticsLocale() !== 'pt-PT') return row?.label || '';
  const year = Number(row?.year);
  const month = Number(row?.month);
  if (!Number.isInteger(year) || year < 1 || !Number.isInteger(month) || month < 1 || month > 12) {
    return row?.label || '';
  }
  return new Date(Date.UTC(year, month - 1, 1)).toLocaleDateString('pt-PT', {
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
  });
}

export function formatDateTime(dateString, timeZone) {
  if (!dateString) return '—';
  try {
    return new Date(dateString).toLocaleString(analyticsLocale(), timeZone ? { timeZone } : undefined);
  } catch {
    return new Date(dateString).toLocaleString();
  }
}

export function formatTimeOnly(dateString, timeZone) {
  if (!dateString) return '—';
  try {
    return new Date(dateString).toLocaleTimeString(analyticsLocale(), {
      timeZone,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  } catch {
    return new Date(dateString).toLocaleTimeString();
  }
}
