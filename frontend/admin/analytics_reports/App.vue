<template>
  <v-app class="admin-analytics-app">
    <div class="admin-analytics-shell">
      <AnalyticsSidebar />
      <v-main class="admin-analytics-main">
        <v-container fluid class="px-4">
          <AnalyticsPageHeader />
          <router-view />
        </v-container>
      </v-main>
    </div>
    <v-snackbar
      v-model="snackbar.open"
      :color="snackbar.color"
      :timeout="4000"
      location="bottom"
    >
      {{ snackbar.text }}
      <template #actions>
        <v-btn variant="text" size="small" @click="snackbar.open = false">Close</v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { provide, reactive } from 'vue';
import './styles/analytics-layout.css';
import AnalyticsSidebar from './components/AnalyticsSidebar.vue';
import AnalyticsPageHeader from './components/AnalyticsPageHeader.vue';
import { SET_MESSAGE_KEY } from './composables/useAnalyticsMessages';

defineOptions({ name: 'AdminAnalyticsReportsApp' });

const snackbar = reactive({ open: false, text: '', color: 'surface' });

function setMessage(text, type = 'info') {
  const map = { info: 'info', success: 'success', error: 'error', warning: 'warning' };
  snackbar.text = text;
  snackbar.color = map[type] || 'info';
  snackbar.open = true;
}

provide(SET_MESSAGE_KEY, setMessage);
</script>
