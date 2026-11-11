import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import axios from 'axios';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { createRouter, createWebHashHistory } from 'vue-router';
import { appTheme } from '@/theme';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import App from './App.vue';
import SectionsListPage from './pages/SectionsListPage.vue';
import SectionEditPage from './pages/SectionEditPage.vue';

axios.defaults.withCredentials = true;

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', name: 'sections-list', component: SectionsListPage },
    { path: '/new', name: 'section-new', component: SectionEditPage, props: { isNew: true } },
    {
      path: '/edit/:id',
      name: 'section-edit',
      component: SectionEditPage,
      props: (route) => ({ isNew: false, id: route.params.id }),
    },
  ],
});

const vuetify = createVuetify({ theme: appTheme });
const app = createApp(App);
app.use(vuetify);
app.use(router);
app.provide(APP_CONFIG_KEY, window.APP_CONFIG || {});
app.mount('#admin-repository-sections-app');
