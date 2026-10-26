<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>
      <v-icon start color="primary">mdi-cog</v-icon>
      Data aggregations
    </v-card-title>
    <v-card-text class="admin-analytics-card-sections">
      <AggregationHowItWorksAlert />
      <div class="text-body-2">
        <v-icon size="small" class="mr-1">mdi-clock-outline</v-icon>
        <strong>Last successful run:</strong>
        <span v-if="lastCompletedAt">{{ formatDateTime(lastCompletedAt, timezone) }}</span>
        <span v-else class="text-medium-emphasis">Never</span>
      </div>
      <AggregationControlsCard :running="running" @run="runFull" @stop="stop" />
      <AggregationStatusCard :status="status" />
      <CronInstructionsCard />
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useAggregationRunner } from '../composables/useAggregationRunner';
import { formatDateTime } from '../utils/formatters';
import AggregationHowItWorksAlert from '../components/aggregations/AggregationHowItWorksAlert.vue';
import AggregationControlsCard from '../components/aggregations/AggregationControlsCard.vue';
import AggregationStatusCard from '../components/aggregations/AggregationStatusCard.vue';
import CronInstructionsCard from '../components/aggregations/CronInstructionsCard.vue';

defineOptions({ name: 'AggregationsPage' });

const { config } = useAppConfig();
const timezone = computed(() => config.value?.serverTimezone || 'UTC');

const { running, status, lastCompletedAt, runFull, stop } = useAggregationRunner();
</script>
