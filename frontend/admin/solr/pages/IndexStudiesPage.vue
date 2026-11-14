<template>
  <BatchIndexPanel
    title="Index studies"
    description="Imports survey documents in batches. When the batch returns no more rows, Solr is committed."
    v-model:start-row="startRow"
    v-model:batch-size="batchSize"
    :total="total"
    :processed="processed"
    :last-row="lastRow"
    :running="indexing.studies"
    :stopped="stopped"
    :done="done"
    :error="error"
    :can-resume="stopped && lastRow > 0"
    @start="start"
    @stop="stop"
    @resume="resume"
    @use-last="startRow = lastRow"
  />
</template>

<script setup>
import { onMounted, ref } from 'vue';
import BatchIndexPanel from '../components/BatchIndexPanel.vue';
import { useSolrOps } from '../composables/useSolrOps.js';

defineOptions({ name: 'SolrIndexStudiesPage' });

const { indexing, counts, refreshCounts, showToast, commit, api } = useSolrOps();

const startRow = ref(0);
const batchSize = ref(10);
const processed = ref(0);
const lastRow = ref(0);
const stopped = ref(false);
const done = ref(false);
const error = ref('');
const total = ref(null);

async function runLoop(fromRow, already) {
  indexing.studies = true;
  stopped.value = false;
  done.value = false;
  error.value = '';
  let cursor = fromRow;
  let count = already;

  try {
    while (indexing.studies) {
      const data = await api().importSurveysBatch(cursor, batchSize.value);
      if (!indexing.studies) break;
      const result = data.result || {};
      const next = Number(result.last_row_id) || 0;
      const rows = Number(result.rows_processed) || 0;
      if (next > 0) {
        count += rows;
        processed.value = count;
        lastRow.value = next;
        cursor = next;
        continue;
      }
      processed.value = count;
      indexing.studies = false;
      done.value = true;
      await commit();
      await refreshCounts();
      showToast(`Indexed ${count.toLocaleString()} studies`);
      break;
    }
  } catch (e) {
    if (indexing.studies) {
      error.value = e.message;
      indexing.studies = false;
      showToast(e.message, 'error');
    }
  }
}

function start() {
  processed.value = 0;
  lastRow.value = 0;
  runLoop(startRow.value, 0);
}

function stop() {
  indexing.studies = false;
  stopped.value = true;
}

function resume() {
  runLoop(lastRow.value || startRow.value, processed.value);
}

onMounted(async () => {
  await refreshCounts();
  const db = counts.db || {};
  total.value = db.surveys ?? db.datasets ?? db.studies ?? null;
});
</script>
