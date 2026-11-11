import { createRouter, createWebHistory } from 'vue-router';
import UsersListPage from './pages/UsersListPage.vue';
import UserFormPage from './pages/UserFormPage.vue';
import ApiKeysPage from './pages/ApiKeysPage.vue';
import UserApiKeysPage from './pages/UserApiKeysPage.vue';

function historyBase() {
  const raw =
    typeof window !== 'undefined' && window.APP_CONFIG && window.APP_CONFIG.routerPathBase
      ? String(window.APP_CONFIG.routerPathBase)
      : '/admin/users';
  return `${raw.replace(/\/$/, '')}/`;
}

export function createUsersRouter() {
  return createRouter({
    history: createWebHistory(historyBase()),
    routes: [
      { path: '/', name: 'users-list', component: UsersListPage },
      { path: '/add', name: 'user-create', component: UserFormPage },
      { path: '/edit/:id(\\d+)', name: 'user-edit', component: UserFormPage, props: true },
      { path: '/edit/:id(\\d+)/api_keys', name: 'user-api-keys', component: UserApiKeysPage, props: true },
      { path: '/api_keys', name: 'api-keys', component: ApiKeysPage },
      { path: '/:pathMatch(.*)*', redirect: { name: 'users-list' } },
    ],
  });
}
