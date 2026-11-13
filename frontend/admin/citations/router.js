import { createRouter, createWebHistory } from 'vue-router';
import CitationsListPage from './pages/CitationsListPage.vue';
import CitationFormPage from './pages/CitationFormPage.vue';
import ImportPage from './pages/ImportPage.vue';
import ExportPage from './pages/ExportPage.vue';

function historyBase() {
  const raw =
    typeof window !== 'undefined' && window.APP_CONFIG && window.APP_CONFIG.routerPathBase
      ? String(window.APP_CONFIG.routerPathBase)
      : '/admin/citations';
  return `${raw.replace(/\/$/, '')}/`;
}

export function createCitationsRouter() {
  return createRouter({
    history: createWebHistory(historyBase()),
    routes: [
      { path: '/', name: 'citations-list', component: CitationsListPage },
      { path: '/add', name: 'citation-create', component: CitationFormPage },
      { path: '/edit/:id(\\d+)', name: 'citation-edit', component: CitationFormPage, props: true },
      { path: '/import', name: 'citations-import', component: ImportPage },
      { path: '/export', name: 'citations-export', component: ExportPage },
      { path: '/:pathMatch(.*)*', redirect: { name: 'citations-list' } },
    ],
  });
}
