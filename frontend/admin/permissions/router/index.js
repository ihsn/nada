import { createRouter, createWebHashHistory } from 'vue-router';
import RolesListPage from '../pages/RolesListPage.vue';
import RoleEditPage from '../pages/RoleEditPage.vue';
import RolePermissionsPage from '../pages/RolePermissionsPage.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', name: 'roles-list', component: RolesListPage },
    {
      path: '/roles/:id/edit',
      name: 'role-edit',
      component: RoleEditPage,
      props: true,
    },
    {
      path: '/roles/:id/permissions',
      name: 'role-permissions',
      component: RolePermissionsPage,
      props: true,
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
});
