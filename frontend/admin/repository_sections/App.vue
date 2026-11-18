<template>
  <v-app class="admin-rsec-app">
    <v-main class="admin-rsec-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
defineOptions({ name: 'AdminRepositorySectionsApp' });
</script>

<style>
.admin-rsec-app,
.admin-rsec-page {
  background-color: #f0f2f5;
}
</style>
