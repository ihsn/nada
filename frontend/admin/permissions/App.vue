<template>
  <v-app class="admin-permissions-app">
    <v-main class="admin-permissions-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
defineOptions({ name: 'AdminPermissionsApp' });
</script>

<style>
.admin-permissions-app,
.admin-permissions-page {
  background-color: #f0f2f5;
}
</style>
