<template>
  <v-app class="admin-licensed-requests-app">
    <v-main class="admin-licensed-requests-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
defineOptions({ name: 'AdminLicensedRequestsApp' });
</script>

<style>
.admin-licensed-requests-app,
.admin-licensed-requests-page {
  background-color: #f0f2f5;
}
</style>
