import { useI18n } from '@/shared/composables/useI18n';

const { t } = useI18n();

export const MONTH_OPTIONS = [
  { title: t('analytics_january', 'January'), value: 1 },
  { title: t('analytics_february', 'February'), value: 2 },
  { title: t('analytics_march', 'March'), value: 3 },
  { title: t('analytics_april', 'April'), value: 4 },
  { title: t('analytics_may', 'May'), value: 5 },
  { title: t('analytics_june', 'June'), value: 6 },
  { title: t('analytics_july', 'July'), value: 7 },
  { title: t('analytics_august', 'August'), value: 8 },
  { title: t('analytics_september', 'September'), value: 9 },
  { title: t('analytics_october', 'October'), value: 10 },
  { title: t('analytics_november', 'November'), value: 11 },
  { title: t('analytics_december', 'December'), value: 12 },
];

export const MONTHLY_CHART_RANGE_OPTIONS = [3, 6, 12, 24].map((value) => ({
  title: t('analytics_last_months', 'Last %s months', value),
  value,
}));
