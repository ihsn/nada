import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import axios from 'axios';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import App from './App.vue';
import { createDepositRouter } from './router';

axios.defaults.withCredentials = true;

const vuetify = createVuetify({
  theme: appTheme,
});
const app = createApp(App);
const router = createDepositRouter();
app.use(vuetify);
app.use(router);
app.provide(APP_CONFIG_KEY, window.APP_CONFIG || {});
router.isReady().then(() => {
  app.mount('#datadeposit-app');
});
