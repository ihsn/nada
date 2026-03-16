import { createRouter, createWebHashHistory } from 'vue-router';
import CodelistListPage from '../pages/CodelistListPage.vue';
import CodelistDetailPage from '../pages/CodelistDetailPage.vue';

const routes = [
  {
    path: '/',
    name: 'codelists',
    component: CodelistListPage,
    meta: { title: 'Codelists' },
  },
  {
    path: '/codelist/:id',
    name: 'codelist-detail',
    component: CodelistDetailPage,
    meta: { title: 'Codelist' },
    props: true,
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
