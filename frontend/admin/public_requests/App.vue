<template>
  <v-app class="admin-pr-app">
    <v-main class="admin-pr-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <PublicRequestPage />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import PublicRequestPage from './pages/PublicRequestPage.vue';

defineOptions({ name: 'AdminPublicRequestsApp' });
</script>

<style>
.admin-pr-app,
.admin-pr-page {
  background-color: #f0f2f5;
}
</style>
