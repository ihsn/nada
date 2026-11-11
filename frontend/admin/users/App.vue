<template>
  <v-app class="admin-users-app">
    <v-main class="admin-users-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view :key="$route.path" />
      </v-container>
    </v-main>
    <GlobalDialog />
  </v-app>
</template>

<script setup>
import GlobalDialog from './components/GlobalDialog.vue';

defineOptions({ name: 'AdminUsersApp' });
</script>

<style>
.admin-users-app,
.admin-users-page {
  background-color: #f0f2f5;
}
/* base.css resets margins outside Vuetify's layer, so spacing utilities like mb-4 have no effect. */
.users-stack {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.users-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 4px 0;
}
.users-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  max-width: 100%;
  padding: 2px 8px;
  border-radius: 4px;
  border: 1px solid #c5d3ea;
  background: #edf2fb;
  color: #1c3664;
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.35;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.users-badge--admin {
  border-color: #f1c2a8;
  background: #fdf0e8;
  color: #8a3207;
}
.users-badge--collection {
  border-color: #9fd3cc;
  background: #eaf6f4;
  color: #0b5a50;
  text-decoration: none;
}
a.users-badge--collection:hover {
  background: #d4eeea;
  text-decoration: underline;
}
.users-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.users-breadcrumbs .v-breadcrumbs-item,
.users-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}
.users-field-label {
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 4px;
}
</style>
