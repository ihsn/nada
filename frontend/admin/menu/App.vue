<template>
  <v-app class="admin-menu-app">
    <v-main class="admin-menu-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
defineOptions({ name: 'AdminMenuApp' });
</script>

<style>
.admin-menu-app,
.admin-menu-page {
  background-color: #f0f2f5;
}
</style>
