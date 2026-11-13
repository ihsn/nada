<template>
  <v-app class="admin-citations-app">
    <v-main class="admin-citations-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <router-view :key="$route.path" />
      </v-container>
    </v-main>
    <GlobalDialog />
  </v-app>
</template>

<script setup>
import GlobalDialog from './components/GlobalDialog.vue';

defineOptions({ name: 'AdminCitationsApp' });
</script>

<style>
.admin-citations-app,
.admin-citations-page {
  background-color: #f0f2f5;
}
/* base.css resets margins outside Vuetify's layer, so spacing utilities like mb-4 have no effect. */
.cit-stack {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.cit-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.cit-breadcrumbs .v-breadcrumbs-item,
.cit-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}
.cit-field-label {
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 4px;
}
.cit-section-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 12px;
}
.cit-muted {
  color: rgba(0, 0, 0, 0.6);
  font-size: 0.8125rem;
}
.cit-title-link {
  color: rgb(var(--v-theme-primary));
  font-weight: 500;
  text-decoration: none;
}
.cit-title-link:hover {
  text-decoration: underline;
}
.cit-preview {
  font-size: 0.9375rem;
  line-height: 1.5;
}
.cit-preview .title {
  font-weight: 500;
}
.cit-dialog-message {
  white-space: pre-line;
}

/* base.css resets margins outside Vuetify's layer, so the margin utilities used on these pages are restated here. */
.admin-citations-app .mt-1 { margin-top: 4px !important; }
.admin-citations-app .mt-2 { margin-top: 8px !important; }
.admin-citations-app .mt-3 { margin-top: 12px !important; }
.admin-citations-app .mt-4 { margin-top: 16px !important; }
.admin-citations-app .mb-1 { margin-bottom: 4px !important; }
.admin-citations-app .mb-2 { margin-bottom: 8px !important; }
.admin-citations-app .mb-3 { margin-bottom: 12px !important; }
.admin-citations-app .mb-4 { margin-bottom: 16px !important; }
.admin-citations-app .my-4 { margin-top: 16px !important; margin-bottom: 16px !important; }
.admin-citations-app .ma-4 { margin: 16px !important; }
.admin-citations-app .ms-1 { margin-inline-start: 4px !important; }
.admin-citations-app .ms-2 { margin-inline-start: 8px !important; }
.admin-citations-app .me-1 { margin-inline-end: 4px !important; }
.admin-citations-app .me-2 { margin-inline-end: 8px !important; }
.admin-citations-app .v-row { row-gap: 8px; }
</style>
