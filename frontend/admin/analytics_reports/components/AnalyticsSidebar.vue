<template>
  <aside class="admin-analytics-sidebar" :aria-label="t('analytics_navigation', 'Analytics navigation')">
    <v-list density="compact" nav class="pt-2">
      <v-list-item class="mb-1">
        <template #prepend>
          <v-icon color="primary">mdi-chart-bar</v-icon>
        </template>
        <v-list-item-title class="font-weight-medium">{{ t('Analytics', 'Analytics') }}</v-list-item-title>
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
import { useI18n } from '@/shared/composables/useI18n';
import router from '../router';

defineOptions({ name: 'AnalyticsSidebar' });

const { t } = useI18n();
const navItems = computed(() =>
  router
    .getRoutes()
    .filter((r) => r.meta?.title && r.name !== undefined && r.path !== '/')
    .map((r) => ({
      name: r.name,
      title: t(r.meta.titleKey, r.meta.title),
      icon: r.meta.navIcon || 'mdi-circle-small',
    }))
);
</script>
