<template>
  <div class="admin-solr-stack">
    <v-row dense>
      <v-col cols="12" md="4">
        <v-card elevation="1">
          <v-card-title class="solr-card-title">Connection</v-card-title>
          <v-divider />
          <v-card-text class="pa-4">
            <div class="d-flex align-center ga-2 mb-3">
              <span
                class="solr-status-dot"
                :class="online ? 'solr-status-dot--ok' : 'solr-status-dot--err'"
              />
              <span class="font-weight-medium">{{ online ? 'Online' : 'Offline' }}</span>
              <v-spacer />
              <v-btn
                size="small"
                variant="text"
                prepend-icon="mdi-refresh"
                :loading="ping.loading"
                @click="refreshPing().then(refreshCounts)"
              >
                Refresh
              </v-btn>
            </div>
            <div v-if="ping.message" class="text-body-2 text-medium-emphasis mb-2">
              {{ ping.message }}
            </div>
            <div v-if="ping.lastAt" class="text-caption text-medium-emphasis">
              Last ping {{ formatTime(ping.lastAt) }}
            </div>
            <div v-if="core.name" class="mt-3 text-body-2">
              Core
              <strong class="solr-mono">{{ core.name }}</strong>
              <v-chip
                class="ml-2"
                size="x-small"
                :color="core.exists ? 'success' : 'error'"
                variant="tonal"
              >
                {{ core.exists ? 'exists' : 'missing' }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card elevation="1">
          <v-card-title class="solr-card-title">Database vs index</v-card-title>
          <v-divider />
          <v-card-text class="pa-4">
            <v-progress-linear v-if="counts.loading" indeterminate class="mb-3" />
            <v-alert v-else-if="counts.error" type="error" density="compact" variant="tonal">
              {{ counts.error }}
            </v-alert>
            <template v-else>
              <div
                v-for="row in countRows"
                :key="row.key"
                class="d-flex justify-space-between align-center py-2"
              >
                <span class="text-body-2">{{ row.label }}</span>
                <span class="text-body-2 solr-mono">
                  {{ fmt(row.index) }}
                  <span class="text-medium-emphasis">/ {{ fmt(row.db) }}</span>
                </span>
              </div>
            </template>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card elevation="1">
          <v-card-title class="solr-card-title">System</v-card-title>
          <v-divider />
          <v-card-text class="pa-4">
            <v-progress-linear v-if="system.loading" indeterminate class="mb-3" />
            <v-alert v-else-if="system.error" type="warning" density="compact" variant="tonal">
              {{ system.error }}
            </v-alert>
            <template v-else-if="system.data">
              <div class="text-body-2 mb-2">
                <span class="text-medium-emphasis">Solr</span>
                {{ system.data.solr_version || system.data.version || '—' }}
              </div>
              <div v-if="system.data.jvm" class="text-body-2 mb-1">
                <span class="text-medium-emphasis">JVM</span>
                {{ system.data.jvm.version || system.data.jvm.name || '—' }}
              </div>
              <div v-if="coreStatusDocs != null" class="text-body-2">
                <span class="text-medium-emphasis">Docs in core</span>
                {{ fmt(coreStatusDocs) }}
              </div>
            </template>
            <div v-else class="text-medium-emphasis text-body-2">No system info</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card v-if="core.exists && core.status" elevation="1">
      <v-card-title class="solr-card-title">Configured core details</v-card-title>
      <v-divider />
      <v-card-text class="pa-4">
        <v-row dense>
          <v-col cols="12" sm="6" md="3">
            <div class="text-caption text-medium-emphasis">Instance dir</div>
            <div class="text-body-2 solr-mono">{{ core.status.instanceDir || '—' }}</div>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <div class="text-caption text-medium-emphasis">Config</div>
            <div class="text-body-2">{{ core.status.config || '—' }}</div>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <div class="text-caption text-medium-emphasis">Index size</div>
            <div class="text-body-2">{{ core.status.index?.size || '—' }}</div>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <div class="text-caption text-medium-emphasis">Uptime</div>
            <div class="text-body-2">{{ formatUptime(core.status.uptime) }}</div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useSolrOps } from '../composables/useSolrOps.js';

defineOptions({ name: 'SolrOverviewPage' });

const { ping, counts, core, system, refreshPing, refreshCounts, refreshDashboard } = useSolrOps();

const online = computed(() => String(ping.status || '').toUpperCase() === 'OK');

const countRows = computed(() => {
  const db = counts.db || {};
  const idx = counts.index || {};
  return [
    {
      key: 'studies',
      label: 'Studies',
      db: db.surveys ?? db.datasets ?? db.studies,
      index: idx.datasets ?? idx.surveys ?? idx.studies,
    },
    {
      key: 'variables',
      label: 'Variables',
      db: db.variables,
      index: idx.variables,
    },
    {
      key: 'citations',
      label: 'Citations',
      db: db.citations,
      index: idx.citations,
    },
  ];
});

const coreStatusDocs = computed(() => {
  const n = core.status?.index?.numDocs;
  return n === undefined || n === null ? null : n;
});

function fmt(n) {
  if (n === undefined || n === null || n === '') return '—';
  const num = Number(n);
  return Number.isFinite(num) ? num.toLocaleString() : String(n);
}

function formatTime(d) {
  try {
    return new Date(d).toLocaleTimeString();
  } catch {
    return '';
  }
}

function formatUptime(ms) {
  if (ms == null || ms === '') return '—';
  const sec = Math.floor(Number(ms) / 1000);
  if (!Number.isFinite(sec) || sec < 0) return String(ms);
  const d = Math.floor(sec / 86400);
  const h = Math.floor((sec % 86400) / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = sec % 60;
  if (d > 0) return `${d}d ${h}h ${m}m`;
  if (h > 0) return `${h}h ${m}m ${s}s`;
  return `${m}m ${s}s`;
}

onMounted(() => {
  if (!ping.lastAt) refreshDashboard();
});
</script>
