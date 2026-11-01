<template>
  <div class="d-flex flex-wrap align-center justify-space-between gap-3">
    <h1 class="admin-analytics-page-title mb-0">{{ pageTitle }}</h1>
    <div v-if="serverClock" class="text-end">
      <div class="text-body-2 font-weight-medium">{{ serverClock }}</div>
      <div class="text-caption text-medium-emphasis">{{ t('analytics_server_time', 'Server time') }} — {{ timezone }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { analyticsLocale } from '../utils/formatters';

defineOptions({ name: 'AnalyticsPageHeader' });

const route = useRoute();
const { config } = useAppConfig();
const { t } = useI18n();

const pageTitle = computed(() => {
  if (route.name === 'overview') return t('analytics_reports', 'Analytics Reports');
  return route.meta?.title
    ? t(route.meta.titleKey, route.meta.title)
    : t('analytics_reports', 'Analytics Reports');
});

const timezone = computed(() => config.value?.serverTimezone || 'UTC');
const serverClock = ref('');

let timer = null;

function tick() {
  try {
    serverClock.value = new Date().toLocaleString(analyticsLocale(), {
      timeZone: timezone.value,
      weekday: 'short',
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  } catch {
    serverClock.value = new Date().toLocaleString();
  }
}

onMounted(() => {
  tick();
  timer = setInterval(tick, 1000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>
