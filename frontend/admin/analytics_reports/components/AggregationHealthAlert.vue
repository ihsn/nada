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
import { timeAgo } from '../utils/formatters';

const props = defineProps({
  lastCompletedAt: { type: String, default: null },
});

defineOptions({ name: 'AggregationHealthAlert' });

const health = computed(() => {
  if (!props.lastCompletedAt) {
    return { type: 'warning', text: 'Aggregation has never been run. Visit Aggregations to run it.' };
  }
  const hours = (Date.now() - new Date(props.lastCompletedAt).getTime()) / 3600000;
  const ago = timeAgo(props.lastCompletedAt);
  if (hours < 24) {
    return { type: 'success', text: `Last aggregated ${ago}. Data is up to date.` };
  }
  if (hours < 168) {
    return { type: 'warning', text: `Last aggregated ${ago}. Consider running aggregation soon.` };
  }
  return { type: 'error', text: `Last aggregated ${ago}. Data may be significantly out of date.` };
});
</script>
