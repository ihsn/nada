import { createRouter, createWebHashHistory } from 'vue-router';
import OverviewPage from '../pages/OverviewPage.vue';
import SearchTestPage from '../pages/SearchTestPage.vue';
import JobsPage from '../pages/JobsPage.vue';
import SyncStatusPage from '../pages/SyncStatusPage.vue';
import DiffPage from '../pages/DiffPage.vue';
import CollectionInfoPage from '../pages/CollectionInfoPage.vue';
import DangerZonePage from '../pages/DangerZonePage.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      component: OverviewPage,
      name: 'overview',
      meta: {
        title: 'Overview',
        subtitle: 'Coverage and health of the semantic search index',
      },
    },
    {
      path: '/search',
      component: SearchTestPage,
      name: 'search',
      meta: {
        title: 'Try search',
        subtitle: 'Run a query against the live index',
      },
    },
    {
      path: '/jobs',
      component: JobsPage,
      name: 'jobs',
      meta: {
        title: 'Jobs',
        subtitle: 'Background ingest and reconcile jobs',
      },
    },
    {
      path: '/sync',
      component: SyncStatusPage,
      name: 'sync',
      meta: {
        title: 'Change queue',
        subtitle: 'Pending catalog edits waiting to be indexed',
      },
    },
    {
      path: '/diff',
      component: DiffPage,
      name: 'diff',
      meta: {
        title: 'Index',
        subtitle: 'Coverage and repair of the catalog vs the index',
      },
    },
    {
      path: '/collection',
      component: CollectionInfoPage,
      name: 'collection',
      meta: {
        title: 'Collection',
        subtitle: 'Read-only Qdrant collection diagnostics',
      },
    },
    {
      path: '/danger',
      component: DangerZonePage,
      name: 'danger',
      meta: {
        title: 'Danger zone',
        subtitle: 'Destructive index operations — there is no undo',
      },
    },
  ],
});
