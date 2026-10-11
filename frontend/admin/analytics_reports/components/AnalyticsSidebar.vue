<template>
  <aside class="admin-analytics-sidebar" aria-label="Analytics navigation">
    <v-list density="compact" nav class="pt-2">
      <v-list-item class="mb-1">
        <template #prepend>
          <v-icon color="primary">mdi-chart-bar</v-icon>
        </template>
        <v-list-item-title class="font-weight-medium">Analytics</v-list-item-title>
      </v-list-item>
      <v-divider class="mb-2" />
      <v-list-item
        v-for="item in navItems"
        :key="item.name"
        :to="{ name: item.name }"
        :prepend-icon="item.icon"
        :title="item.title"
        rounded="lg"
        color="primary"
      />
    </v-list>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import router from '../router';

defineOptions({ name: 'AnalyticsSidebar' });

const navItems = computed(() =>
  router
    .getRoutes()
    .filter((r) => r.meta?.title && r.name !== undefined && r.path !== '/')
    .map((r) => ({
      name: r.name,
      title: r.meta.title,
      icon: r.meta.navIcon || 'mdi-circle-small',
    }))
);
</script>
