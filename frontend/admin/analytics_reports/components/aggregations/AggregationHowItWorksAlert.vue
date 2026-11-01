<template>
  <v-alert
    type="info"
    variant="tonal"
    class="admin-analytics-section-elevated"
    rounded="lg"
    elevation="1"
  >
    <div class="text-subtitle-2 font-weight-bold mb-2">{{ t('analytics_how_aggregation', 'How aggregation works') }}</div>
    <ul class="pl-4 mb-2" style="line-height: 1.6">
      <li><strong>{{ t('analytics_how_daily_title', 'Daily:') }}</strong> {{ t('analytics_how_daily', 'Fills missing daily aggregates from raw events, up to yesterday.') }}</li>
      <li><strong>{{ t('analytics_how_monthly_title', 'Monthly:') }}</strong> {{ t('analytics_how_monthly', 'Rolls daily data into monthly totals (oldest months first).') }}</li>
      <li><strong>{{ t('analytics_how_month_end_title', 'Month-end:') }}</strong> {{ t('analytics_how_month_end', 'Finalizes past months and removes their daily rows.') }}</li>
      <li><strong>{{ t('analytics_how_cleanup_title', 'Cleanup:') }}</strong> {{ t('analytics_how_cleanup', 'Deletes raw events older than the retention window.') }}</li>
      <li><strong>{{ t('analytics_how_sync_title', 'Sync:') }}</strong> {{ t('analytics_how_sync', 'Updates study view/download counters on the catalog.') }}</li>
    </ul>
    <div class="text-caption">
      {{ t('analytics_keep_open', 'Processing runs one step per request; keep this page open until the run finishes. You can stop at any time.') }}
    </div>
  </v-alert>
</template>

<script setup>
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'AggregationHowItWorksAlert' });

const { t } = useI18n();
</script>
