import { createRouter, createWebHashHistory } from 'vue-router';
import TemplatesListPage from '../pages/TemplatesListPage.vue';
import TemplateDetailPage from '../pages/TemplateDetailPage.vue';

const routes = [
  {
    path: '/',
    name: 'templates',
    component: TemplatesListPage,
    meta: { title: 'Display templates' },
  },
  {
    path: '/template/:uid',
    name: 'template-detail',
    component: TemplateDetailPage,
    meta: { title: 'Display template' },
    props: true,
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
