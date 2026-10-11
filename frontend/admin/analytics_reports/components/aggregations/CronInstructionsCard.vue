<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>
      <v-icon start>mdi-clock-outline</v-icon>
      Run aggregation via cron
    </v-card-title>
    <v-card-text>
      <p class="text-body-2 mb-2">
        To run the full pipeline on a schedule, use the CLI from your NADA project root:
      </p>
      <pre class="analytics-code-block">php index.php cli/analytics run_aggregates</pre>
      <p class="text-body-2 mt-3 mb-2">Example crontab entry (daily at 2:00 AM):</p>
      <pre class="analytics-code-block">0 2 * * * cd {{ nadaBasePath }} &amp;&amp; php index.php cli/analytics run_aggregates</pre>
      <p class="text-caption text-medium-emphasis mt-2 mb-0">
        The CLI runs the same pipeline as “Run full aggregation” and exits when done.
      </p>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'CronInstructionsCard' });

const { config } = useAppConfig();
const nadaBasePath = computed(() => config.value?.nadaBasePath || '/path/to/nada');
</script>
