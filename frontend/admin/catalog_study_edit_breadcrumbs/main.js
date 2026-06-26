import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import App from './App.vue';

const root = document.getElementById('catalog-study-edit-breadcrumbs-app');
if (root) {
  let cfg = {};
  try {
    cfg = JSON.parse(root.dataset.config || '{}');
  } catch (e) {
    cfg = {};
  }

  const vuetify = createVuetify({
    theme: appTheme,
  });

  const app = createApp(App, {
    homeUrl: cfg.homeUrl || '',
    catalogUrl: cfg.catalogUrl || '',
    editUrl: cfg.editUrl || '',
    homeLabel: cfg.homeLabel || 'Home',
    catalogLabel: cfg.catalogLabel || 'Catalog',
    editLabel: cfg.editLabel || 'Edit',
  });
  app.use(vuetify);
  app.mount(root);
}
