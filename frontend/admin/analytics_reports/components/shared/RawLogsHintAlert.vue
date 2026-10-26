<template>
  <v-alert type="info" variant="tonal" density="compact" class="admin-analytics-section-elevated" rounded="lg">
    Raw events are high volume. Choose a date range (max {{ maxDays }} days). For trends, use Daily or Monthly data.
  </v-alert>
</template>

<script setup>
defineProps({
  maxDays: { type: Number, default: 31 },
});

defineOptions({ name: 'RawLogsHintAlert' });
</script>
