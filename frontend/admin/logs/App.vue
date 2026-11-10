<template>
  <v-app class="admin-logs-app">
    <v-main class="admin-logs-page">
      <v-container fluid class="px-4 pt-2 pb-6">
        <v-breadcrumbs :items="breadcrumbItems" class="logs-breadcrumbs px-0 pt-0">
          <template #divider>
            <v-icon icon="mdi-chevron-right" size="16" />
          </template>
        </v-breadcrumbs>

        <div class="logs-header">
          <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">{{ currentTab.heading }}</h1>
          <div class="logs-header__actions">
            <v-btn
              v-for="tab in tabs"
              :key="tab.value"
              :href="tab.href"
              :prepend-icon="tab.icon"
              :color="tab.value === view ? 'primary' : undefined"
              :variant="tab.value === view ? 'tonal' : 'outlined'"
              :aria-current="tab.value === view ? 'page' : undefined"
              size="small"
            >
              {{ tab.title }}
            </v-btn>
          </div>
        </div>

        <SiteLogsPage v-if="view === 'site'" />
        <ApiLogsPage v-else-if="view === 'api'" />
        <LogsCleanupPage v-else />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import SiteLogsPage from './pages/SiteLogsPage.vue';
import ApiLogsPage from './pages/ApiLogsPage.vue';
import LogsCleanupPage from './pages/LogsCleanupPage.vue';

defineOptions({ name: 'AdminLogsApp' });

const { config, siteUrl } = useAppConfig();
const { t } = useI18n();

const view = computed(() => {
  const v = config.value?.logsView;
  return v === 'api' || v === 'cleanup' ? v : 'site';
});

const site = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const tabs = computed(() => [
  {
    value: 'site',
    title: t('site_logs', 'Site logs'),
    heading: t('site_logs', 'Site logs'),
    icon: 'mdi-text-box-outline',
    href: `${site.value}/admin/logs`,
  },
  {
    value: 'api',
    title: 'API logs',
    heading: 'API logs',
    icon: 'mdi-api',
    href: `${site.value}/admin/api_logs`,
  },
  {
    value: 'cleanup',
    title: 'Cleanup',
    heading: 'Log cleanup & archiving',
    icon: 'mdi-archive-arrow-down-outline',
    href: `${site.value}/admin/logs/cleanup`,
  },
]);

const currentTab = computed(() => tabs.value.find((tab) => tab.value === view.value) || tabs.value[0]);

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${site.value}/admin` },
  { title: currentTab.value.heading, disabled: true },
]);
</script>

<style>
.admin-logs-app,
.admin-logs-page {
  background-color: #f0f2f5;
}

.logs-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.logs-header__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.logs-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
</style>
