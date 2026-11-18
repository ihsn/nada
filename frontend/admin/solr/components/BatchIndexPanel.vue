<template>
  <v-card elevation="1">
    <v-card-title class="solr-card-title">{{ title }}</v-card-title>
    <v-divider />
    <v-card-text class="pa-5">
      <p v-if="description" class="text-body-2 text-medium-emphasis batch-index-desc">
        {{ description }}
      </p>

      <v-row class="batch-index-fields" dense>
        <v-col cols="12" sm="6" md="4">
          <div class="solr-field">
            <label class="solr-field-label" for="solr-batch-start-row">Start after id</label>
            <v-text-field
              id="solr-batch-start-row"
              :model-value="startRow"
              type="number"
              variant="outlined"
              density="comfortable"
              hide-details
              :disabled="running"
              @update:model-value="$emit('update:startRow', Number($event) || 0)"
            />
            <span class="solr-field-hint">Use 0 to start from the beginning</span>
          </div>
        </v-col>
        <v-col cols="12" sm="6" md="4">
          <div class="solr-field">
            <label class="solr-field-label" for="solr-batch-size">Batch size</label>
            <v-text-field
              id="solr-batch-size"
              :model-value="batchSize"
              type="number"
              variant="outlined"
              density="comfortable"
              hide-details
              :disabled="running"
              @update:model-value="$emit('update:batchSize', Math.max(1, Number($event) || 1))"
            />
          </div>
        </v-col>
        <v-col v-if="total != null" cols="12" sm="6" md="4">
          <div class="solr-field">
            <div class="solr-field-label">In database</div>
            <div class="text-body-1 font-weight-medium pt-2">
              {{ Number(total).toLocaleString() }}
            </div>
          </div>
        </v-col>
      </v-row>

      <div class="batch-index-actions d-flex flex-wrap ga-3">
        <v-btn
          color="primary"
          prepend-icon="mdi-play"
          :disabled="running"
          :loading="running && !stopped"
          @click="$emit('start')"
        >
          Start
        </v-btn>
        <v-btn
          color="warning"
          variant="tonal"
          prepend-icon="mdi-pause"
          :disabled="!running"
          @click="$emit('stop')"
        >
          Stop
        </v-btn>
        <v-btn
          variant="tonal"
          prepend-icon="mdi-play-outline"
          :disabled="running || !canResume"
          @click="$emit('resume')"
        >
          Resume
        </v-btn>
        <v-btn
          variant="text"
          prepend-icon="mdi-skip-forward"
          :disabled="running || lastRow <= 0"
          @click="$emit('use-last')"
        >
          Use last id as start
        </v-btn>
      </div>

      <div v-if="processed > 0 || running || stopped" class="batch-index-progress">
        <div class="d-flex justify-space-between text-body-2 mb-2">
          <span>
            Indexed {{ processed.toLocaleString() }}
            <template v-if="total">of {{ Number(total).toLocaleString() }}</template>
          </span>
          <span v-if="lastRow > 0" class="text-medium-emphasis">
            Last id {{ lastRow.toLocaleString() }}
          </span>
        </div>
        <v-progress-linear
          :model-value="progressPct"
          :indeterminate="running && !total"
          height="10"
          rounded
          color="primary"
        />
        <v-alert
          v-if="stopped"
          class="mt-4"
          type="warning"
          density="comfortable"
          variant="tonal"
        >
          Stopped. Resume continues from the last processed id.
        </v-alert>
        <v-alert
          v-if="error"
          class="mt-4"
          type="error"
          density="comfortable"
          variant="tonal"
        >
          {{ error }}
        </v-alert>
        <v-alert
          v-if="done && !running && !stopped"
          class="mt-4"
          type="success"
          density="comfortable"
          variant="tonal"
        >
          Finished. A Solr commit was requested.
        </v-alert>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'BatchIndexPanel' });

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  startRow: { type: Number, default: 0 },
  batchSize: { type: Number, default: 10 },
  total: { type: [Number, null], default: null },
  processed: { type: Number, default: 0 },
  lastRow: { type: Number, default: 0 },
  running: { type: Boolean, default: false },
  stopped: { type: Boolean, default: false },
  done: { type: Boolean, default: false },
  error: { type: String, default: '' },
  canResume: { type: Boolean, default: false },
});

defineEmits(['update:startRow', 'update:batchSize', 'start', 'stop', 'resume', 'use-last']);

const progressPct = computed(() => {
  if (!props.total) return props.running ? 0 : 100;
  return Math.min(100, (props.processed / props.total) * 100);
});
</script>

<style scoped>
.batch-index-desc {
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.batch-index-fields {
  margin-bottom: 0 !important;
}

.batch-index-fields :deep(.v-col) {
  padding-bottom: 12px;
}

.batch-index-actions {
  margin-top: 1.25rem;
  margin-bottom: 0.25rem;
}

.batch-index-progress {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}
</style>
