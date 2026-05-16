import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import axios from 'axios';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import App from './App.vue';

axios.defaults.withCredentials = true;

const vuetify = createVuetify({
  theme: appTheme,
});
const app = createApp(App);
app.use(vuetify);
app.provide(APP_CONFIG_KEY, window.APP_CONFIG || {});
app.mount('#catalog-study-related-data-app');
