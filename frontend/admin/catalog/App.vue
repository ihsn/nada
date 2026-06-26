<template>
  <v-app class="admin-catalog-app">
    <GlobalDialog />
    <v-main class="admin-catalog-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import GlobalDialog from './components/GlobalDialog.vue';

defineOptions({ name: 'AdminCatalogApp' });
</script>

<style>
.admin-catalog-app,
.admin-catalog-page {
  background-color: #f0f2f5;
}
</style>
