<template>
  <v-app class="admin-header-vuetify-app">
    <AdminAppBar />
  </v-app>
</template>

<script setup>
import AdminAppBar from '../shared/AdminAppBar.vue';

defineOptions({ name: 'AdminHeaderRoot' });
</script>

<style scoped>
.admin-header-vuetify-app.v-application {
  display: block;
  min-height: 0 !important;
  height: auto !important;
}
.admin-header-vuetify-app :deep(.v-application__wrap) {
  min-height: 0 !important;
}
</style>
