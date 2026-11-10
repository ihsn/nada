<template>
  <v-app class="admin-countries-app">
    <v-main class="admin-countries-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
defineOptions({ name: 'AdminCountriesApp' });
</script>

<style>
.admin-countries-app,
.admin-countries-page {
  background-color: #f0f2f5;
}

.ctry-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.ctry-breadcrumbs .v-breadcrumbs-item,
.ctry-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}

.ctry-table th {
  font-size: 0.75rem !important;
  font-weight: 600 !important;
  letter-spacing: 0.02em;
  color: rgba(var(--v-theme-on-surface), 0.6) !important;
  white-space: nowrap;
}

.ctry-table td {
  vertical-align: middle;
}

.ctry-col-actions {
  width: 1%;
  white-space: nowrap;
}

.ctry-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}

.ctry-link:hover {
  text-decoration: underline;
}

.ctry-field-group + .ctry-field-group {
  margin-top: 0.75rem;
}

.ctry-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.9);
  margin-bottom: 4px;
}

.ctry-form-actions {
  margin-top: 1.5rem;
}
</style>
