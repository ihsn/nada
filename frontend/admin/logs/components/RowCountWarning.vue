<template>
  <v-alert v-if="counts && counts[`${table}_exceeds_threshold`]" type="warning" variant="tonal" density="compact" class="mb-4">
    <strong>Large number of log entries.</strong>
    The {{ table }} table has about {{ formatNumber(counts[table]) }} rows
    (threshold {{ formatNumber(counts.warning_threshold) }}), which may affect performance.
    <a :href="cleanupUrl" class="font-weight-medium">Run cleanup &amp; archiving</a> to archive old entries.
  </v-alert>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useLogsApi } from '../composables/useLogsApi';
import { formatNumber } from '../utils/format';

defineProps({
  table: { type: String, required: true, validator: (v) => v === 'sitelogs' || v === 'api_logs' },
});

const { siteUrl } = useAppConfig();
const { rowCounts } = useLogsApi();
const counts = ref(null);

const cleanupUrl = computed(() => `${String(siteUrl.value || '').replace(/\/$/, '')}/admin/logs/cleanup`);

onMounted(async () => {
  try {
    counts.value = await rowCounts();
  } catch {
    counts.value = null;
  }
});
</script>
