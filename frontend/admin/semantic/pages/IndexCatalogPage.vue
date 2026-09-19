<template>
  <div>
    <v-alert v-if="!canEdit" type="info" variant="tonal" class="mb-4">
      You have view-only access — indexing actions are disabled.
    </v-alert>

    <v-card elevation="1" rounded="lg" class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="4">
            <label class="text-caption text-medium-emphasis">Catalog type</label>
            <v-select
              v-model="catalogType"
              :items="catalogTypeOptions"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>
          <v-col cols="6" md="2">
            <label class="text-caption text-medium-emphasis">Page size</label>
            <v-text-field v-model.number="ps" type="number" variant="outlined" density="comfortable" hide-details />
          </v-col>
          <v-col cols="6" md="2">
            <label class="text-caption text-medium-emphasis">Limit (optional)</label>
            <v-text-field
              v-model.number="limit"
              type="number"
              variant="outlined"
              density="comfortable"
              placeholder="smoke test"
              hide-details
            />
          </v-col>
          <v-col cols="12" md="4" class="d-flex align-center">
            <v-checkbox v-model="force" label="Force (re-fetch/re-embed cached ids)" hide-details density="compact" />
          </v-col>
        </v-row>
        <v-row dense>
          <v-col cols="12" md="8">
            <v-checkbox
              v-model="resume"
              label="Resume (skip idnos already completed by a stopped/crashed run of this catalog type)"
              hide-details
              density="compact"
            />
          </v-col>
        </v-row>

        <div class="mt-4">
          <v-btn
            color="primary"
            prepend-icon="mdi-play"
            :loading="loading"
            :disabled="!canEdit"
            @click="submit"
          >
            {{ catalogType === '__all__' ? 'Index every type' : `Index ${typeLabel(catalogType)}` }}
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4" closable @click:close="error = null">
      {{ errorMessage(error) }}
    </v-alert>

    <v-card v-if="lastResult" elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title">Submitted</v-card-title>
      <v-divider />
      <v-card-text>
        <v-table density="comfortable">
          <thead>
            <tr>
              <th>Catalog type</th>
              <th>Job id</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in submittedJobs" :key="row.job_id">
              <td>{{ typeLabel(row.catalog_type) }}</td>
              <td><code>{{ row.job_id }}</code></td>
              <td>
                <v-chip size="small" :color="row.already_running ? 'warning' : 'primary'">
                  {{ row.already_running ? 'already running' : row.status }}
                </v-chip>
              </td>
            </tr>
          </tbody>
        </v-table>
        <div class="mt-3">
          <router-link to="/jobs" class="text-decoration-none text-primary">Watch progress on Jobs →</router-link>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { typeLabel } from '../typeLabels.js';

defineOptions({ name: 'SemanticIndexCatalogPage' });

const { canEdit } = useAppConfig();
const { loading, error, errorMessage, indexOne, indexAll } = useSemanticApi();

const catalogTypeOptions = [
  { title: 'All types', value: '__all__' },
  { title: 'Document', value: 'document' },
  { title: 'Indicator', value: 'timeseries' },
  { title: 'Microdata', value: 'survey' },
  { title: 'Geospatial', value: 'geospatial' },
];

const catalogType = ref('__all__');
const ps = ref(100);
const limit = ref(null);
const force = ref(false);
const resume = ref(false);
const lastResult = ref(null);

const submittedJobs = computed(() => {
  if (!lastResult.value) return [];
  if (Array.isArray(lastResult.value.jobs)) {
    // /admin/ingest/from-catalog/all shape
    return lastResult.value.jobs.map((j) => ({
      catalog_type: j.catalog_type,
      job_id: j.job?.id,
      status: j.job?.status,
      already_running: j.already_running,
    }));
  }
  // /admin/ingest/from-catalog shape (single job envelope)
  return [
    {
      catalog_type: lastResult.value.params?.catalog_type || catalogType.value,
      job_id: lastResult.value.id,
      status: lastResult.value.status,
      already_running: !!lastResult.value.was_already_running,
    },
  ];
});

async function submit() {
  const body = {
    ps: ps.value || 100,
    force: !!force.value,
    resume: !!resume.value,
  };
  if (limit.value) body.limit = limit.value;

  try {
    if (catalogType.value === '__all__') {
      lastResult.value = await indexAll(body);
    } else {
      lastResult.value = await indexOne({ ...body, catalog_type: catalogType.value });
    }
  } catch (e) {
    // 409 = single-flighted job already running for this type — not a failure,
    // nada-ai relays {detail, job} so we can still show it as "already running".
    if (e?.response?.status === 409 && e.response.data?.job) {
      lastResult.value = { ...e.response.data.job, was_already_running: true };
      error.value = null;
    } else {
      lastResult.value = null;
    }
  }
}
</script>
