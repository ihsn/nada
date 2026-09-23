<template>
  <v-card elevation="1" rounded="lg">
    <div class="d-flex align-center flex-wrap ga-2 pa-3">
      <v-chip
        v-for="pill in pills"
        :key="pill.key"
        size="small"
        variant="tonal"
        :color="pill.color"
        :prepend-icon="pill.icon"
      >
        {{ pill.text }}
      </v-chip>
      <v-spacer />
      <v-btn
        v-if="needsWarmup && canEdit"
        size="small"
        color="primary"
        variant="tonal"
        prepend-icon="mdi-power"
        :loading="warmingUp"
        @click="$emit('warmup')"
      >
        Warm up
      </v-btn>
      <v-btn
        size="small"
        variant="text"
        :append-icon="detailsOpen ? 'mdi-chevron-up' : 'mdi-chevron-down'"
        @click="detailsOpen = !detailsOpen"
      >
        Details
      </v-btn>
      <v-btn
        size="small"
        variant="text"
        prepend-icon="mdi-refresh"
        :loading="loading"
        @click="$emit('refresh')"
      >
        Refresh
      </v-btn>
    </div>

    <v-expand-transition>
      <div v-if="detailsOpen">
        <v-divider />
        <v-alert v-if="error" type="error" variant="tonal" density="compact" class="ma-3">
          {{ error }}
        </v-alert>
        <v-row dense class="pa-4">
          <v-col cols="12" md="6">
            <div class="text-subtitle-2 mb-2">API</div>
            <template v-if="overview?.health?.ok">
              <div class="text-body-2">Backend: <strong>{{ overview.health.data?.backend || '—' }}</strong></div>
              <div class="text-body-2">
                Collection: <strong>{{ overview.health.data?.collection || '—' }}</strong>
                <v-icon
                  :icon="overview.health.data?.collection_exists ? 'mdi-check' : 'mdi-close'"
                  :color="overview.health.data?.collection_exists ? 'success' : 'error'"
                  size="16"
                  class="ml-1"
                />
              </div>
            </template>
            <v-alert v-else-if="overview?.health" type="error" variant="tonal" density="compact">
              {{ overview.health.error }}
            </v-alert>
            <v-progress-linear v-else indeterminate color="primary" />
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-subtitle-2 mb-2">Embeddings</div>
            <template v-if="overview?.embeddings?.ok">
              <div v-for="row in embeddingRows" :key="row.label" class="text-body-2">
                {{ row.label }}: <strong>{{ row.value }}</strong>
              </div>
              <div v-if="!embeddingRows.length" class="text-body-2 text-medium-emphasis">No details yet.</div>
            </template>
            <v-alert v-else-if="overview?.embeddings" type="error" variant="tonal" density="compact">
              {{ overview.embeddings.error }}
            </v-alert>
            <v-progress-linear v-else indeterminate color="primary" />
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-subtitle-2 mb-2">Collection</div>
            <template v-if="overview?.collection?.ok">
              <div class="text-body-2">Points indexed: <strong>{{ formatCount(collectionInfo.points_count) }}</strong></div>
              <div class="text-body-2">Vector size: <strong>{{ collectionInfo.vector_size ?? '—' }}</strong></div>
              <div class="text-body-2">Sparse (BM25): <strong>{{ collectionInfo.sparse ? 'on' : 'off' }}</strong></div>
              <div class="text-body-2">Status: <strong>{{ collectionInfo.status ?? '—' }}</strong></div>
              <router-link to="/collection" class="text-decoration-none text-primary text-caption">
                Full collection info →
              </router-link>
            </template>
            <div v-else-if="engine && engine !== 'qdrant'" class="text-body-2 text-medium-emphasis">
              Not available for the {{ engine }} engine.
            </div>
            <v-alert v-else-if="overview?.collection" type="error" variant="tonal" density="compact">
              {{ overview.collection.error }}
            </v-alert>
            <v-progress-linear v-else indeterminate color="primary" />
          </v-col>

          <v-col cols="12" md="6">
            <div class="text-subtitle-2 mb-2">Embedding drift</div>
            <template v-if="overview?.drift?.ok">
              <v-alert
                v-if="overview.drift.data?.dimension_match === false"
                type="warning"
                variant="tonal"
                density="compact"
                class="mb-2"
              >
                {{ overview.drift.data?.warning || 'Configured model dimension does not match the stored collection.' }}
              </v-alert>
              <v-chip
                v-else-if="overview.drift.data?.dimension_match === true"
                color="success"
                size="small"
                class="mb-2"
              >
                Dimensions match
              </v-chip>
              <div class="text-body-2">Configured: <strong>{{ overview.drift.data?.configured_dimension ?? 'not loaded yet' }}</strong></div>
              <div class="text-body-2">Stored: <strong>{{ overview.drift.data?.stored_dimension ?? '—' }}</strong></div>
            </template>
            <v-alert v-else-if="overview?.drift" type="error" variant="tonal" density="compact">
              {{ overview.drift.error }}
            </v-alert>
            <v-progress-linear v-else indeterminate color="primary" />
          </v-col>
        </v-row>
      </div>
    </v-expand-transition>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
import { formatCount } from '../typeLabels.js';

defineOptions({ name: 'SemanticHealthStrip' });

const props = defineProps({
  overview: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
  canEdit: { type: Boolean, default: false },
  warmingUp: { type: Boolean, default: false },
});

defineEmits(['refresh', 'warmup']);

const detailsOpen = ref(false);

/** From the already-fixed GET /health (backend/collection/collection_exists are the same field names for every
 * engine now) — used to tell the Collection card/pill "not applicable" apart from "actually erroring", since the
 * separate GET /admin/qdrant/collection probe below is genuinely Qdrant-only and always errors for any other
 * engine. */
const engine = computed(() => props.overview?.health?.data?.backend || null);

const collectionInfo = computed(() => {
  const info = props.overview?.collection?.data?.info || {};
  const vectors = info?.config?.params?.vectors;
  return {
    points_count: info.points_count,
    status: info.status,
    vector_size: vectors && typeof vectors === 'object' ? vectors.size : undefined,
    sparse: !!(info?.config?.params?.sparse_vectors && Object.keys(info.config.params.sparse_vectors).length),
  };
});

const embeddingsData = computed(() => props.overview?.embeddings?.data || {});

const needsWarmup = computed(() => embeddingsData.value?.status === 'not_initialized');

const embeddingRows = computed(() => {
  const data = embeddingsData.value;
  if (!data || typeof data !== 'object') return [];
  const pairs = [
    ['status', 'Status'],
    ['model', 'Model'],
    ['model_name', 'Model'],
    ['model_id', 'Model'],
    ['dimension', 'Dimension'],
    ['dim', 'Dimension'],
    ['device', 'Device'],
    ['backend', 'Backend'],
  ];
  const seen = new Set();
  const rows = [];
  for (const [key, label] of pairs) {
    if (data[key] == null || data[key] === '' || seen.has(label)) continue;
    seen.add(label);
    rows.push({ label, value: String(data[key]) });
  }
  return rows;
});

function pillColor(ok, warn) {
  if (ok === false) return 'error';
  if (warn) return 'warning';
  if (ok) return 'success';
  return 'grey';
}

const pills = computed(() => {
  const ov = props.overview;
  const healthOk = ov?.health?.ok === true;
  const healthStatus = ov?.health?.data?.status || (healthOk ? 'ok' : null);

  const embOk = ov?.embeddings?.ok === true;
  const embStatus = embeddingsData.value?.status;
  const embWarn = embStatus === 'not_initialized';
  const embText = !ov?.embeddings
    ? 'Embeddings…'
    : !embOk
      ? 'Embeddings error'
      : embWarn
        ? 'Embeddings not loaded'
        : embStatus === 'ready'
          ? 'Embeddings ready'
          : `Embeddings ${embStatus || 'ok'}`;

  const colNotApplicable = engine.value && engine.value !== 'qdrant';
  const colOk = ov?.collection?.ok === true;
  const points = collectionInfo.value.points_count;
  const colText = colNotApplicable
    ? 'Collection n/a'
    : !ov?.collection
      ? 'Collection…'
      : !colOk
        ? 'Collection error'
        : `${formatCount(points)} points`;

  const drift = ov?.drift?.data;
  const driftOk = ov?.drift?.ok === true;
  const mismatch = drift?.dimension_match === false;
  const driftText = !ov?.drift
    ? 'Dimensions…'
    : !driftOk
      ? 'Drift check failed'
      : mismatch
        ? 'Dimension mismatch'
        : drift?.dimension_match === true
          ? 'Dimensions match'
          : 'Dimensions unknown';

  return [
    {
      key: 'api',
      text: !ov?.health ? 'API…' : healthOk ? `API ${healthStatus}` : 'API error',
      color: pillColor(ov?.health ? healthOk : null),
      icon: healthOk ? 'mdi-check-circle' : 'mdi-heart-pulse',
    },
    {
      key: 'emb',
      text: embText,
      color: pillColor(ov?.embeddings ? embOk && !embWarn : null, embWarn),
      icon: 'mdi-vector-triangle',
    },
    {
      key: 'col',
      text: colText,
      color: colNotApplicable ? 'grey' : pillColor(ov?.collection ? colOk : null),
      icon: 'mdi-database-outline',
    },
    {
      key: 'drift',
      text: driftText,
      color: pillColor(ov?.drift ? driftOk && !mismatch : null, mismatch),
      icon: 'mdi-ruler-square-compass',
    },
  ];
});
</script>
