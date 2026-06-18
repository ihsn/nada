import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import App from './App.vue';
import { createCatalogSearchRouter } from './router';

const vuetify = createVuetify({
  theme: appTheme,
});

const app = createApp(App);
const router = createCatalogSearchRouter();

app.use(vuetify);
app.use(router);
app.provide(APP_CONFIG_KEY, window.APP_CONFIG || {});

router.isReady().then(() => {
  app.mount('#public-catalog-vue-app');
  document.getElementById('catalog-ssr')?.remove();
});
