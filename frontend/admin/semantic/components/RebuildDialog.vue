<template>
  <v-dialog :model-value="modelValue" max-width="600" @update:model-value="$emit('update:modelValue', $event)">
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center">
        Force re-index studies
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" size="small" @click="close" />
      </v-card-title>
      <v-card-subtitle class="text-wrap pb-2">
        Reads every study of the chosen type from the catalog again and indexes it again, including studies that are
        already searchable, as a NADA-AI job outside the change queue. Use it after a change in how metadata is read, or
        when a type looks wrong. Search keeps working while it runs. For routine catch-up, use <strong>Update</strong>,
        which only queues what is missing.
      </v-card-subtitle>

      <v-card-text>
        <v-alert v-if="!canEdit" type="info" variant="tonal" density="compact" class="mb-4">
          You have view-only access — indexing actions are disabled.
        </v-alert>

        <label class="semantic-field-label" for="rebuild-type">What to rebuild</label>
        <v-select
          id="rebuild-type"
          v-model="catalogType"
          :items="typeOptions"
          variant="outlined"
          density="comfortable"
          hide-details
        />
        <p v-if="catalogType === ALL_TYPES" class="text-caption text-medium-emphasis mt-2 mb-0">
          Microdata studies also re-index their variables.
        </p>

        <v-expansion-panels variant="accordion" class="mt-4">
          <v-expansion-panel>
            <v-expansion-panel-title class="text-body-2">Advanced options</v-expansion-panel-title>
            <v-expansion-panel-text>
              <v-row dense>
                <v-col cols="6">
                  <label class="semantic-field-label" for="rebuild-limit">Only the first … studies</label>
                  <v-text-field
                    id="rebuild-limit"
                    v-model.number="limit"
                    type="number"
                    placeholder="all"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
                <v-col cols="6">
                  <label class="semantic-field-label" for="rebuild-ps">Studies fetched per request (1–100)</label>
                  <v-text-field
                    id="rebuild-ps"
                    v-model.number="ps"
                    type="number"
                    min="1"
                    max="100"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                  />
                </v-col>
              </v-row>
              <v-checkbox v-model="resume" density="compact" hide-details class="mt-2">
                <template #label>Continue a rebuild that was stopped, instead of starting over</template>
              </v-checkbox>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mt-4" closable @click:close="error = null">
          {{ errorMessage(error) }}
        </v-alert>
        <v-alert v-if="submitted" type="success" variant="tonal" density="compact" class="mt-4">
          Re-index started.
          <router-link :to="{ name: 'activity', query: { tab: 'jobs' } }" class="text-decoration-none" @click="close">
            Follow it in Activity →
          </router-link>
        </v-alert>
      </v-card-text>

      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="close">Cancel</v-btn>
        <v-btn color="primary" :loading="loading" :disabled="!canEdit" @click="submit">
          Re-index {{ catalogType === ALL_TYPES ? 'all types' : typeLabel(catalogType) }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { typeLabel } from '../typeLabels.js';

defineOptions({ name: 'SemanticRebuildDialog' });

const ALL_TYPES = '__all__';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  /** NADA data types (surveys.type) that exist in the catalog, from the coverage breakdown. */
  types: { type: Array, default: () => [] },
  /** Type to preselect when the dialog opens; null for all types. */
  presetType: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue']);

const { canEdit } = useAppConfig();
const { loading, error, errorMessage, indexOne, indexAll } = useSemanticApi();

const typeOptions = computed(() => [
  { title: 'All types', value: ALL_TYPES },
  ...props.types.map((t) => ({ title: typeLabel(t), value: t })),
]);

const catalogType = ref(ALL_TYPES);
const ps = ref(20);
const limit = ref(null);
const resume = ref(false);
const submitted = ref(false);

function close() {
  emit('update:modelValue', false);
}

// Each opening starts clean, on the type it was opened for.
watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      catalogType.value = props.presetType || ALL_TYPES;
      submitted.value = false;
      error.value = null;
    }
  }
);

async function submit() {
  // A rebuild re-reads and re-indexes every study, already-indexed ones included: that is `force`.
  const body = { ps: ps.value || 20, force: true, resume: !!resume.value };
  if (limit.value) body.limit = limit.value;

  try {
    if (catalogType.value === ALL_TYPES) {
      await indexAll(body);
    } else {
      await indexOne({ ...body, catalog_type: catalogType.value });
    }
    submitted.value = true;
  } catch (e) {
    // 409: a rebuild of this type is already running — it is still in flight, so not an error here.
    if (e?.response?.status === 409) {
      submitted.value = true;
      error.value = null;
    }
  }
}
</script>
