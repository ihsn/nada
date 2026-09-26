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
        <p class="text-caption text-medium-emphasis mt-2 mb-0">
          Runs the catalog search exactly as the public catalog would with semantic search on (the
          <strong>{{ engine || '—' }}</strong> engine of nada-ai, relevance order), with the search driver's debug output.
        </p>

        <v-expansion-panels variant="accordion" class="mt-3">
          <v-expansion-panel>
            <v-expansion-panel-title class="text-body-2">Filters & options</v-expansion-panel-title>
            <v-expansion-panel-text>
              <v-row dense>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="type"
                    :items="typeOptions"
                    label="Dataset type"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
                <v-col cols="6" md="3">
                  <v-text-field
                    v-model.number="yearFrom"
                    type="number"
                    label="Year from"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
                <v-col cols="6" md="3">
                  <v-text-field
                    v-model.number="yearTo"
                    type="number"
                    label="Year to"
                    clearable
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field v-model.number="limit" type="number" label="Page size" variant="outlined" density="comfortable" hide-details />
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

    <template v-if="result">
      <v-alert v-if="result.semantic_fallback" type="warning" variant="tonal" class="mb-4">
        {{ result.semantic_fallback }}
      </v-alert>
      <v-alert v-if="result.semantic_note" type="info" variant="tonal" class="mb-4">
        {{ result.semantic_note }}
      </v-alert>

      <v-card elevation="1" rounded="lg" class="mb-4">
        <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
          Results
          <v-chip size="x-small" variant="tonal">{{ result.engine }}</v-chip>
          <v-spacer />
          <span class="text-caption text-medium-emphasis font-weight-regular">
            {{ formatCount(result.found) }} found
            <template v-for="(count, key) in result.search_counts_by_type" :key="key">
              · {{ typeLabel(key) }} {{ formatCount(count) }}
            </template>
          </span>
        </v-card-title>
        <v-divider />
        <v-card-text>
          <v-alert v-if="!result.rows.length" type="info" variant="tonal">
            No results for this query and filters.
          </v-alert>
          <v-expansion-panels v-else variant="accordion">
            <v-expansion-panel v-for="(row, i) in result.rows" :key="row.id">
              <v-expansion-panel-title>
                <div class="d-flex align-center flex-wrap ga-2" style="width: 100%">
                  <span class="text-caption text-medium-emphasis">{{ result.offset + i + 1 }}</span>
                  <v-chip size="small" color="primary">{{ typeLabel(row.type) }}</v-chip>
                  <span class="text-body-2">{{ row.title }}</span>
                  <code class="text-caption">{{ row.idno }}</code>
                  <v-spacer />
                  <v-chip
                    v-for="source in matchedBy(row)"
                    :key="source"
                    size="x-small"
                    variant="tonal"
                    :color="source === 'keyword' || source === 'lexical' ? 'secondary' : 'primary'"
                  >
                    {{ source }}
                  </v-chip>
                  <span v-if="score(row) !== null" class="text-caption text-medium-emphasis">
                    score {{ score(row).toFixed(3) }}
                  </span>
                </div>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <p class="text-caption text-medium-emphasis mb-2">
                  {{ row.nation || '—' }} · {{ years(row) }}
                  <template v-if="row.var_found"> · keyword in {{ formatCount(row.var_found) }} variable(s)</template>
                </p>
                <pre class="text-caption" style="white-space: pre-wrap">{{ formatJson(hitDetails(row)) }}</pre>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-card-text>
      </v-card>

      <v-card v-if="result.debug" elevation="1" rounded="lg">
        <v-card-title class="semantic-card-title">Search driver debug</v-card-title>
        <v-divider />
        <v-card-text>
          <pre class="text-caption" style="white-space: pre-wrap">{{ formatJson(result.debug) }}</pre>
        </v-card-text>
      </v-card>
    </template>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { useEngine } from '../composables/useEngine.js';
import { typeLabel, formatCount } from '../typeLabels.js';

defineOptions({ name: 'SemanticSearchTestPage' });

const { loading, error, errorMessage, search } = useSemanticApi();
const { engine, ensureEngine } = useEngine();
ensureEngine();

const query = ref('');
const type = ref(null);
const yearFrom = ref(null);
const yearTo = ref(null);
const limit = ref(15);
const result = ref(null);

// NADA dataset types (surveys.type), as the catalog tabs filter them
const typeOptions = [
  { title: 'Microdata', value: 'survey' },
  { title: 'Geospatial', value: 'geospatial' },
  { title: 'Document', value: 'document' },
  { title: 'Indicator', value: 'timeseries' },
  { title: 'Table', value: 'table' },
  { title: 'Image', value: 'image' },
  { title: 'Video', value: 'video' },
  { title: 'Script', value: 'script' },
];

function formatJson(value) {
  return JSON.stringify(value ?? {}, null, 2);
}

/** Which side found the study: the drivers' semantic_hit.matched_by (qdrant_db and opensearch). */
function matchedBy(row) {
  return row.semantic_hit?.matched_by || [];
}

function score(row) {
  const s = row.semantic_hit?._score ?? row.semantic_hit?.score;
  return typeof s === 'number' ? s : null;
}

function years(row) {
  const start = row.year_start && Number(row.year_start) ? row.year_start : null;
  const end = row.year_end && Number(row.year_end) ? row.year_end : null;
  if (start && end && start !== end) return `${start}–${end}`;
  return start || end || '—';
}

function hitDetails(row) {
  const details = { id: row.id, semantic_hit: row.semantic_hit ?? null };
  if (row.semantic_document_pages?.length) details.document_pages = row.semantic_document_pages;
  return details;
}

async function runSearch() {
  if (!query.value.trim()) return;
  try {
    result.value = await search({
      query: query.value.trim(),
      type: type.value || undefined,
      from: yearFrom.value || undefined,
      to: yearTo.value || undefined,
      limit: limit.value || 15,
    });
  } catch {
    result.value = null;
  }
}
</script>
