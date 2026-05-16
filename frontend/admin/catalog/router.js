import { createRouter, createWebHistory } from 'vue-router';
import CatalogListPage from './pages/CatalogListPage.vue';
import AdminCatalogBatchPage from './pages/AdminCatalogBatchPage.vue';

/**
 * History base must match the browser pathname prefix. PHP's site_url() may include
 * index.php while rewritten URLs do not — using only routerPathBase breaks matching.
 */
function adminCatalogHistoryBase() {
  if (typeof window !== 'undefined' && window.location && window.location.pathname) {
    let p = window.location.pathname;
    p = p.replace(/\/+$/, '') || '/';
    p = p.replace(/\/batch-(import|refresh|generate)$/, '') || '/';
    return `${p}/`;
  }
  const raw =
    typeof window !== 'undefined' && window.APP_CONFIG && window.APP_CONFIG.routerPathBase
      ? String(window.APP_CONFIG.routerPathBase)
      : '/admin/catalog';
  return `${raw.replace(/\/$/, '')}/`;
}

export function createAdminCatalogRouter() {
  const base = adminCatalogHistoryBase();

  return createRouter({
    history: createWebHistory(base),
    routes: [
      {
        path: '/',
        name: 'catalog-list',
        component: CatalogListPage,
      },
      {
        path: '/batch-import',
        name: 'batch-import',
        component: AdminCatalogBatchPage,
        props: { tool: 'batch-import' },
      },
      {
        path: '/batch-refresh',
        name: 'batch-refresh',
        component: AdminCatalogBatchPage,
        props: { tool: 'batch-refresh' },
      },
      {
        path: '/batch-generate',
        name: 'batch-generate',
        component: AdminCatalogBatchPage,
        props: { tool: 'batch-generate' },
      },
    ],
  });
}
