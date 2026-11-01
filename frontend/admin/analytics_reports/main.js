import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import '@/assets/base.css';
import axios from 'axios';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import { pt } from 'vuetify/locale';
import { appTheme } from '@/theme';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import App from './App.vue';
import router from './router';

axios.defaults.withCredentials = true;

const { t } = useI18n();
const locale = window.APP_CONFIG?.analyticsLocale === 'pt-PT'
  ? {
      locale: 'pt',
      fallback: 'en',
      messages: {
        pt: {
          ...pt,
          loading: t('analytics_loading', 'Loading...'),
          noDataText: t('analytics_no_data', 'No data available'),
          dataIterator: {
            ...pt.dataIterator,
            loadingText: t('analytics_loading_items', 'Loading items...'),
            noResultsText: t('analytics_no_results', 'No matching records found'),
          },
        },
      },
    }
  : undefined;
const vuetify = createVuetify({ theme: appTheme, locale });
const app = createApp(App);
app.use(vuetify);
app.use(router);
app.provide(APP_CONFIG_KEY, window.APP_CONFIG || {});
app.mount('#admin-analytics-reports-app');
