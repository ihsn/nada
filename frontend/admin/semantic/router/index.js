import { createRouter, createWebHashHistory } from 'vue-router';
import OverviewPage from '../pages/OverviewPage.vue';
import SearchTestPage from '../pages/SearchTestPage.vue';
import ActivityPage from '../pages/ActivityPage.vue';
import IndexingPage from '../pages/IndexingPage.vue';
export default createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      component: OverviewPage,
      name: 'overview',
      meta: {
        title: 'Overview',
        subtitle: 'Is semantic search working, and what can be found with it',
      },
    },
    {
      path: '/indexing',
      component: IndexingPage,
      name: 'indexing',
      meta: {
        title: 'Indexing',
        subtitle: 'Keep the search index in step with the catalog',
      },
    },
    {
      path: '/activity',
      component: ActivityPage,
      name: 'activity',
      meta: {
        title: 'Activity',
        subtitle: 'Indexing jobs, and catalog edits waiting to be applied',
      },
    },
    {
      path: '/search',
      component: SearchTestPage,
      name: 'search',
      meta: {
        title: 'Test search',
        subtitle: 'Run a query against the live index',
      },
    },
    // Earlier addresses of pages that moved, so bookmarks still land somewhere sensible.
    { path: '/diff', redirect: (to) => ({ name: 'indexing', query: to.query }) },
    { path: '/jobs', redirect: { name: 'activity', query: { tab: 'jobs' } } },
    { path: '/sync', redirect: { name: 'activity', query: { tab: 'queue' } } },
    { path: '/danger', redirect: { name: 'indexing', hash: '#maintenance' } },
    { path: '/collection', redirect: { name: 'overview' } },
  ],
});
