<template>
  <v-app class="admin-permissions-app">
    <v-main class="admin-permissions-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
    <GlobalDialog />
  </v-app>
</template>

<script setup>
import GlobalDialog from './components/GlobalDialog.vue';

defineOptions({ name: 'AdminPermissionsApp' });
</script>

<style>
.admin-permissions-app,
.admin-permissions-page {
  background-color: #f0f2f5;
}

.perm-breadcrumbs {
  font-size: 0.8125rem;
}

.perm-breadcrumbs .v-breadcrumbs-item,
.perm-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}

.perm-section-header {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.perm-stack {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
</style>
