<template>
  <div>
    <!-- Server Info -->
    <div v-if="serverInfo" class="mb-4">
      <div class="text-subtitle-2 mb-2">Server</div>
      <v-list density="compact" class="py-0">
        <v-list-item class="px-0">
          <template #title><span class="text-body-2">PHP Version</span></template>
          <template #append>
            <v-chip size="x-small" label color="blue-grey-lighten-4" style="font-family:monospace;">{{ serverInfo.php_version }}</v-chip>
          </template>
        </v-list-item>
        <v-list-item class="px-0">
          <template #title><span class="text-body-2">Server Time</span></template>
          <template #append>
            <span class="text-caption text-medium-emphasis">{{ serverInfo.server_time }} {{ serverInfo.server_tz }}</span>
          </template>
        </v-list-item>
      </v-list>
    </div>

    <!-- Log Row Counts -->
    <div class="text-subtitle-2 mb-2">Log Tables</div>

    <v-list density="compact" class="py-0">
      <v-list-item class="px-0">
        <template #title>
          <span class="text-body-2">Site Logs</span>
          <div class="text-caption text-medium-emphasis">
            <span v-if="sitelogsStatus === 'unknown'">Count unavailable</span>
            <span v-else>{{ formatCount(logsHealth.sitelogs) }} rows</span>
          </div>
        </template>
        <template #append>
          <v-icon v-if="sitelogsStatus === 'ok'" color="success" size="small">mdi-check-circle</v-icon>
          <v-icon v-else-if="sitelogsStatus === 'warning'" color="warning" size="small">mdi-alert</v-icon>
          <v-icon v-else color="grey" size="small">mdi-help-circle-outline</v-icon>
        </template>
      </v-list-item>

      <v-list-item class="px-0">
        <template #title>
          <span class="text-body-2">API Logs</span>
          <div class="text-caption text-medium-emphasis">
            <span v-if="apiLogsStatus === 'unknown'">Count unavailable</span>
            <span v-else>{{ formatCount(logsHealth.api_logs) }} rows</span>
          </div>
        </template>
        <template #append>
          <v-icon v-if="apiLogsStatus === 'ok'" color="success" size="small">mdi-check-circle</v-icon>
          <v-icon v-else-if="apiLogsStatus === 'warning'" color="warning" size="small">mdi-alert</v-icon>
          <v-icon v-else color="grey" size="small">mdi-help-circle-outline</v-icon>
        </template>
      </v-list-item>
    </v-list>

    <v-alert
      v-if="sitelogsStatus === 'warning' || apiLogsStatus === 'warning'"
      type="warning"
      density="compact"
      variant="tinted"
      class="mt-2 text-caption"
    >
      One or more log tables have exceeded the row count threshold.
    </v-alert>

    <div v-if="!logsHealth" class="text-medium-emphasis text-caption">Loading log health…</div>

    <div class="mt-2 text-right">
      <a :href="siteUrl + '/admin/logs/cleanup'" class="text-caption text-decoration-none">Cleanup &amp; Archiving →</a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  logsHealth: { type: Object, default: null },
  serverInfo: { type: Object, default: null },
  siteUrl: { type: String, default: '' },
});

const sitelogsStatus = computed(() => {
  if (!props.logsHealth || props.logsHealth.sitelogs === null) return 'unknown';
  return props.logsHealth.sitelogs_exceeds_threshold ? 'warning' : 'ok';
});

const apiLogsStatus = computed(() => {
  if (!props.logsHealth || props.logsHealth.api_logs === null) return 'unknown';
  return props.logsHealth.api_logs_exceeds_threshold ? 'warning' : 'ok';
});

function formatCount(n) {
  if (n === null || n === undefined) return '?';
  return Number(n).toLocaleString();
}
</script>
