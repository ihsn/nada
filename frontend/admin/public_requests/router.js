import { createRouter, createWebHistory } from 'vue-router';
import PublicRequestsListPage from './pages/PublicRequestsListPage.vue';
import PublicRequestPage from './pages/PublicRequestPage.vue';

function historyBase() {
  if (typeof window !== 'undefined' && window.location && window.location.pathname) {
    let p = window.location.pathname;
    p = p.replace(/\/+$/, '') || '/';
    // Strip trailing /:id so list + detail share the same history base
    p = p.replace(/\/\d+$/, '');
    return `${p}/`;
  }
  const raw =
    typeof window !== 'undefined' && window.APP_CONFIG && window.APP_CONFIG.routerPathBase
      ? String(window.APP_CONFIG.routerPathBase)
      : '/admin/public_requests';
  return `${raw.replace(/\/$/, '')}/`;
}

export function createPublicRequestsRouter() {
  const base = historyBase();

  return createRouter({
    history: createWebHistory(base),
    routes: [
      {
        path: '/',
        name: 'public-requests-list',
        component: PublicRequestsListPage,
      },
      {
        path: '/:id(\\d+)',
        name: 'public-request-detail',
        component: PublicRequestPage,
        props: true,
      },
    ],
  });
}
