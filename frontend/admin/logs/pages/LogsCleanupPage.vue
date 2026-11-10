<template>
  <div class="logs-cleanup-page">
    <p class="text-body-2 text-medium-emphasis mb-5">
      Move old log entries to CSV files to keep the log tables small and the site fast.
    </p>

    <LogCleanupPanel table="sitelogs" :title="t('site_logs', 'Site logs')" />
    <LogCleanupPanel table="api_logs" title="API logs" />
  </div>
</template>

<script setup>
import { useI18n } from '@/shared/composables/useI18n';
import LogCleanupPanel from '../components/LogCleanupPanel.vue';

defineOptions({ name: 'LogsCleanupPage' });

const { t } = useI18n();
</script>