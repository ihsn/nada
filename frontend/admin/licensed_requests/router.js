import { createRouter, createWebHistory } from 'vue-router';
import LicensedRequestsListPage from './pages/LicensedRequestsListPage.vue';
import LicensedRequestEditPage from './pages/LicensedRequestEditPage.vue';

function historyBase() {
  if (typeof window !== 'undefined' && window.location && window.location.pathname) {
    let p = window.location.pathname;
    p = p.replace(/\/+$/, '') || '/';
    // Strip /edit/:id so both list and edit URLs use base /admin/licensed_requests/
    // Strip /edit/:id or /edit/:id/:tab so base works for both list and edit URLs
    p = p.replace(/\/edit\/[^/]+(?:\/[^/]+)?$/, '');
    return `${p}/`;
  }
  const raw =
    typeof window !== 'undefined' && window.APP_CONFIG && window.APP_CONFIG.routerPathBase
      ? String(window.APP_CONFIG.routerPathBase)
      : '/admin/licensed_requests';
  return `${raw.replace(/\/$/, '')}/`;
}

export function createLicensedRequestsRouter() {
  const base = historyBase();

  return createRouter({
    history: createWebHistory(base),
    routes: [
      {
        path: '/',
        name: 'licensed-requests-list',
        component: LicensedRequestsListPage,
      },
      {
        path: '/edit/:id/:tab(info|process|comm|mon|fwd)',
        name: 'licensed-request-edit-tab',
        component: LicensedRequestEditPage,
        props: true,
      },
      {
        path: '/edit/:id/:tab',
        redirect: (to) => ({
          name: 'licensed-request-edit',
          params: { id: to.params.id },
          replace: true,
        }),
      },
      {
        path: '/edit/:id',
        name: 'licensed-request-edit',
        component: LicensedRequestEditPage,
        props: (route) => ({ id: route.params.id }),
      },
    ],
  });
}
