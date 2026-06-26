import { createRouter, createWebHistory } from 'vue-router';
import CatalogSearchPage from './pages/CatalogSearchPage.vue';

/**
 * History base must match the browser pathname (includes index.php and optional repo segment).
 * PHP serves the Vue shell at /catalog and /catalog/{repo}.
 */
export function catalogSearchHistoryBase() {
  if (typeof window !== 'undefined' && window.location?.pathname) {
    const p = window.location.pathname.replace(/\/+$/, '') || '/';
    return `${p}/`;
  }
  return '/catalog/';
}

export function createCatalogSearchRouter() {
  return createRouter({
    history: createWebHistory(catalogSearchHistoryBase()),
    routes: [
      {
        path: '/',
        name: 'catalog-search',
        component: CatalogSearchPage,
      },
    ],
  });
}
