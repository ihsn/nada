import { createRouter, createWebHistory } from 'vue-router';
import DepositHome from './components/DepositHome.vue';
import DepositCreate from './components/DepositCreate.vue';
import DepositWizard from './DepositWizard.vue';
import DepositSummaryPage from './pages/DepositSummaryPage.vue';
import DepositEmailPage from './pages/DepositEmailPage.vue';

/**
 * History base must match the browser pathname prefix. PHP's site_url() may include
 * index.php while rewritten URLs do not — using only routerPathBase breaks matching.
 */
function depositHistoryBase() {
  if (typeof window !== 'undefined' && window.location && window.location.pathname) {
    let p = window.location.pathname;
    p = p.replace(/\/+$/, '') || '/';
    p = p.replace(/\/email\/[^/]+$/, '');
    p = p.replace(/\/summary\/[^/]+$/, '');
    p = p.replace(/\/study\/[^/]+$/, '');
    p = p.replace(/\/create$/, '');
    p = p.replace(/\/projects$/, '');
    return `${p}/`;
  }
  const raw =
    typeof window !== 'undefined' && window.APP_CONFIG && window.APP_CONFIG.routerPathBase
      ? String(window.APP_CONFIG.routerPathBase)
      : '/datadeposit';
  return `${raw.replace(/\/$/, '')}/`;
}

export function createDepositRouter() {
  return createRouter({
    history: createWebHistory(depositHistoryBase()),
    routes: [
      {
        path: '/',
        name: 'home',
        component: DepositHome,
      },
      {
        path: '/projects',
        name: 'projects',
        component: DepositHome,
      },
      {
        path: '/create',
        name: 'create',
        component: DepositCreate,
      },
      {
        path: '/study/:id',
        name: 'study',
        component: DepositWizard,
      },
      {
        path: '/summary/:id',
        name: 'summary',
        component: DepositSummaryPage,
        props: true,
      },
      {
        path: '/email/:id',
        name: 'email',
        component: DepositEmailPage,
        props: true,
      },
    ],
  });
}
