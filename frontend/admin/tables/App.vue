<template>
  <v-app class="admin-tables-app">
    <v-main class="admin-tables-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
    <v-snackbar
      v-model="snackbar.open"
      :color="snackbar.type"
      :timeout="3500"
      location="bottom"
      multi-line
    >
      {{ snackbar.text }}
      <template #actions>
        <v-btn variant="text" size="small" @click="snackbar.open = false">Close</v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { reactive, provide } from 'vue';
import './styles/tables-tab-panel.css';
import './styles/tables-layout.css';

defineOptions({ name: 'AdminTablesApp' });

const snackbar = reactive({ open: false, text: '', type: 'success' });

function setMessage(text, type = 'success') {
  const map = { info: 'info', success: 'success', error: 'error', warning: 'warning' };
  snackbar.text = text;
  snackbar.type = map[type] || 'info';
  snackbar.open = true;
}

provide('setMessage', setMessage);
</script>

<style>
.admin-tables-app,
.admin-tables-page {
  background-color: #f0f2f5;
}
</style>
