<template>
  <div class="admin-semantic-stack">
    <Teleport to="#semantic-page-actions">
      <v-btn
        variant="outlined"
        prepend-icon="mdi-refresh"
        :loading="loadingTypeBreakdown || loadingMissing || loadingStale"
        @click="reloadAll"
      >
        Refresh
      </v-btn>
    </Teleport>

    <IndexingActivityCard />

    <v-card id="studies" elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
        Studies
        <v-spacer />
        <v-btn variant="text" prepend-icon="mdi-database-sync-outline" :disabled="!canEdit" @click="openRebuild(null)">
          Force re-index…
        </v-btn>
        <v-btn
          color="primary"
          prepend-icon="mdi-play"
          :loading="isSubmitting(ALL_TYPES_KEY)"
          :disabled="!canEdit"
          @click="updateAll"
        >
          Update all
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-3">
          <strong>Update</strong> queues the studies that are missing and the ones no longer in the catalog; NADA-AI
          then works through the queue, catalog edits first. <strong>Force re-index</strong> reads every study of a
          type again, including the ones already searchable, outside the queue.
        </p>
        <CoverageTable
          :rows="sortedTypeBreakdown"
          :total="typeBreakdownTotal"
          :loading="loadingTypeBreakdown"
          :error="typeBreakdownError ? typeBreakdownErrorMessage(typeBreakdownError) : null"
          :row-segments="rowSegments"
          show-actions
          clickable-counts
          :can-edit="canEdit"
          :is-submitting="isSubmitting"
          @select-errors="(type) => filterMissingByType(type, true)"
          @select-missing="(type) => filterMissingByType(type, false)"
          @select-stale="filterStaleByType"
          @update-type="updateType"
          @rebuild-type="openRebuild"
        />
      </v-card-text>
    </v-card>

    <VariablesCoverageCard id="variables" ref="variablesCard" />
    <CitationsCoverageCard id="citations" ref="citationsCard" />

    <v-card ref="missingCard" elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
        Studies to add
        <v-chip size="small" variant="tonal">{{ missingTotal }}</v-chip>
        <v-spacer />
        <span class="text-caption text-medium-emphasis font-weight-regular">
          Published in the catalog, not searchable yet
        </span>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-3">
        <v-alert v-if="missingError" type="error" variant="tonal" density="compact" style="margin-bottom: 12px">
          {{ missingErrorMessage(missingError) }}
        </v-alert>
        <div class="d-flex flex-wrap align-center" style="gap: 16px; margin-bottom: 12px">
          <v-select
            v-model="missingDataType"
            :items="dataTypeOptions"
            placeholder="All types"
            persistent-placeholder
            variant="outlined"
            density="compact"
            hide-details
            clearable
            style="max-width: 220px"
            @update:model-value="onMissingFilterChange"
          />
          <v-checkbox
            v-model="missingHasError"
            label="Errors only"
            density="compact"
            hide-details
            @update:model-value="onMissingFilterChange"
          />
        </div>
        <v-data-table-server
          v-model:items-per-page="missingItemsPerPage"
          v-model:page="missingPage"
          :headers="missingHeaders"
          :items="missingItems"
          :items-length="missingTotal"
          :loading="loadingMissing"
          density="comfortable"
          @update:options="loadMissing"
        >
          <template #item.idno="{ item }">
            <a v-if="item.object_id" :href="studyEditUrl(item.object_id)" target="_blank" rel="noopener" title="Edit the study">
              <code>{{ item.idno || `SID ${item.object_id}` }}</code>
            </a>
            <code v-else>{{ item.idno }}</code>
          </template>
          <template #item.type="{ item }">{{ typeLabel(item.type) }}</template>
          <template #item.last_error="{ item }">
            <span
              v-if="item.last_error"
              class="text-caption text-warning d-inline-block text-truncate"
              style="max-width: 320px"
              :title="item.last_error"
            >{{ item.last_error }}</span>
            <span v-else class="text-medium-emphasis">—</span>
          </template>
          <template #item.actions="{ item }">
            <v-btn
              size="small"
              variant="text"
              :disabled="!canEdit || !canIndexRow(item)"
              :loading="isSubmitting(idnoSubmitKey(item.idno))"
              @click="indexMissingRow(item)"
            >
              Index
            </v-btn>
          </template>
        </v-data-table-server>
      </v-card-text>
    </v-card>

    <v-card ref="staleCard" elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
        Studies to remove
        <v-chip size="small" variant="tonal">{{ staleTotal }}</v-chip>
        <v-spacer />
        <span class="text-caption text-medium-emphasis font-weight-regular">
          Still in the index, no longer in the catalog — removed by Clean up or Update
        </span>
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-3">
        <v-alert v-if="staleError" type="error" variant="tonal" density="compact" style="margin-bottom: 12px">
          {{ staleErrorMessage(staleError) }}
        </v-alert>
        <div class="d-flex flex-wrap align-center" style="gap: 16px; margin-bottom: 12px">
          <v-select
            v-model="staleDataType"
            :items="dataTypeOptions"
            placeholder="All types"
            persistent-placeholder
            variant="outlined"
            density="compact"
            hide-details
            clearable
            style="max-width: 220px"
            @update:model-value="onStaleFilterChange"
          />
        </div>
        <v-data-table-server
          v-model:items-per-page="staleItemsPerPage"
          v-model:page="stalePage"
          :headers="staleHeaders"
          :items="staleItems"
          :items-length="staleTotal"
          :loading="loadingStale"
          density="comfortable"
          @update:options="loadStale"
        >
          <template #item.idno="{ item }"><code>{{ item.idno }}</code></template>
          <template #item.type="{ item }">{{ typeLabel(item.type) }}</template>
        </v-data-table-server>
      </v-card-text>
    </v-card>

    <MaintenanceCard
      id="maintenance"
      :to-remove="typeBreakdown.length ? typeBreakdownTotal.stale : null"
      @changed="reloadAll"
    />

    <RebuildDialog v-model="rebuildOpen" :types="dataTypes" :preset-type="rebuildType" />

    <v-snackbar
      v-model="toast.open"
      :color="toast.ok ? 'success' : 'error'"
      location="top"
      :timeout="-1"
      :z-index="10000"
      multi-line
    >
      {{ toast.message }}
      <template #actions>
        <v-btn v-if="toast.ok" variant="text" @click="goToJobs">Open Activity</v-btn>
        <v-btn variant="text" @click="toast.open = false">Dismiss</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { useCoverageBreakdown } from '../composables/useCoverageBreakdown.js';
import { useSyncHealth } from '../composables/useSyncHealth.js';
import { typeLabel, metadataTypeFromDataType } from '../typeLabels.js';
import RebuildDialog from '../components/RebuildDialog.vue';
import CoverageTable from '../components/CoverageTable.vue';
import VariablesCoverageCard from '../components/VariablesCoverageCard.vue';
import CitationsCoverageCard from '../components/CitationsCoverageCard.vue';
import MaintenanceCard from '../components/MaintenanceCard.vue';
import IndexingActivityCard from '../components/IndexingActivityCard.vue';

defineOptions({ name: 'SemanticIndexingPage' });

const route = useRoute();
const router = useRouter();
const { canEdit, studyEditBaseUrl } = useAppConfig();

/** The study's edit page in the catalog admin. */
function studyEditUrl(sid) {
  return `${(studyEditBaseUrl.value || '').replace(/\/?$/, '/')}${sid}`;
}
const variablesCard = ref(null);
const citationsCard = ref(null);
const {
  loadingTypeBreakdown,
  typeBreakdownError,
  typeBreakdownErrorMessage,
  typeBreakdown,
  typeBreakdownTotal,
  sortedTypeBreakdown,
  rowSegments,
  loadTypeBreakdown,
} = useCoverageBreakdown();
// the coverage and the lists follow the indexing while NADA-AI works through the queue
useSyncHealth().onProgress(() => reloadAll());
const {
  loading: loadingMissing,
  error: missingError,
  errorMessage: missingErrorMessage,
  getDiffMissing,
} = useSemanticApi();
const {
  loading: loadingStale,
  error: staleError,
  errorMessage: staleErrorMessage,
  getDiffStale,
} = useSemanticApi();
const { syncIndex, reconcileNow, requeueObject } = useSemanticApi();

const missingCard = ref(null);
const staleCard = ref(null);
const rebuildOpen = ref(false);
const rebuildType = ref(null);

const missingHeaders = [
  { title: 'Study', key: 'idno' },
  { title: 'Type', key: 'type' },
  { title: 'Last error', key: 'last_error', sortable: false },
  { title: '', key: 'actions', sortable: false, align: 'end' },
];
const staleHeaders = [
  { title: 'Study', key: 'idno' },
  { title: 'Type', key: 'type' },
];

const missingItems = ref([]);
const missingTotal = ref(0);
const missingItemsPerPage = ref(50);
const missingPage = ref(1);
const missingDataType = ref(null);
const missingHasError = ref(false);

const staleItems = ref([]);
const staleTotal = ref(0);
const staleItemsPerPage = ref(50);
const stalePage = ref(1);
const staleDataType = ref(null);

// The types that exist in the catalog right now, for the filters and the Rebuild dialog.
const dataTypes = computed(() => typeBreakdown.value.map((row) => row.data_type));
const dataTypeOptions = computed(() => dataTypes.value.map((t) => ({ title: typeLabel(t), value: t })));

const ALL_TYPES_KEY = '__all__';
const submittingKeys = ref(new Set()); // keys with an update/index POST currently in flight
const toast = ref({ open: false, ok: true, message: '' });

function showToast(ok, message) {
  toast.value = { open: true, ok, message };
}

function goToJobs() {
  toast.value = { ...toast.value, open: false };
  router.push({ name: 'activity', query: { tab: 'queue' } });
}

function idnoSubmitKey(idno) {
  return `idno:${idno}`;
}

function canIndexRow(item) {
  return Boolean(item?.object_id) && Boolean(metadataTypeFromDataType(item?.type));
}

function isSubmitting(key) {
  return submittingKeys.value.has(key);
}

function startSubmitting(key) {
  submittingKeys.value = new Set(submittingKeys.value).add(key);
}

function doneSubmitting(key) {
  const next = new Set(submittingKeys.value);
  next.delete(key);
  submittingKeys.value = next;
}

function failureText(e) {
  return e?.response?.data?.detail || e?.response?.data?.message || e?.message || 'request failed';
}

function openRebuild(dataType) {
  rebuildType.value = dataType || null;
  rebuildOpen.value = true;
}

async function loadMissing({ page, itemsPerPage }) {
  try {
    const data = await getDiffMissing(
      'survey',
      itemsPerPage,
      (page - 1) * itemsPerPage,
      missingDataType.value,
      missingHasError.value
    );
    missingItems.value = data?.items || [];
    missingTotal.value = data?.total || 0;
  } catch {
    // missingError ref already set
  }
}

async function loadStale({ page, itemsPerPage }) {
  try {
    const data = await getDiffStale('survey', itemsPerPage, (page - 1) * itemsPerPage, staleDataType.value);
    staleItems.value = data?.items || [];
    staleTotal.value = data?.total || 0;
  } catch {
    // staleError ref already set
  }
}

// A filter changing invalidates the current page, so jump back to page 1 —
// v-data-table-server's @update:options doesn't fire on its own here since
// these controls sit outside the table.
function onMissingFilterChange() {
  missingPage.value = 1;
  loadMissing({ page: 1, itemsPerPage: missingItemsPerPage.value });
}

/** From the Studies table — filter "Studies to add" and scroll to that card. */
function filterMissingByType(dataType, hasError) {
  missingDataType.value = dataType;
  missingHasError.value = hasError;
  onMissingFilterChange();
  missingCard.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function onStaleFilterChange() {
  stalePage.value = 1;
  loadStale({ page: 1, itemsPerPage: staleItemsPerPage.value });
}

function filterStaleByType(dataType) {
  staleDataType.value = dataType;
  onStaleFilterChange();
  staleCard.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function reloadAll() {
  variablesCard.value?.load();
  citationsCard.value?.load();
  loadTypeBreakdown();
  loadMissing({ page: missingPage.value, itemsPerPage: missingItemsPerPage.value });
  loadStale({ page: stalePage.value, itemsPerPage: staleItemsPerPage.value });
}

/** Ask NADA-AI to start on the queue now instead of at its next check; it picks the work up either way. */
async function startProcessing() {
  try {
    await reconcileNow();
  } catch {
    /* NADA-AI's scheduler gets to the queue at its next check */
  }
}

function queuedText(queued) {
  const totals = { delete: 0, upsert_full: 0, upsert_partial: 0 };
  Object.values(queued || {}).forEach((c) => {
    Object.keys(totals).forEach((k) => {
      totals[k] += Number(c?.[k]) || 0;
    });
  });
  const parts = [];
  if (totals.upsert_full) parts.push(`${totals.upsert_full} to index`);
  if (totals.delete) parts.push(`${totals.delete} to remove`);
  if (totals.upsert_partial) parts.push(`${totals.upsert_partial} to refresh`);
  return parts.length ? `Queued ${parts.join(', ')}.` : 'Nothing to queue: everything is indexed or already queued.';
}

/** Update one type: queue its missing studies and the ones gone from the catalog. */
async function updateType(dataType) {
  const label = typeLabel(dataType);
  if (!window.confirm(`Update ${label}: queue the missing studies and the ones no longer in the catalog?`)) return;
  startSubmitting(dataType);
  try {
    const data = await syncIndex({ objectType: 'survey', dataType });
    await startProcessing();
    showToast(true, `${label}: ${queuedText(data?.queued)}`);
  } catch (e) {
    showToast(false, `Could not update ${label}: ${failureText(e)}`);
  } finally {
    doneSubmitting(dataType);
    loadTypeBreakdown();
  }
}

/** Update all: queue what every study and citation needs (catalog edits are already queued as they happen). */
async function updateAll() {
  if (!window.confirm('Update the search index: queue the missing studies and citations, and the ones no longer in the catalog?')) {
    return;
  }
  startSubmitting(ALL_TYPES_KEY);
  try {
    const data = await syncIndex({ objectType: 'all' });
    await startProcessing();
    showToast(true, queuedText(data?.queued));
  } catch (e) {
    showToast(false, `Could not update: ${failureText(e)}`);
  } finally {
    doneSubmitting(ALL_TYPES_KEY);
    loadTypeBreakdown();
  }
}

/** Queue one missing study for indexing (ahead of any sync work). */
async function indexMissingRow(item) {
  const idno = item?.idno;
  if (!idno || !canIndexRow(item)) {
    return;
  }

  const key = idnoSubmitKey(idno);
  startSubmitting(key);
  try {
    await requeueObject('survey', item.object_id);
    await startProcessing();
    showToast(true, `"${idno}" queued for indexing.`);
  } catch (e) {
    showToast(false, `Could not queue "${idno}": ${failureText(e)}`);
  } finally {
    doneSubmitting(key);
  }
}

function scrollToHash() {
  const id = String(route.hash || '').replace(/^#/, '');
  if (!id) return;
  requestAnimationFrame(() => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

onMounted(() => {
  loadTypeBreakdown();

  // Arrived from an Overview link — pre-apply that filter, then load.
  if (route.query.view === 'stale') {
    if (route.query.type) {
      staleDataType.value = String(route.query.type);
    }
  } else {
    if (route.query.type) {
      missingDataType.value = String(route.query.type);
    }
    if (route.query.hasError) {
      missingHasError.value = true;
    }
  }
  loadMissing({ page: 1, itemsPerPage: missingItemsPerPage.value });
  loadStale({ page: 1, itemsPerPage: staleItemsPerPage.value });
  if (route.query.view === 'stale') {
    requestAnimationFrame(() => {
      staleCard.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  } else if (route.query.type || route.query.hasError) {
    requestAnimationFrame(() => {
      missingCard.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  } else {
    scrollToHash();
  }
});
</script>
