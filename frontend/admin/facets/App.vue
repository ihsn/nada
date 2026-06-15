<template>
  <v-app class="admin-facets-app">
    <v-main class="admin-facets-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
defineOptions({ name: 'AdminFacetsApp' });
</script>

<style>
.admin-facets-app,
.admin-facets-page {
  background-color: #f0f2f5;
}
</style>
