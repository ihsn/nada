<template>
  <v-card elevation="1" rounded="lg">
    <v-card-text class="d-flex align-center flex-wrap ga-4 py-3">
      <span class="font-weight-medium">Search engine</span>
      <v-progress-circular v-if="loading && !status" indeterminate size="18" width="2" />
      <span v-else-if="error && !status" class="text-error text-body-2">{{ errorMessage(error) }}</span>
      <template v-else-if="status">
        <v-chip size="small" variant="tonal" color="primary">{{ engineLabel }}</v-chip>
        <span class="text-body-2">
          <template v-for="(row, i) in serving" :key="row.key">
            <template v-if="i > 0"> · </template>{{ row.label }}: <strong :class="row.engine === 'database' ? '' : 'text-primary'">{{ row.engine }}</strong>
          </template>
        </span>
        <v-chip
          v-if="usesNadaAi && breakerOpen"
          size="small"
          variant="tonal"
          color="warning"
          prepend-icon="mdi-pause-circle-outline"
        >
          nada-ai paused
        </v-chip>
        <v-chip
          v-else-if="usesNadaAi && nadaAi"
          size="small"
          variant="tonal"
          :color="mismatch ? 'warning' : 'success'"
          :prepend-icon="mismatch ? 'mdi-alert-outline' : 'mdi-check-circle-outline'"
        >
          {{ nadaAi.engine ? `nada-ai runs ${nadaAi.engine}` : 'nada-ai has not answered' }}
        </v-chip>
        <v-spacer />
        <v-btn size="small" variant="text" prepend-icon="mdi-refresh" :loading="loading" @click="refresh({ force: true })">
          Refresh
        </v-btn>
        <v-btn size="small" variant="text" :href="settingsUrl" prepend-icon="mdi-cog-outline">Settings</v-btn>
      </template>
    </v-card-text>

    <div v-if="status && usesNadaAi" class="px-4 pb-3 text-caption text-medium-emphasis">
      <div v-if="nadaAi && nadaAi.source !== 'info'">
        <template v-if="nadaAi.source === 'stale'">nada-ai did not answer just now; what it can serve is from its last answer.</template>
        <template v-else>nada-ai has not answered yet, so what it runs and can serve is not known.</template>
      </div>
      <div>
        When nada-ai is unavailable a search is
        <strong>{{ status.policy === 'error' ? 'answered with an error' : 'served from the catalog database' }}</strong>
        (Site configurations &gt; Search).
      </div>
      <div v-if="fallbacks.length">
        Served by the database this hour:
        <template v-for="(row, i) in fallbacks" :key="row.kind"><template v-if="i > 0">, </template>{{ row.count }} ({{ row.label }})</template>.
      </div>
    </div>
  </v-card>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { SERVING_LABELS, useEngineStatus } from '../composables/useEngineStatus.js';

defineOptions({ name: 'SemanticEngineStatusCard' });

const { siteUrl } = useAppConfig();
const { errorMessage } = useSemanticApi();
const { status, error, loading, refresh, engineLabel, usesNadaAi, breakerOpen, mismatch, fallbacks } = useEngineStatus();

const nadaAi = computed(() => status.value?.nada_ai || null);
const settingsUrl = computed(() => `${String(siteUrl.value || '').replace(/\/$/, '')}/admin/configurations#/search`);

const SEARCHES = [
  { key: 'studies', label: 'Studies' },
  { key: 'variables', label: 'Variables' },
  { key: 'citations', label: 'Citations' },
];
const serving = computed(() =>
  SEARCHES.map(({ key, label }) => ({ key, label, engine: SERVING_LABELS[status.value?.serves?.[key]] || '—' })),
);

defineExpose({ load: () => refresh({ force: true }) });

onMounted(() => refresh());
</script>
