<template>
  <div>
    <v-card elevation="1" rounded="lg" class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="9">
            <v-text-field
              v-model="query"
              label="Query"
              variant="outlined"
              density="comfortable"
              hide-details
              autofocus
              @keydown.enter="runSearch"
            />
          </v-col>
          <v-col cols="12" md="3" class="d-flex align-center">
            <v-btn color="primary" block :loading="loading" :disabled="!query.trim()" @click="runSearch">
              Search
            </v-btn>
          </v-col>
        </v-row>

        <v-expansion-panels variant="accordion" class="mt-3">
          <v-expansion-panel>
            <v-expansion-panel-title class="text-body-2">Filters & options</v-expansion-panel-title>
            <v-expansion-panel-text>
              <v-row dense>
                <v-col cols="12" md="4">
                  <v-select v-model="mode" :items="modeOptions" label="Mode" variant="outlined" density="comfortable" hide-details />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field v-model.number="size" type="number" label="Size" variant="outlined" density="comfortable" hide-details />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="filters.type"
                    :items="typeOptions"
                    label="Type"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
                <v-col cols="6" md="3">
                  <v-text-field
                    v-model.number="filters.year_start"
                    type="number"
                    label="Year start"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
                <v-col cols="6" md="3">
                  <v-text-field
                    v-model.number="filters.year_end"
                    type="number"
                    label="Year end"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="geographiesText"
                    label="Geographies (comma-separated)"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
              </v-row>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </v-card-text>
    </v-card>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4" closable @click:close="error = null">
      {{ errorMessage(error) }}
    </v-alert>

    <v-card v-if="result" elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center">
        Results
        <v-spacer />
        <span class="text-caption text-medium-emphasis font-weight-regular">{{ result.total ?? result.hits.length }} total</span>
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-alert v-if="!result.hits.length" type="info" variant="tonal">
          No hits for this query/filters combination.
        </v-alert>
        <v-expansion-panels v-else variant="accordion">
          <v-expansion-panel v-for="(hit, i) in result.hits" :key="i">
            <v-expansion-panel-title>
              <div class="d-flex align-center flex-wrap ga-2" style="width: 100%">
                <v-chip size="small" color="primary">{{ typeLabel(hit._source?.metadata?.type) }}</v-chip>
                <code class="text-caption">{{ hit._source?.metadata?.idno || hit._id }}</code>
                <v-spacer />
                <span v-if="hit._score !== null && hit._score !== undefined" class="text-caption text-medium-emphasis">
                  score {{ hit._score.toFixed(3) }}
                </span>
              </div>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <p class="text-body-2 mb-2">{{ snippet(hit._source?.page_content) }}</p>
              <pre class="text-caption" style="white-space: pre-wrap">{{ formatMetadata(hit._source?.metadata) }}</pre>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { typeLabel } from '../typeLabels.js';

defineOptions({ name: 'SemanticSearchTestPage' });

const { loading, error, errorMessage, search } = useSemanticApi();

const query = ref('');
const mode = ref('hybrid');
const size = ref(10);
const geographiesText = ref('');
const filters = reactive({ type: null, year_start: null, year_end: null });
const result = ref(null);

const modeOptions = ['keyword', 'vector', 'hybrid'];
// The stored document type vocabulary (what filters.type actually matches
// against) differs from the ingest catalog_type names: catalog_type=survey
// stores as type=microdata, catalog_type=timeseries stores as type=indicator.
const typeOptions = [
  { title: 'Document', value: 'document' },
  { title: 'Indicator', value: 'indicator' },
  { title: 'Microdata', value: 'microdata' },
  { title: 'Geospatial', value: 'geospatial' },
];

function snippet(text) {
  if (!text) return '—';
  const s = String(text).trim();
  return s.length > 240 ? `${s.slice(0, 240)}…` : s;
}

function formatMetadata(metadata) {
  return JSON.stringify(metadata || {}, null, 2);
}

function buildFilters() {
  const f = {};
  if (filters.type) f.type = filters.type;
  if (filters.year_start) f.year_start = filters.year_start;
  if (filters.year_end) f.year_end = filters.year_end;
  const geos = geographiesText.value
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  if (geos.length) f.geographies = geos;
  return Object.keys(f).length ? f : undefined;
}

async function runSearch() {
  if (!query.value.trim()) return;
  try {
    result.value = await search({
      query: query.value.trim(),
      mode: mode.value,
      size: size.value || 10,
      filters: buildFilters(),
      include_facets: false,
    });
  } catch {
    result.value = null;
  }
}
</script>
