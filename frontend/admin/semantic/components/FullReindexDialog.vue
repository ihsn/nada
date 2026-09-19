<template>
  <v-dialog :model-value="modelValue" max-width="640" @update:model-value="$emit('update:modelValue', $event)">
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center">
        Full reindex
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" size="small" @click="close" />
      </v-card-title>
      <v-card-subtitle class="text-wrap pb-2">
        Pulls ids straight from the catalog API — independent of the diff on this page. Use this after a
        parser/content fix, or to rebuild a type from scratch; for routine drift, use Reconcile now instead.
      </v-card-subtitle>

      <v-card-text>
        <v-alert v-if="!canEdit" type="info" variant="tonal" density="compact" class="mb-4">
          You have view-only access — indexing actions are disabled.
        </v-alert>

        <div class="mb-4">
          <label class="semantic-field-label" for="full-reindex-catalog-type">Catalog type</label>
          <v-select
            id="full-reindex-catalog-type"
            v-model="catalogType"
            :items="catalogTypeOptions"
            variant="outlined"
            density="comfortable"
            hide-details
          />
        </div>

        <v-row dense class="mb-4">
          <v-col cols="6">
            <label class="semantic-field-label" for="full-reindex-ps">Page size</label>
            <v-text-field
              id="full-reindex-ps"
              v-model.number="ps"
              type="number"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>
          <v-col cols="6">
            <label class="semantic-field-label" for="full-reindex-limit">Limit (optional)</label>
            <v-text-field
              id="full-reindex-limit"
              v-model.number="limit"
              type="number"
              placeholder="smoke test a few ids"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>
        </v-row>

        <div class="d-flex flex-column ga-2 mt-2">
          <div class="border rounded-lg pa-3">
            <v-checkbox v-model="force" density="compact" hide-details class="mb-0">
              <template #label><span class="font-weight-medium">Force</span></template>
            </v-checkbox>
            <p class="text-caption text-medium-emphasis mb-0 ml-8">
              Re-fetch and re-embed every id, including ones already indexed. Needed to pick up a parser or
              content fix — without it, already-indexed ids are skipped.
            </p>
          </div>
          <div class="border rounded-lg pa-3">
            <v-checkbox v-model="resume" density="compact" hide-details class="mb-0">
              <template #label><span class="font-weight-medium">Resume</span></template>
            </v-checkbox>
            <p class="text-caption text-medium-emphasis mb-0 ml-8">
              Skip ids already completed by a stopped or crashed run of this catalog type, instead of
              starting over from the beginning.
            </p>
          </div>
        </div>

        <v-alert v-if="force" type="warning" variant="tonal" density="compact" class="mt-4">
          With <strong>Force</strong> checked, this re-indexes every catalog entry
          {{ catalogType === '__all__' ? 'of every type' : `of type "${catalogType}"` }}, not just the ones
          currently missing — heavier than Reconcile now, and runs as a background job you can watch (and
          cancel) on Jobs.
        </v-alert>

        <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mt-4" closable @click:close="error = null">
          {{ errorMessage(error) }}
        </v-alert>
        <v-alert v-if="submitted" type="success" variant="tonal" density="compact" class="mt-4">
          Submitted. <router-link to="/jobs" class="text-decoration-none" @click="close">Watch progress on Jobs →</router-link>
        </v-alert>
      </v-card-text>

      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="close">Cancel</v-btn>
        <v-btn
          color="primary"
          :loading="loading"
          :disabled="!canEdit"
          @click="submit"
        >
          Start full reindex
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';

defineOptions({ name: 'FullReindexDialog' });

const props = defineProps({ modelValue: { type: Boolean, default: false } });
const emit = defineEmits(['update:modelValue']);

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
const force = ref(true);
const resume = ref(false);
const submitted = ref(false);

function close() {
  emit('update:modelValue', false);
}

// Reset the "submitted" banner each time the dialog is reopened, so a stale
// success message from a previous run doesn't linger under a fresh form.
watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      submitted.value = false;
      error.value = null;
    }
  }
);

async function submit() {
  const body = { ps: ps.value || 100, force: !!force.value, resume: !!resume.value };
  if (limit.value) body.limit = limit.value;

  try {
    if (catalogType.value === '__all__') {
      await indexAll(body);
    } else {
      await indexOne({ ...body, catalog_type: catalogType.value });
    }
    submitted.value = true;
  } catch (e) {
    // 409 = single-flighted job already running for this type — still a submission, just already in flight.
    if (e?.response?.status === 409) {
      submitted.value = true;
      error.value = null;
    }
  }
}
</script>
