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
          NADA-AI paused
        </v-chip>
        <v-chip
          v-else-if="usesNadaAi && nadaAi"
          size="small"
          variant="tonal"
          :color="mismatch ? 'warning' : 'success'"
          :prepend-icon="mismatch ? 'mdi-alert-outline' : 'mdi-check-circle-outline'"
        >
          {{ nadaAi.engine ? `NADA-AI runs ${nadaAi.engine}` : 'NADA-AI has not answered' }}
        </v-chip>
        <v-spacer />
        <v-btn size="small" variant="text" prepend-icon="mdi-refresh" :loading="loading" @click="refresh({ force: true })">
          Refresh
        </v-btn>
        <v-btn size="small" variant="text" :href="settingsUrl" prepend-icon="mdi-cog-outline">Settings</v-btn>
      </template>
    </v-card-text>

    <div v-if="status && usesNadaAi" class="px-4 pb-3 text-caption text-medium-emphasis">
      <div v-if="limitsText">NADA-AI limits (set in NADA-AI): {{ limitsText }}</div>
      <div v-if="nadaAi && nadaAi.source !== 'info'">
        <template v-if="nadaAi.source === 'stale'">NADA-AI did not answer just now; what it can serve is from its last answer.</template>
        <template v-else>NADA-AI has not answered yet, so what it runs and can serve is not known.</template>
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
const { status, error, loading, refresh, engineLabel, usesNadaAi, breakerOpen, mismatch } = useEngineStatus();

const nadaAi = computed(() => status.value?.nada_ai || null);

/** NADA-AI's paging and search limits, read-only: they are NADA-AI settings (GET /info limits). */
const limitsText = computed(() => {
  const l = nadaAi.value?.limits;
  if (!l) return '';
  const n = (v) => Number(v).toLocaleString();
  const parts = [];
  if (l.max_limit) parts.push(`up to ${n(l.max_limit)} results per page`);
  if (l.variables_max_offset || l.max_offset) {
    const numbered = [
      l.variables_max_offset ? `${n(l.variables_max_offset)} variables` : null,
      l.max_offset ? `${n(l.max_offset)} studies` : null,
    ].filter(Boolean).join(' / ');
    parts.push(`numbered pages up to ${numbered}, then Next by cursor`);
  }
  if (l.variables_count_cap) parts.push(`matches counted exactly up to ${n(l.variables_count_cap)}`);
  if (l.search_timeout_seconds) parts.push(`searches stop after ${l.search_timeout_seconds} s`);
  return parts.join(' · ');
});
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
