import { createRouter, createWebHashHistory } from 'vue-router';
import OverviewPage from '../pages/OverviewPage.vue';
import IndexStudiesPage from '../pages/IndexStudiesPage.vue';
import IndexVariablesPage from '../pages/IndexVariablesPage.vue';
import IndexBySurveyPage from '../pages/IndexBySurveyPage.vue';
import IndexCitationsPage from '../pages/IndexCitationsPage.vue';
import SchemaPage from '../pages/SchemaPage.vue';
import CorePage from '../pages/CorePage.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      name: 'overview',
      component: OverviewPage,
      meta: {
        title: 'Dashboard',
        subtitle: 'Connection health, document counts, and system status',
      },
    },
    {
      path: '/index/studies',
      name: 'index-studies',
      component: IndexStudiesPage,
      meta: {
        title: 'Index studies',
        subtitle: 'Batch-import survey documents into Solr',
      },
    },
    {
      path: '/index/variables',
      name: 'index-variables',
      component: IndexVariablesPage,
      meta: {
        title: 'Index variables',
        subtitle: 'Batch-import variable documents into Solr',
      },
    },
    {
      path: '/index/by-survey',
      name: 'index-by-survey',
      component: IndexBySurveyPage,
      meta: {
        title: 'Index by survey',
        subtitle: 'Index variables for one survey, or walk all surveys',
      },
    },
    {
      path: '/index/citations',
      name: 'index-citations',
      component: IndexCitationsPage,
      meta: {
        title: 'Index citations',
        subtitle: 'Batch-import citation documents into Solr',
      },
    },
    {
      path: '/schema',
      name: 'schema',
      component: SchemaPage,
      meta: {
        title: 'Schema',
        subtitle: 'Field setup, validation, and schema overview',
      },
    },
    {
      path: '/core',
      name: 'core',
      component: CorePage,
      meta: {
        title: 'Core',
        subtitle: 'Check or create the configured Solr core',
      },
    },
  ],
});
