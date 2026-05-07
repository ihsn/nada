import { createRouter, createWebHashHistory } from 'vue-router';
import SiteConfigurationsPage from '../pages/SiteConfigurationsPage.vue';
import { SITE_CONFIG_SECTION_IDS } from '../sections';

const routes = [
  {
    path: '/',
    redirect: { name: 'site-config-section', params: { section: 'general' } },
  },
  {
    path: '/test-email',
    redirect: { name: 'site-config-section', params: { section: 'test_email' }, replace: true },
  },
  {
    path: '/:section',
    name: 'site-config-section',
    component: SiteConfigurationsPage,
    beforeEnter: (to, _from, next) => {
      const id = to.params.section;
      if (typeof id !== 'string' || !SITE_CONFIG_SECTION_IDS.has(id)) {
        return next({ name: 'site-config-section', params: { section: 'general' }, replace: true });
      }
      next();
    },
  },
];

export default createRouter({
  history: createWebHashHistory(),
  routes,
});
