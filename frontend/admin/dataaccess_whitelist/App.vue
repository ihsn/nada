<template>
  <v-app class="admin-daw-app">
    <v-main class="admin-daw-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <WhitelistPage />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import WhitelistPage from './pages/WhitelistPage.vue';

defineOptions({ name: 'AdminDataaccessWhitelistApp' });
</script>

<style>
.admin-daw-app,
.admin-daw-page {
  background-color: #f0f2f5;
}
</style>
