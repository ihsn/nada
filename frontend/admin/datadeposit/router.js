import { createRouter, createWebHistory } from 'vue-router';
import AdminDepositListPage from './pages/AdminDepositListPage.vue';
import AdminDepositWorkspacePage from './pages/AdminDepositWorkspacePage.vue';
import AdminDepositSummaryPage from './pages/AdminDepositSummaryPage.vue';
import AdminDepositAssignPage from './pages/AdminDepositAssignPage.vue';
import AdminDepositTasksPage from './pages/AdminDepositTasksPage.vue';
import AdminDepositMyTasksPage from './pages/AdminDepositMyTasksPage.vue';
import AdminDepositTaskDetailPage from './pages/AdminDepositTaskDetailPage.vue';

const WORKSPACE_TABS = 'info|process|files|communicate|history';

/**
 * History base must match the browser pathname prefix. PHP's site_url() may include
 * index.php while rewritten URLs do not. Strip /projects/:id/:tab so list and workspace
 * share /admin/datadeposit/.
 */
function adminDepositHistoryBase() {
  if (typeof window !== 'undefined' && window.location && window.location.pathname) {
    let p = window.location.pathname;
    p = p.replace(/\/+$/, '') || '/';
    p = p.replace(/\/projects\/\d+(?:\/[^/]+)?$/, '') || '/';
    p = p.replace(/\/summary\/\d+$/, '') || '/';
    p = p.replace(/\/assign\/\d+$/, '') || '/';
    p = p.replace(/\/tasks\/info\/\d+$/, '') || '/';
    p = p.replace(/\/tasks\/my_tasks$/, '') || '/';
    p = p.replace(/\/tasks$/, '') || '/';
    return `${p}/`;
  }
  const raw =
    typeof window !== 'undefined' && window.APP_CONFIG && window.APP_CONFIG.routerPathBase
      ? String(window.APP_CONFIG.routerPathBase)
      : '/admin/datadeposit';
  return `${raw.replace(/\/$/, '')}/`;
}

export function createAdminDepositRouter() {
  const base = adminDepositHistoryBase();

  return createRouter({
    history: createWebHistory(base),
    routes: [
      {
        path: '/',
        name: 'admin-deposit-list',
        component: AdminDepositListPage,
      },
      {
        path: '/tasks',
        name: 'admin-deposit-tasks',
        component: AdminDepositTasksPage,
      },
      {
        path: '/tasks/my_tasks',
        name: 'admin-deposit-my-tasks',
        component: AdminDepositMyTasksPage,
      },
      {
        path: '/tasks/info/:id',
        name: 'admin-deposit-task',
        component: AdminDepositTaskDetailPage,
        props: true,
      },
      {
        path: '/projects/:id/assign',
        name: 'admin-deposit-assign',
        component: AdminDepositAssignPage,
        props: true,
      },
      {
        path: '/assign/:id',
        name: 'admin-deposit-assign-legacy',
        component: AdminDepositAssignPage,
        props: true,
      },
      {
        path: `/projects/:id/:tab(${WORKSPACE_TABS})`,
        name: 'admin-deposit-workspace-tab',
        component: AdminDepositWorkspacePage,
        props: true,
      },
      {
        path: '/projects/:id/:tab',
        redirect: (to) => ({
          name: 'admin-deposit-workspace',
          params: { id: to.params.id },
          replace: true,
        }),
      },
      {
        path: '/projects/:id',
        name: 'admin-deposit-workspace',
        component: AdminDepositWorkspacePage,
        props: (route) => ({ id: route.params.id }),
      },
      {
        path: '/summary/:id',
        name: 'admin-deposit-summary',
        component: AdminDepositSummaryPage,
        props: true,
      },
    ],
  });
}
