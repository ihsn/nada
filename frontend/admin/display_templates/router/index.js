import { createRouter, createWebHashHistory } from 'vue-router';
import DisplayTemplatesListPage from '../pages/DisplayTemplatesListPage.vue';
import DisplayTemplateDetailPage from '../pages/DisplayTemplateDetailPage.vue';

const routes = [
  {
    path: '/',
    name: 'display-templates',
    component: DisplayTemplatesListPage,
    meta: { title: 'Display template manager' },
  },
  {
    path: '/template/:uid',
    name: 'display-template-detail',
    component: DisplayTemplateDetailPage,
    meta: { title: 'Display template' },
    props: true,
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
