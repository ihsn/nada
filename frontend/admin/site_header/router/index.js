import { createRouter, createWebHashHistory } from 'vue-router';
import DesignPage from '../pages/DesignPage.vue';
import HtmlPage from '../pages/HtmlPage.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', name: 'design', component: DesignPage },
    { path: '/html', name: 'html', component: HtmlPage },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
});
