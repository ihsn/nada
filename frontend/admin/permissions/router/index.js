import { createRouter, createWebHashHistory } from 'vue-router';
import RolesListPage from '../pages/RolesListPage.vue';
import RolePage from '../pages/RolePage.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', name: 'roles-list', component: RolesListPage },
    { path: '/roles/:id(\\d+)', name: 'role', component: RolePage, props: true },
    { path: '/roles/:id(\\d+)/edit', redirect: (to) => ({ name: 'role', params: { id: to.params.id } }) },
    { path: '/roles/:id(\\d+)/permissions', redirect: (to) => ({ name: 'role', params: { id: to.params.id } }) },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
});
