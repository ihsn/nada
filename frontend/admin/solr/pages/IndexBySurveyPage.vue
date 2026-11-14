<template>
  <div class="admin-solr-stack">
    <v-card elevation="1">
      <v-card-title class="solr-card-title">Index variables by survey</v-card-title>
      <v-divider />
      <v-card-text class="pa-5">
        <p class="text-body-2 text-medium-emphasis by-survey-desc">
          Leave survey id empty to walk every survey. When one survey finishes, Solr is committed
          (or after the full catalog walk).
        </p>

        <v-row dense class="by-survey-fields">
          <v-col cols="12" sm="6" md="4">
            <div class="solr-field">
              <label class="solr-field-label" for="solr-by-survey-id">Survey id (optional)</label>
              <v-text-field
                id="solr-by-survey-id"
                v-model="surveyId"
                variant="outlined"
                density="comfortable"
                hide-details
                :disabled="indexing.bySurvey"
              />
              <span class="solr-field-hint">Empty = all surveys</span>
            </div>
          </v-col>
          <v-col cols="12" sm="6" md="4">
            <div class="solr-field">
              <label class="solr-field-label" for="solr-by-survey-chunk">Chunk size</label>
              <v-text-field
                id="solr-by-survey-chunk"
                v-model.number="chunkSize"
                type="number"
                variant="outlined"
                density="comfortable"
                hide-details
                :disabled="indexing.bySurvey"
              />
            </div>
          </v-col>
          <v-col cols="12" sm="6" md="4">
            <div class="solr-field">
              <label class="solr-field-label" for="solr-by-survey-start">
                Start after variable uid
              </label>
              <v-text-field
                id="solr-by-survey-start"
                v-model.number="startId"
                type="number"
                variant="outlined"
                density="comfortable"
                hide-details
                :disabled="indexing.bySurvey"
              />
            </div>
          </v-col>
        </v-row>

        <div class="by-survey-actions d-flex flex-wrap ga-3">
          <v-btn
            color="primary"
            prepend-icon="mdi-play"
            :disabled="indexing.bySurvey"
            :loading="indexing.bySurvey"
            @click="start"
          >
            Start
          </v-btn>
          <v-btn
            color="warning"
            variant="tonal"
            prepend-icon="mdi-pause"
            :disabled="!indexing.bySurvey"
            @click="stop"
          >
            Stop
          </v-btn>
        </div>

        <div v-if="mode === 'all' && totalSurveys > 0" class="mb-3 text-body-2">
          Survey {{ Math.min(currentSurveyIndex + 1, totalSurveys) }} of
          {{ totalSurveys.toLocaleString() }}
          <span v-if="currentSurveyId" class="text-medium-emphasis">
            (id {{ currentSurveyId }})
          </span>
        </div>

        <div v-if="progress.total_variables || indexing.bySurvey || done || error">
          <div class="d-flex justify-space-between text-body-2 mb-1">
            <span>
              Variables {{ Number(progress.processed || 0).toLocaleString() }}
              <template v-if="progress.total_variables">
                / {{ Number(progress.total_variables).toLocaleString() }}
              </template>
            </span>
            <span class="text-medium-emphasis">
              {{ Number(progress.percentage || 0).toFixed(1) }}%
            </span>
          </div>
          <v-progress-linear
            :model-value="Number(progress.percentage) || 0"
            height="10"
            rounded
            color="primary"
          />
        </div>

        <v-alert v-if="stopped" class="mt-3" type="warning" density="compact" variant="tonal">
          Stopped.
        </v-alert>
        <v-alert v-if="error" class="mt-3" type="error" density="compact" variant="tonal">
          {{ error }}
        </v-alert>
        <v-alert v-if="done" class="mt-3" type="success" density="compact" variant="tonal">
          Finished. A Solr commit was requested.
        </v-alert>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useSolrOps } from '../composables/useSolrOps.js';

defineOptions({ name: 'SolrIndexBySurveyPage' });

const { indexing, showToast, commit, api } = useSolrOps();

const surveyId = ref('');
const chunkSize = ref(200);
const startId = ref(0);
const mode = ref('one');
const progress = reactive({ processed: 0, total_variables: 0, percentage: 0 });
const stopped = ref(false);
const done = ref(false);
const error = ref('');
const allIds = ref([]);
const currentSurveyIndex = ref(0);
const totalSurveys = ref(0);
const currentSurveyId = ref('');

function stop() {
  indexing.bySurvey = false;
  stopped.value = true;
}

async function indexOneSurvey(sid, fromUid, { partOfBatch = false } = {}) {
  let cursor = fromUid;
  currentSurveyId.value = String(sid);

  while (indexing.bySurvey) {
    const data = await api().importVariablesBySurveyBatch(sid, cursor, chunkSize.value);
    if (!indexing.bySurvey) return false;

    const result = data.result || {};
    progress.total_variables = Number(result.total_variables) || progress.total_variables;
    progress.processed = Number(result.rows_processed) || 0;
    progress.percentage = Number(result.progress_percentage) || 0;
    const next = Number(result.last_row_id) || 0;

    if (progress.percentage >= 100 || progress.processed === 0 || !next) {
      if (partOfBatch) return true;
      indexing.bySurvey = false;
      done.value = true;
      await commit();
      showToast(`Indexed variables for survey ${sid}`);
      return true;
    }
    cursor = next;
  }
  return false;
}

async function processAll() {
  while (indexing.bySurvey && currentSurveyIndex.value < allIds.value.length) {
    const sid = allIds.value[currentSurveyIndex.value];
    progress.processed = 0;
    progress.percentage = 0;
    progress.total_variables = 0;
    const ok = await indexOneSurvey(sid, 0, { partOfBatch: true });
    if (!indexing.bySurvey) return;
    if (!ok) return;
    currentSurveyIndex.value += 1;
  }
  if (indexing.bySurvey) {
    indexing.bySurvey = false;
    done.value = true;
    await commit();
    showToast(`Indexed variables for ${totalSurveys.value.toLocaleString()} surveys`);
  }
}

async function start() {
  indexing.bySurvey = true;
  stopped.value = false;
  done.value = false;
  error.value = '';
  progress.processed = 0;
  progress.total_variables = 0;
  progress.percentage = 0;

  try {
    const sid = String(surveyId.value || '').trim();
    if (sid) {
      mode.value = 'one';
      await indexOneSurvey(sid, startId.value || 0);
    } else {
      mode.value = 'all';
      const data = await api().getAllSurveyIds();
      allIds.value = data.result?.survey_ids || data.result?.ids || [];
      if (!Array.isArray(allIds.value)) allIds.value = [];
      totalSurveys.value = allIds.value.length;
      currentSurveyIndex.value = 0;
      if (!totalSurveys.value) {
        indexing.bySurvey = false;
        showToast('No surveys found', 'warning');
        return;
      }
      await processAll();
    }
  } catch (e) {
    if (indexing.bySurvey) {
      error.value = e.message;
      indexing.bySurvey = false;
      showToast(e.message, 'error');
    }
  }
}
</script>

<style scoped>
.by-survey-desc {
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.by-survey-fields {
  margin-bottom: 0 !important;
}

.by-survey-fields :deep(.v-col) {
  padding-bottom: 12px;
}

.by-survey-actions {
  margin-top: 1.25rem;
  margin-bottom: 1rem;
}
</style>
