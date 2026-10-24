<template>
  <v-card v-if="status" class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title class="d-flex align-center">
      <v-icon start :color="color">{{ icon }}</v-icon>
      {{ status.title }}
    </v-card-title>
    <v-card-text>
      <p>{{ status.message }}</p>
      <v-progress-linear
        v-if="status.progress !== undefined"
        :model-value="status.progress"
        color="primary"
        height="25"
        class="mb-2"
      >
        <strong>{{ status.progress }}%</strong>
      </v-progress-linear>
      <div v-if="status.details" class="text-caption text-medium-emphasis">{{ status.details }}</div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: Object, default: null },
});

defineOptions({ name: 'AggregationStatusCard' });

const color = computed(() => {
  const map = { info: 'info', success: 'success', warning: 'warning', error: 'error' };
  return map[props.status?.type] || 'info';
});

const icon = computed(() => {
  const map = {
    info: 'mdi-information',
    success: 'mdi-check-circle',
    warning: 'mdi-alert',
    error: 'mdi-alert-circle',
  };
  return map[props.status?.type] || 'mdi-information';
});
</script>
