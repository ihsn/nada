<template>
  <v-card elevation="1" rounded="lg">
    <v-card-title class="semantic-card-title">Maintenance</v-card-title>
    <v-divider />
    <v-card-text class="pt-2">
      <div class="semantic-maintenance-row">
        <div>
          <div class="font-weight-medium">Clean up</div>
          <div class="text-body-2 text-medium-emphasis">
            Remove studies and citations no longer in the catalog<template v-if="toRemove != null"> ({{ formatCount(toRemove) }} now)</template>.
          </div>
        </div>
        <v-btn
          variant="tonal"
          color="primary"
          prepend-icon="mdi-broom"
          :loading="cleaning"
          :disabled="!canEdit || toRemove === 0"
          @click="cleanUp"
        >
          Clean up
        </v-btn>
      </div>
      <v-alert v-if="cleanResult" :type="cleanResult.ok ? 'success' : 'error'" variant="tonal" density="compact" class="mb-3">
        {{ cleanResult.message }}
        <router-link v-if="cleanResult.ok" :to="{ name: 'activity', query: { tab: 'queue' } }">Follow it in Activity →</router-link>
      </v-alert>

      <v-divider />

      <div class="semantic-maintenance-row">
        <div>
          <div class="font-weight-medium">Restore tracking from the index</div>
          <div class="text-body-2 text-medium-emphasis">
            After switching engines: mark what NADA-AI already holds as indexed, instead of indexing it all again.
          </div>
        </div>
        <v-btn variant="tonal" color="primary" prepend-icon="mdi-database-import-outline" :loading="importing" :disabled="!canEdit" @click="importExisting">
          Restore tracking
        </v-btn>
      </div>
      <v-alert v-if="importResult" :type="importResult.ok ? 'success' : 'error'" variant="tonal" density="compact" class="mb-3">
        {{ importResult.message }}
        <router-link v-if="importResult.ok" :to="{ name: 'activity', query: { tab: 'jobs' } }">Follow it in Activity →</router-link>
      </v-alert>

      <v-divider />

      <div class="semantic-maintenance-row">
        <div>
          <div class="font-weight-medium text-error">Rebuild the study index</div>
          <div class="text-body-2 text-medium-emphasis">Empty it and index every study again. Embeddings are reused.</div>
        </div>
        <v-btn variant="tonal" color="error" prepend-icon="mdi-database-refresh-outline" :loading="rebuildingStudies" :disabled="!canDelete" @click="rebuildStudies">
          Rebuild…
        </v-btn>
      </div>
      <v-alert v-if="studiesResult" :type="studiesResult.ok ? 'success' : 'error'" variant="tonal" density="compact" class="mb-3">
        {{ studiesResult.message }}
        <router-link v-if="studiesResult.ok" :to="{ name: 'activity', query: { tab: 'queue' } }">Follow them in Activity →</router-link>
      </v-alert>

      <template v-for="index in INDEX_REBUILDS" :key="index.what">
        <v-divider />
        <div class="semantic-maintenance-row">
          <div>
            <div class="font-weight-medium text-error">Rebuild the {{ index.label }} index</div>
            <div class="text-body-2 text-medium-emphasis">Empty it and index every {{ index.label }} again. OpenSearch only.</div>
          </div>
          <v-btn
            variant="tonal"
            color="error"
            prepend-icon="mdi-database-refresh-outline"
            :loading="rebuilding === index.what"
            :disabled="!canDelete || rebuilding !== null"
            @click="rebuildIndex(index)"
          >
            Rebuild…
          </v-btn>
        </div>
        <v-alert
          v-if="rebuildResults[index.what]"
          :type="rebuildResults[index.what].ok ? 'success' : rebuildResults[index.what].busy ? 'warning' : 'error'"
          variant="tonal"
          density="compact"
          class="mb-3"
        >
          {{ rebuildResults[index.what].message }}
          <router-link v-if="rebuildResults[index.what].ok || rebuildResults[index.what].busy" :to="{ name: 'activity', query: { tab: 'jobs' } }">
            {{ rebuildResults[index.what].busy ? 'See it in Activity →' : 'Follow it in Activity →' }}
          </router-link>
        </v-alert>
      </template>

      <v-divider />

      <div class="semantic-maintenance-row">
        <div>
          <div class="font-weight-medium text-error">Delete and rebuild</div>
          <div class="text-body-2 text-medium-emphasis">Empty every index and index everything again, embeddings included. Slow.</div>
        </div>
        <v-btn variant="tonal" color="error" prepend-icon="mdi-delete-alert" :disabled="!canDelete" @click="recreateDialog = true">
          Delete and rebuild…
        </v-btn>
      </div>
      <v-alert v-if="recreateError" type="error" variant="tonal" density="compact" class="mb-3" closable @click:close="recreateError = null">
        {{ errorMessage(recreateError) }}
      </v-alert>
      <v-alert v-if="recreateResult" type="success" variant="tonal" density="compact" class="mb-3">
        Index emptied; {{ formatCount(recreateResult.total) }} studies and citations queued.
        <router-link :to="{ name: 'activity', query: { tab: 'queue' } }">Follow them in Activity →</router-link>
      </v-alert>

      <v-divider />

      <div class="semantic-maintenance-row">
        <div>
          <div class="font-weight-medium text-error">Delete index</div>
          <div class="text-body-2 text-medium-emphasis">Empty every index. Nothing is indexed again.</div>
        </div>
        <v-btn variant="tonal" color="error" prepend-icon="mdi-trash-can-outline" :disabled="!canDelete" @click="dropDialog = true">
          Delete index…
        </v-btn>
      </div>
      <v-alert v-if="dropError" type="error" variant="tonal" density="compact" class="mb-3" closable @click:close="dropError = null">
        {{ dropErrorMessage(dropError) }}
      </v-alert>
      <v-alert v-if="dropResult" type="success" variant="tonal" density="compact" class="mb-3">
        Index deleted. Nothing is searchable until you rebuild.
      </v-alert>

      <p v-if="!canDelete" class="text-caption text-medium-emphasis mb-0">
        You don't have permission to delete the index.
      </p>
    </v-card-text>

    <v-dialog v-model="recreateDialog" max-width="480">
      <v-card rounded="lg">
        <v-card-title class="semantic-card-title text-error">Delete and rebuild the index</v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <p class="text-body-2 mb-4">Type <code>RECREATE</code> to confirm.</p>
          <v-text-field v-model="recreateConfirm" variant="outlined" density="comfortable" hide-details autofocus />
        </v-card-text>
        <v-card-actions class="px-4 pb-4 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="recreateDialog = false">Cancel</v-btn>
          <v-btn color="error" :disabled="recreateConfirm !== 'RECREATE'" :loading="recreating" @click="recreate">
            Delete and rebuild
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dropDialog" max-width="480">
      <v-card rounded="lg">
        <v-card-title class="semantic-card-title text-error">Delete the index</v-card-title>
        <v-divider />
        <v-card-text class="pt-4">
          <p class="text-body-2 mb-4">Type <code>DELETE</code> to confirm. Nothing is indexed again afterwards.</p>
          <v-text-field v-model="dropConfirm" variant="outlined" density="comfortable" hide-details autofocus />
        </v-card-text>
        <v-card-actions class="px-4 pb-4 pt-0">
          <v-spacer />
          <v-btn variant="text" @click="dropDialog = false">Cancel</v-btn>
          <v-btn color="error" :disabled="dropConfirm !== 'DELETE'" :loading="dropping" @click="drop">Delete index</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup>
import { ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { formatCount } from '../typeLabels.js';
import { conflictMessage } from '../jobConflict.js';

defineOptions({ name: 'SemanticMaintenanceCard' });

defineProps({
  /** Studies still indexed but no longer in the catalog (the "To remove" total); null while unknown. */
  toRemove: { type: Number, default: null },
});
const emit = defineEmits(['changed']);

const { canEdit, canDelete } = useAppConfig();
const { loading: cleaning, syncIndex, reconcileNow } = useSemanticApi();
const { loading: recreating, error: recreateError, errorMessage, deleteIndex: deleteForRebuild, rebuildTracking } =
  useSemanticApi();
const { loading: dropping, error: dropError, errorMessage: dropErrorMessage, deleteIndex, rebuildTracking: forgetTracking } =
  useSemanticApi();

const { loading: importing, importIndex } = useSemanticApi();
const { loading: rebuildingStudies, recreateStudiesIndex, rebuildTracking: queueEverything } = useSemanticApi();
const { recreateVariablesIndex, recreateCitationsIndex } = useSemanticApi();
const studiesResult = ref(null);

/** Indexes rebuilt on their own, each as one NADA-AI job (drop, create with the current mapping, index all again). */
const INDEX_REBUILDS = [
  { what: 'variables', label: 'variable', recreate: recreateVariablesIndex },
  { what: 'citations', label: 'citation', recreate: recreateCitationsIndex },
];
/** The index being asked to rebuild, or null. */
const rebuilding = ref(null);
const rebuildResults = ref({});
const cleanResult = ref(null);
const importResult = ref(null);
const recreateDialog = ref(false);
const recreateConfirm = ref('');
const recreateResult = ref(null);
const dropDialog = ref(false);
const dropConfirm = ref('');
const dropResult = ref(null);

/** Ask NADA-AI to start on the queue now; its scheduler gets to it at its next check otherwise. */
async function startProcessing() {
  try {
    await reconcileNow();
  } catch {
    /* picked up at the next check */
  }
}

function failure(e) {
  return e?.response?.data?.detail || e?.response?.data?.message || e?.message;
}

async function cleanUp() {
  if (!window.confirm('Remove every study and citation that is no longer in the catalog from the search index?')) return;
  try {
    const data = await syncIndex({ objectType: 'all', removeOnly: true });
    const n = Object.values(data?.queued || {}).reduce((sum, c) => sum + (Number(c?.delete) || 0), 0);
    await startProcessing();
    cleanResult.value = { ok: true, message: n ? `${formatCount(n)} queued for removal.` : 'Nothing to remove.' };
  } catch (e) {
    cleanResult.value = { ok: false, message: `Could not start the clean up: ${failure(e)}` };
  }
  emit('changed');
}

async function importExisting() {
  if (!window.confirm('Have NADA-AI report what its index holds, and NADA queue what is left to do?')) return;
  try {
    await importIndex();
    importResult.value = { ok: true, message: 'Started: NADA-AI reads its index, then NADA queues the rest.' };
  } catch (e) {
    importResult.value = { ok: false, message: `Could not start: ${failure(e)}` };
  }
  emit('changed');
}

/** Empty NADA-AI's study index, then let NADA forget what it tracked and queue everything (embeddings are reused). */
async function rebuildStudies() {
  if (!window.confirm('Empty the study index and index every study again? Embeddings are kept; keyword search is incomplete until it is done.')) return;
  try {
    await recreateStudiesIndex();
    const data = await queueEverything({ queue: true });
    const total = Object.values(data?.queued || {}).reduce((sum, c) => sum + (Number(c?.upsert_full) || 0), 0);
    await startProcessing();
    studiesResult.value = { ok: true, message: `Study index emptied; ${formatCount(total)} studies and citations queued.` };
  } catch (e) {
    studiesResult.value = { ok: false, message: `Could not rebuild the study index: ${failure(e)}` };
  }
  emit('changed');
}

/** Empty one NADA-AI index and index all of it again, as one NADA-AI job. */
async function rebuildIndex(index) {
  if (!window.confirm(`Empty the ${index.label} index and index every ${index.label} again? Its search returns little or nothing until the job is done.`)) return;
  rebuilding.value = index.what;
  let outcome;
  try {
    await index.recreate();
    outcome = { ok: true, message: `The ${index.label} index was emptied; every ${index.label} is being indexed again.` };
  } catch (e) {
    const busy = conflictMessage(e);
    outcome = {
      ok: false,
      busy: busy !== null,
      message: busy ? `${busy} The ${index.label} index was not emptied.` : `Could not rebuild the ${index.label} index: ${failure(e)}`,
    };
  } finally {
    rebuilding.value = null;
  }
  rebuildResults.value = { ...rebuildResults.value, [index.what]: outcome };
  emit('changed');
}

/** Empty the index in NADA-AI, then let NADA forget what it tracked and queue everything. */
async function recreate() {
  try {
    await deleteForRebuild();
    const data = await rebuildTracking({ queue: true });
    const total = Object.values(data?.queued || {}).reduce((sum, c) => sum + (Number(c?.upsert_full) || 0), 0);
    await startProcessing();
    recreateResult.value = { total };
    recreateDialog.value = false;
    recreateConfirm.value = '';
  } catch {
    recreateResult.value = null;
  }
  emit('changed');
}

async function drop() {
  try {
    dropResult.value = await deleteIndex();
    await forgetTracking({ queue: false });
    dropDialog.value = false;
    dropConfirm.value = '';
  } catch {
    dropResult.value = null;
  }
  emit('changed');
}
</script>

<style scoped>
.semantic-maintenance-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 12px 0;
}
</style>
