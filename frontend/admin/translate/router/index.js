import { createRouter, createWebHashHistory } from 'vue-router';
import LanguagesListPage from '../pages/LanguagesListPage.vue';
import LanguageEditPage from '../pages/LanguageEditPage.vue';
import LanguageSearchPage from '../pages/LanguageSearchPage.vue';
import LanguageImportPage from '../pages/LanguageImportPage.vue';

export function createTranslateRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      { path: '/', name: 'languages-list', component: LanguagesListPage },
      { path: '/import', name: 'language-import', component: LanguageImportPage },
      { path: '/:lang/search', name: 'language-search', component: LanguageSearchPage, props: true },
      { path: '/:lang/edit/:file?', name: 'language-edit', component: LanguageEditPage, props: true },
      { path: '/:lang', name: 'language-edit-root', component: LanguageEditPage, props: true },
      { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
  });
}
