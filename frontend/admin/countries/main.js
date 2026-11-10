import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import axios from 'axios';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import { createCountriesRouter } from './router/index.js';
import App from './App.vue';

axios.defaults.withCredentials = true;

const config = window.APP_CONFIG || {};
const vuetify = createVuetify({ theme: appTheme });
const app = createApp(App);
app.use(vuetify);
app.use(createCountriesRouter(config.view));
app.provide(APP_CONFIG_KEY, config);
app.mount('#admin-countries-app');
