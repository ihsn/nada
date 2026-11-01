<template>
  <v-alert
    v-if="health.type !== 'success'"
    class="admin-analytics-section-elevated"
    :type="health.type"
    variant="tonal"
    density="compact"
    rounded="lg"
    elevation="1"
  >
    <v-icon start size="small">mdi-database-clock</v-icon>
    {{ health.text }}
  </v-alert>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { timeAgo } from '../utils/formatters';

const props = defineProps({
  lastCompletedAt: { type: String, default: null },
});

defineOptions({ name: 'AggregationHealthAlert' });

const { t } = useI18n();
const health = computed(() => {
  if (!props.lastCompletedAt) {
    return { type: 'warning', text: t('analytics_health_never', 'Aggregation has never been run. Visit Aggregations to run it.') };
  }
  const hours = (Date.now() - new Date(props.lastCompletedAt).getTime()) / 3600000;
  const ago = timeAgo(props.lastCompletedAt);
  if (hours < 24) {
    return { type: 'success', text: t('analytics_health_fresh', 'Last aggregated %s. Data is up to date.', ago) };
  }
  if (hours < 168) {
    return { type: 'warning', text: t('analytics_health_stale', 'Last aggregated %s. Consider running aggregation soon.', ago) };
  }
  return { type: 'error', text: t('analytics_health_old', 'Last aggregated %s. Data may be significantly out of date.', ago) };
});
</script>
