import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { appTheme } from '@/theme';
import AdminHeaderRoot from './AdminHeaderRoot.vue';

const vuetify = createVuetify({
  theme: appTheme,
});

const app = createApp(AdminHeaderRoot);
app.use(vuetify);
app.mount('#admin-app-header');
