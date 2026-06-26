import { createRouter, createWebHistory } from 'vue-router';
import BulkDataAccessListPage from './pages/BulkDataAccessListPage.vue';
import BulkDataAccessEditPage from './pages/BulkDataAccessEditPage.vue';
import BulkDataAccessAttachPage from './pages/BulkDataAccessAttachPage.vue';

function historyBase() {
  if (typeof window !== 'undefined' && window.location && window.location.pathname) {
    let p = window.location.pathname;
    p = p.replace(/\/+$/, '') || '/';
    p = p.replace(/\/attach_studies\/[^/]+$/, '');
    p = p.replace(/\/edit\/[^/]+$/, '');
    p = p.replace(/\/add$/, '');
    return `${p}/`;
  }
  const raw =
    typeof window !== 'undefined' && window.APP_CONFIG && window.APP_CONFIG.routerPathBase
      ? String(window.APP_CONFIG.routerPathBase)
      : '/admin/da_collections';
  return `${raw.replace(/\/$/, '')}/`;
}

export function createBulkDataAccessRouter() {
  const base = historyBase();

  return createRouter({
    history: createWebHistory(base),
    routes: [
      {
        path: '/',
        name: 'bda-list',
        component: BulkDataAccessListPage,
      },
      {
        path: '/add',
        name: 'bda-add',
        component: BulkDataAccessEditPage,
        props: { isNew: true },
      },
      {
        path: '/edit/:id',
        name: 'bda-edit',
        component: BulkDataAccessEditPage,
        props: (route) => ({ id: route.params.id, isNew: false }),
      },
      {
        path: '/attach_studies/:id',
        name: 'bda-attach',
        component: BulkDataAccessAttachPage,
        props: (route) => ({ id: route.params.id }),
      },
    ],
  });
}
