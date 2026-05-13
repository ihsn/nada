<template>
  <div class="logs-health">
    <div v-if="!logsHealth" class="text-medium-emphasis text-body-2 py-1">
      Loading log health…
    </div>

    <!-- Server Info -->
    <div v-if="serverInfo" class="logs-health__server" :class="{ 'mt-2': !logsHealth }">
      <div class="text-subtitle-2 font-weight-medium mb-2 text-medium-emphasis">Server</div>
      <v-list density="compact" class="bg-transparent py-0">
        <v-list-item class="px-0">
          <template #title>
            <span class="text-body-2">PHP version</span>
          </template>
          <template #append>
            <span class="logs-health__php">{{ serverInfo.php_version }}</span>
          </template>
        </v-list-item>
        <v-divider class="my-1" />
        <v-list-item class="px-0">
          <template #title>
            <span class="text-body-2">Server time</span>
          </template>
          <template #append>
            <span class="text-caption text-medium-emphasis text-end d-block logs-health__time-wrap">{{ serverInfo.server_time }} {{ serverInfo.server_tz }}</span>
          </template>
        </v-list-item>
      </v-list>
    </div>

    <div v-if="logsHealth" class="logs-health__log-section">
      <div class="text-subtitle-2 font-weight-medium mb-2 text-medium-emphasis">Log tables</div>

      <v-list density="compact" class="bg-transparent py-0">
        <v-list-item class="px-0" lines="two">
          <template #title>
            <span class="text-body-2">Site logs</span>
          </template>
          <template #subtitle>
            <span class="text-caption text-medium-emphasis">
              <span v-if="sitelogsStatus === 'unknown'">Count unavailable</span>
              <span v-else>{{ formatCount(logsHealth.sitelogs) }} rows</span>
            </span>
          </template>
          <template #append>
            <v-icon v-if="sitelogsStatus === 'ok'" color="success" size="small">mdi-check-circle</v-icon>
            <v-icon v-else-if="sitelogsStatus === 'warning'" color="warning" size="small">mdi-alert</v-icon>
            <v-icon v-else color="grey" size="small">mdi-help-circle-outline</v-icon>
          </template>
        </v-list-item>

        <v-divider class="my-1" />

        <v-list-item class="px-0" lines="two">
          <template #title>
            <span class="text-body-2">API logs</span>
          </template>
          <template #subtitle>
            <span class="text-caption text-medium-emphasis">
              <span v-if="apiLogsStatus === 'unknown'">Count unavailable</span>
              <span v-else>{{ formatCount(logsHealth.api_logs) }} rows</span>
            </span>
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
        class="mt-3 text-caption"
      >
        One or more log tables have exceeded the row count threshold.
      </v-alert>
    </div>

    <div class="mt-3 text-end">
      <a
        :href="siteUrl + '/admin/logs/cleanup'"
        class="text-caption text-decoration-none text-primary"
      >
        Cleanup &amp; archiving →
      </a>
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

<style scoped>
.logs-health__server {
  margin-bottom: 0;
}

.logs-health__log-section {
  padding-top: 0.75rem;
}

.logs-health__php {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  color: rgb(var(--v-theme-on-surface));
  background-color: rgba(var(--v-theme-on-surface), 0.06);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.14);
  border-radius: 6px;
  padding: 4px 10px;
  line-height: 1.3;
}

.logs-health__time-wrap {
  max-width: 14rem;
}
</style>
