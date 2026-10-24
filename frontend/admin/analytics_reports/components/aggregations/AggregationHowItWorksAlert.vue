<template>
  <v-alert
    type="info"
    variant="tonal"
    class="admin-analytics-section-elevated"
    rounded="lg"
    elevation="1"
  >
    <div class="text-subtitle-2 font-weight-bold mb-2">How aggregation works</div>
    <ul class="pl-4 mb-2" style="line-height: 1.6">
      <li><strong>Daily:</strong> Fills missing daily aggregates from raw events, up to yesterday.</li>
      <li><strong>Monthly:</strong> Rolls daily data into monthly totals (oldest months first).</li>
      <li><strong>Month-end:</strong> Finalizes past months and removes their daily rows.</li>
      <li><strong>Cleanup:</strong> Deletes raw events older than the retention window.</li>
      <li><strong>Sync:</strong> Updates study view/download counters on the catalog.</li>
    </ul>
    <div class="text-caption">
      Processing runs one step per request; keep this page open until the run finishes. You can stop at any time.
    </div>
  </v-alert>
</template>

<script setup>
defineOptions({ name: 'AggregationHowItWorksAlert' });
</script>
