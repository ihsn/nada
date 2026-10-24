import { createRouter, createWebHashHistory } from 'vue-router';
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
    meta: { title: 'Overview', navIcon: 'mdi-view-dashboard' },
  },
  {
    path: '/pageviews',
    name: 'pageviews',
    component: RawPageviewsPage,
    meta: { title: 'Raw pageviews', navIcon: 'mdi-eye' },
  },
  {
    path: '/downloads',
    name: 'downloads',
    component: RawDownloadsPage,
    meta: { title: 'Raw downloads', navIcon: 'mdi-download' },
  },
  {
    path: '/daily',
    name: 'daily',
    component: DailyDataPage,
    meta: { title: 'Daily data', navIcon: 'mdi-calendar-today' },
  },
  {
    path: '/monthly',
    name: 'monthly',
    component: MonthlyDataPage,
    meta: { title: 'Monthly data', navIcon: 'mdi-calendar-month' },
  },
  {
    path: '/aggregations',
    name: 'aggregations',
    component: AggregationsPage,
    meta: { title: 'Aggregations', navIcon: 'mdi-cog' },
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

router.afterEach((to) => {
  const title = to.meta?.title;
  if (title) {
    document.title = `${title} — Analytics`;
  }
});

export default router;
