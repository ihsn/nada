import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import axios from 'axios';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import App from './App.vue';

const appConfig = window.APP_CONFIG || {};
if (appConfig.siteUrl) {
  const base = String(appConfig.siteUrl).replace(/\/$/, '');
  axios.interceptors.response.use(
    (r) => r,
    (err) => {
      if (err?.response?.status === 401) {
        window.location = `${base}/auth/login/?destination=admin/`;
      }
      return Promise.reject(err);
    },
  );
}

const vuetify = createVuetify({
  theme: appTheme,
});

const app = createApp(App);
app.use(vuetify);
app.provide(APP_CONFIG_KEY, appConfig);
app.mount('#admin-dashboard-app');
