import 'vuetify/styles';
import '@/assets/base.css';
import './catalogSearch.css';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import { catalogSearchIcons } from './mdiIcons';
import App from './App.vue';

const vuetify = createVuetify({
  theme: appTheme,
  icons: catalogSearchIcons,
});

const app = createApp(App);

app.use(vuetify);
app.provide(APP_CONFIG_KEY, window.APP_CONFIG || {});

app.mount('#public-catalog-vue-app');
document.getElementById('catalog-ssr')?.remove();
