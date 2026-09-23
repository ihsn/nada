<template>
  <div>
    <Teleport to="#semantic-page-actions">
      <v-btn v-if="engine === 'qdrant'" variant="outlined" prepend-icon="mdi-refresh" :loading="loading" @click="load">
        Refresh
      </v-btn>
    </Teleport>

    <v-progress-linear v-if="engineLoading" indeterminate color="primary" class="mb-4" />

    <v-alert v-else-if="engine && engine !== 'qdrant'" type="info" variant="tonal" class="mb-4">
      This page shows Qdrant's own collection info and isn't available for the <strong>{{ engine }}</strong> engine
      this deployment is running.
    </v-alert>

    <template v-else>
      <v-alert v-if="error" type="error" variant="tonal" class="mb-4" closable @click:close="error = null">
        {{ errorMessage(error) }}
      </v-alert>

      <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

      <v-row v-else-if="info">
        <v-col cols="12" md="6">
          <v-card elevation="1" rounded="lg">
            <v-card-title class="semantic-card-title">Collection</v-card-title>
            <v-divider />
            <v-card-text>
              <v-table density="compact">
                <tbody>
                  <tr><td>Name</td><td class="text-right">{{ raw.collection }}</td></tr>
                  <tr><td>Status</td><td class="text-right">{{ info.status }}</td></tr>
                  <tr><td>Points indexed</td><td class="text-right">{{ info.points_count }}</td></tr>
                  <tr><td>Indexed vectors</td><td class="text-right">{{ info.indexed_vectors_count }}</td></tr>
                  <tr><td>Segments</td><td class="text-right">{{ info.segments_count }}</td></tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card elevation="1" rounded="lg">
            <v-card-title class="semantic-card-title">Vectors</v-card-title>
            <v-divider />
            <v-card-text>
              <v-table density="compact">
                <tbody>
                  <tr><td>Dense size</td><td class="text-right">{{ vectorSize ?? '—' }}</td></tr>
                  <tr><td>Distance</td><td class="text-right">{{ vectorDistance ?? '—' }}</td></tr>
                  <tr><td>Sparse (BM25)</td><td class="text-right">{{ sparseNames.length ? sparseNames.join(', ') : 'off' }}</td></tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12">
          <v-card elevation="1" rounded="lg">
            <v-card-title class="semantic-card-title d-flex align-center">
              Payload schema
              <v-spacer />
              <span class="text-caption text-medium-emphasis font-weight-regular">{{ payloadFields.length }} fields</span>
            </v-card-title>
            <v-divider />
            <v-card-text>
              <v-table density="compact">
                <thead>
                  <tr><th>Field</th><th>Type</th><th class="text-right">Indexed points</th></tr>
                </thead>
                <tbody>
                  <tr v-for="f in payloadFields" :key="f.name">
                    <td><code>{{ f.name }}</code></td>
                    <td>{{ f.data_type }}</td>
                    <td class="text-right">{{ f.points }}</td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { useEngine } from '../composables/useEngine.js';

defineOptions({ name: 'SemanticCollectionInfoPage' });

const { loading, error, errorMessage, getCollection } = useSemanticApi();
const { engine, engineLoading, ensureEngine } = useEngine();
const raw = ref(null);

const info = computed(() => raw.value?.info || null);

const vectors = computed(() => info.value?.config?.params?.vectors);
const vectorSize = computed(() => (vectors.value && typeof vectors.value === 'object' ? vectors.value.size : undefined));
const vectorDistance = computed(() => (vectors.value && typeof vectors.value === 'object' ? vectors.value.distance : undefined));
const sparseNames = computed(() => Object.keys(info.value?.config?.params?.sparse_vectors || {}));

const payloadFields = computed(() => {
  const schema = info.value?.payload_schema || {};
  return Object.entries(schema).map(([name, def]) => ({
    name,
    data_type: def?.data_type,
    points: def?.points ?? 0,
  }));
});

async function load() {
  try {
    raw.value = await getCollection();
  } catch {
    raw.value = null;
  }
}

onMounted(async () => {
  await ensureEngine();
  if (engine.value === 'qdrant') await load();
});
</script>
