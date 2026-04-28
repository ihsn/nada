import { createRouter, createWebHashHistory } from 'vue-router';
import DataStructureListPage from '../pages/DataStructureListPage.vue';
import DataStructureCreatePage from '../pages/DataStructureCreatePage.vue';
import DataStructureDetailPage from '../pages/DataStructureDetailPage.vue';
import DataStructureProjectsPage from '../pages/DataStructureProjectsPage.vue';

const routes = [
  {
    path: '/',
    name: 'data-structures',
    component: DataStructureListPage,
    meta: { title: 'Data structures' },
  },
  {
    path: '/create',
    name: 'data-structure-create',
    component: DataStructureCreatePage,
    meta: { title: 'Add data structure' },
  },
  {
    path: '/structure/:id',
    name: 'data-structure-detail',
    component: DataStructureDetailPage,
    meta: { title: 'Data structure' },
    props: true,
  },
  {
    path: '/projects/:id',
    name: 'data-structure-projects',
    component: DataStructureProjectsPage,
    meta: { title: 'Projects using data structure' },
    props: true,
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
