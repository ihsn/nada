<template>
  <v-alert type="info" variant="tonal" density="compact" class="admin-analytics-section-elevated" rounded="lg">
    {{ t('analytics_raw_hint', 'Raw events are high volume. Choose a date range (max %s days). For trends, use Daily or Monthly data.', maxDays) }}
  </v-alert>
</template>

<script setup>
import { useI18n } from '@/shared/composables/useI18n';

defineProps({
  maxDays: { type: Number, default: 31 },
});

defineOptions({ name: 'RawLogsHintAlert' });

const { t } = useI18n();
</script>
