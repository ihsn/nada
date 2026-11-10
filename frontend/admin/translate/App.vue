<template>
  <v-app class="admin-translate-app">
    <v-main class="admin-translate-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
defineOptions({ name: 'AdminTranslateApp' });
</script>

<style>
.admin-translate-app,
.admin-translate-page {
  background-color: #f0f2f5;
}

.tr-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.tr-breadcrumbs .v-breadcrumbs-item,
.tr-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}

.tr-table th {
  font-size: 0.75rem !important;
  font-weight: 600 !important;
  letter-spacing: 0.02em;
  color: rgba(var(--v-theme-on-surface), 0.6) !important;
  white-space: nowrap;
}

.tr-table td {
  vertical-align: middle;
}

.tr-col-actions {
  width: 1%;
  white-space: nowrap;
}

.tr-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}

.tr-link:hover {
  text-decoration: underline;
}

.tr-master {
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
  white-space: pre-wrap;
  margin-bottom: 0.35rem;
}

.tr-key {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
  word-break: break-all;
}

.tr-file-nav .v-list-item--active {
  font-weight: 600;
}
</style>
