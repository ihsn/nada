import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import App from './App.vue';

const vuetify = createVuetify({
  theme: appTheme,
});

const app = createApp(App);
app.use(vuetify);
app.mount('#admin-ui-kit-app');
