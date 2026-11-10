<template>
  <v-app class="admin-filestore-app">
    <v-main class="admin-filestore-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <FilestorePage />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import FilestorePage from './pages/FilestorePage.vue';

defineOptions({ name: 'AdminFilestoreApp' });
</script>

<style>
.admin-filestore-app,
.admin-filestore-page {
  background-color: #f0f2f5;
}
</style>
