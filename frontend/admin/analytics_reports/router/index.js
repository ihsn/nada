import { createRouter, createWebHashHistory } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import OverviewPage from '../pages/OverviewPage.vue';
import RawPageviewsPage from '../pages/RawPageviewsPage.vue';
import RawDownloadsPage from '../pages/RawDownloadsPage.vue';
import DailyDataPage from '../pages/DailyDataPage.vue';
import MonthlyDataPage from '../pages/MonthlyDataPage.vue';
import AggregationsPage from '../pages/AggregationsPage.vue';

const routes = [
  { path: '/', redirect: '/overview' },
  {
    path: '/overview',
    name: 'overview',
    component: OverviewPage,
    meta: { title: 'Overview', titleKey: 'analytics_overview', navIcon: 'mdi-view-dashboard' },
  },
  {
    path: '/pageviews',
    name: 'pageviews',
    component: RawPageviewsPage,
    meta: { title: 'Raw pageviews', titleKey: 'analytics_raw_pageviews', navIcon: 'mdi-eye' },
  },
  {
    path: '/downloads',
    name: 'downloads',
    component: RawDownloadsPage,
    meta: { title: 'Raw downloads', titleKey: 'analytics_raw_downloads', navIcon: 'mdi-download' },
  },
  {
    path: '/daily',
    name: 'daily',
    component: DailyDataPage,
    meta: { title: 'Daily data', titleKey: 'analytics_daily_data', navIcon: 'mdi-calendar-today' },
  },
  {
    path: '/monthly',
    name: 'monthly',
    component: MonthlyDataPage,
    meta: { title: 'Monthly data', titleKey: 'analytics_monthly_data', navIcon: 'mdi-calendar-month' },
  },
  {
    path: '/aggregations',
    name: 'aggregations',
    component: AggregationsPage,
    meta: { title: 'Aggregations', titleKey: 'analytics_aggregations', navIcon: 'mdi-cog' },
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

const { t } = useI18n();

router.afterEach((to) => {
  const title = to.meta?.title;
  if (title) {
    document.title = `${t(to.meta.titleKey, title)} — ${t('Analytics', 'Analytics')}`;
  }
});

export default router;
