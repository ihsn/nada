<template>
  <v-app class="admin-collections-app">
    <GlobalDialog />
    <v-main class="admin-collections-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import GlobalDialog from './components/GlobalDialog.vue';

defineOptions({ name: 'AdminCollectionsApp' });
</script>

<style>
.admin-collections-app,
.admin-collections-page {
  background-color: #f0f2f5;
}
</style>
