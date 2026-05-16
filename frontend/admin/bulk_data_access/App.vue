<template>
  <v-app class="admin-bulk-da-app">
    <v-main class="admin-bulk-da-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
defineOptions({ name: 'AdminBulkDataAccessApp' });
</script>

<style>
.admin-bulk-da-app,
.admin-bulk-da-page {
  background-color: #f0f2f5;
}
</style>
