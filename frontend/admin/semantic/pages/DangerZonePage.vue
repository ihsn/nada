<template>
  <div class="admin-semantic-stack">
    <v-alert v-if="!canDelete" type="info" variant="tonal" density="compact">
      You don't have permission to recreate the index.
    </v-alert>

    <v-card elevation="1" rounded="lg" class="semantic-danger-card">
      <v-card-title class="semantic-card-title d-flex align-center ga-2">
        <v-icon icon="mdi-alert-octagon-outline" color="error" size="22" />
        Recreate index
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-4">
        <p class="text-body-2 mb-3">
          Drops the entire search index or collection and immediately re-ingests every catalog type
          (document, timeseries, survey, geospatial) from scratch.
        </p>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Search will return incomplete or no results until re-ingest finishes — from minutes to hours
          depending on catalog size. There is no undo.
        </p>

        <div class="semantic-danger-option mb-4">
          <v-checkbox
            v-model="forceRefetch"
            density="compact"
            hide-details
            class="ma-0"
          >
            <template #label>
              <span class="text-body-2">Also force re-fetch and re-embed every idno</span>
            </template>
          </v-checkbox>
          <p class="text-caption text-medium-emphasis mb-0 semantic-danger-option__hint">
            Slower. Leave unchecked to reuse the metadata cache.
          </p>
        </div>

        <v-btn color="error" prepend-icon="mdi-delete-alert" :disabled="!canDelete" @click="dialog = true">
          Recreate index…
        </v-btn>

        <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mt-4" closable @click:close="error = null">
          {{ errorMessage(error) }}
        </v-alert>
        <v-alert v-if="result" type="success" variant="tonal" density="compact" class="mt-4">
          Collection recreated. {{ result.jobs?.length || 0 }} ingest job(s) submitted —
          <router-link to="/jobs" class="text-decoration-none">watch progress on Jobs</router-link>.
        </v-alert>
      </v-card-text>
    </v-card>

    <v-card elevation="1" rounded="lg" class="semantic-danger-card">
      <v-card-title class="semantic-card-title d-flex align-center ga-2">
        <v-icon icon="mdi-delete-alert-outline" color="error" size="22" />
        Drop collection
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-4">
        <p class="text-body-2 mb-3">
          Drops the search index or collection only — nothing is re-ingested afterward.
        </p>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Search returns no results until you run a Full reindex from Index. There is no undo.
        </p>

        <v-btn
          color="error"
          variant="outlined"
          prepend-icon="mdi-trash-can-outline"
          :disabled="!canDelete"
          @click="dropDialog = true"
        >
          Drop collection…
        </v-btn>

        <v-alert v-if="dropError" type="error" variant="tonal" density="compact" class="mt-4" closable @click:close="dropError = null">
          {{ dropErrorMessage(dropError) }}
        </v-alert>
        <v-alert v-if="dropResult" type="success" variant="tonal" density="compact" class="mt-4">
          Collection dropped. Nothing is indexed until you run a Full reindex from Index.
        </v-alert>
      </v-card-text>
    </v-card>

    <v-dialog v-model="dropDialog" max-width="480">
      <v-card rounded="lg">
        <v-card-title class="semantic-card-title text-error">Confirm: drop collection</v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <p class="text-body-2 mb-4">
            Type <code>DELETE</code> below to confirm you want to drop the collection with no reindex.
          </p>
          <v-text-field v-model="dropConfirmText" variant="outlined" density="comfortable" hide-details autofocus />
        </v-card-text>
        <v-card-actions class="px-4 pb-4 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="dropDialog = false">Cancel</v-btn>
          <v-btn
            color="error"
            :disabled="dropConfirmText !== 'DELETE'"
            :loading="dropLoading"
            @click="dropCollection"
          >
            Drop now
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialog" max-width="480">
      <v-card rounded="lg">
        <v-card-title class="semantic-card-title text-error">Confirm: recreate index</v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <p class="text-body-2 mb-4">
            Type <code>RECREATE</code> below to confirm you want to drop and rebuild the entire
            semantic search index.
          </p>
          <v-text-field v-model="confirmText" variant="outlined" density="comfortable" hide-details autofocus />
        </v-card-text>
        <v-card-actions class="px-4 pb-4 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn
            color="error"
            :disabled="confirmText !== 'RECREATE'"
            :loading="loading"
            @click="recreate"
          >
            Recreate now
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';

defineOptions({ name: 'SemanticDangerZonePage' });

const { canDelete } = useAppConfig();
const { loading, error, errorMessage, indexAll } = useSemanticApi();
const { loading: dropLoading, error: dropError, errorMessage: dropErrorMessage, deleteCollection } = useSemanticApi();

const dialog = ref(false);
const confirmText = ref('');
const forceRefetch = ref(false);
const result = ref(null);

const dropDialog = ref(false);
const dropConfirmText = ref('');
const dropResult = ref(null);

async function recreate() {
  try {
    result.value = await indexAll({ recreate_index: true, force: forceRefetch.value });
    dialog.value = false;
    confirmText.value = '';
  } catch {
    result.value = null;
  }
}

async function dropCollection() {
  try {
    dropResult.value = await deleteCollection();
    dropDialog.value = false;
    dropConfirmText.value = '';
  } catch {
    dropResult.value = null;
  }
}
</script>
