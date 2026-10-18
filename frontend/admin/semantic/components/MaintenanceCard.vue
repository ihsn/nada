<template>
  <v-card elevation="1" rounded="lg">
    <v-card-title class="semantic-card-title">Maintenance</v-card-title>
    <v-divider />
    <v-card-text class="pt-2">
      <div class="semantic-maintenance-row">
        <div>
          <div class="font-weight-medium">Clean up</div>
          <div class="text-body-2 text-medium-emphasis">
            Queues the removal of studies and citations that are still in the index but no longer in the catalog
            <template v-if="toRemove != null">({{ formatCount(toRemove) }} studies now)</template>. Adds nothing.
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
          <div class="font-weight-medium">Use the existing index</div>
          <div class="text-body-2 text-medium-emphasis">
            After NADA started tracking a NADA-AI whose index already exists: NADA-AI reports what the index holds, then
            NADA queues the removal of what is gone from the catalog, a refresh of published status and filters for
            what is indexed, and the indexing of what is missing. Nothing already indexed is embedded again.
          </div>
        </div>
        <v-btn variant="tonal" color="primary" prepend-icon="mdi-database-import-outline" :loading="importing" :disabled="!canEdit" @click="importExisting">
          Use the existing index
        </v-btn>
      </div>
      <v-alert v-if="importResult" :type="importResult.ok ? 'success' : 'error'" variant="tonal" density="compact" class="mb-3">
        {{ importResult.message }}
        <router-link v-if="importResult.ok" :to="{ name: 'activity', query: { tab: 'jobs' } }">Follow it in Activity →</router-link>
      </v-alert>

      <v-divider />

      <div class="semantic-maintenance-row">
        <div>
          <div class="font-weight-medium text-error">Delete and rebuild</div>
          <div class="text-body-2 text-medium-emphasis">
            Empties the search index, then queues every study and citation to be indexed again from the catalog. Search
            is incomplete until NADA-AI has worked through the queue — minutes to hours depending on the catalog. With
            OpenSearch the variable index is emptied too and refills as microdata studies are indexed. There is no undo.
          </div>
        </div>
        <v-btn color="error" variant="tonal" prepend-icon="mdi-delete-alert" :disabled="!canDelete" @click="recreateDialog = true">
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
          <div class="text-body-2 text-medium-emphasis">
            Empties the search index and indexes nothing. Semantic search returns no results until you use Rebuild or
            Update. There is no undo.
          </div>
        </div>
        <v-btn color="error" variant="outlined" prepend-icon="mdi-trash-can-outline" :disabled="!canDelete" @click="dropDialog = true">
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
