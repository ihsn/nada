<template>
  <div class="admin-semantic-stack">
    <div class="semantic-page-toolbar">
      <v-btn
        variant="outlined"
        prepend-icon="mdi-refresh"
        :loading="loadingTypeBreakdown || loadingMissing || loadingStale"
        @click="reloadAll"
      >
        Refresh
      </v-btn>
      <v-btn variant="outlined" prepend-icon="mdi-database-sync-outline" @click="fullReindexOpen = true">
        Full reindex…
      </v-btn>
      <v-btn
        color="primary"
        prepend-icon="mdi-play"
        :loading="reconciling || isSubmitting(ALL_TYPES_KEY)"
        :disabled="!canEdit"
        @click="reconcileDiff()"
      >
        Reconcile now
      </v-btn>
    </div>

    <v-card elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
        By type
        <v-spacer />
        <span class="text-caption text-medium-emphasis font-weight-regular">
          Sorted by errors, then missing
        </span>
      </v-card-title>
      <v-divider />
      <v-card-text>
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
          @index-type="reconcileDiff"
        />
      </v-card-text>
    </v-card>

    <v-card ref="missingCard" elevation="1" rounded="lg">
      <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
        Missing
        <v-chip size="small" variant="tonal">{{ missingTotal }}</v-chip>
        <v-spacer />
        <span class="text-caption text-medium-emphasis font-weight-regular">
          In the catalog, not currently indexed
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
            label="Type"
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
          <template #item.idno="{ item }"><code>{{ item.idno }}</code></template>
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
        Stale
        <v-chip size="small" variant="tonal">{{ staleTotal }}</v-chip>
        <v-spacer />
        <span class="text-caption text-medium-emphasis font-weight-regular">
          Indexed, no longer in the catalog
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
            label="Type"
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

    <FullReindexDialog v-model="fullReindexOpen" />

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
        <v-btn v-if="toast.ok" variant="text" @click="goToJobs">Open Jobs</v-btn>
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
import { typeLabel, metadataTypeFromDataType } from '../typeLabels.js';
import FullReindexDialog from '../components/FullReindexDialog.vue';
import CoverageTable from '../components/CoverageTable.vue';

defineOptions({ name: 'SemanticDiffPage' });

const route = useRoute();
const router = useRouter();
const { canEdit } = useAppConfig();
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
const { loading: reconciling, reconcileDiffNow } = useSemanticApi();
const { indexByIdno } = useSemanticApi();

const missingCard = ref(null);
const staleCard = ref(null);
const fullReindexOpen = ref(false);

const missingHeaders = [
  { title: 'idno', key: 'idno' },
  { title: 'type', key: 'type' },
  { title: 'last error', key: 'last_error', sortable: false },
  { title: '', key: 'actions', sortable: false, align: 'end' },
];
const staleHeaders = [
  { title: 'idno', key: 'idno' },
  { title: 'type', key: 'type' },
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

// Shared by both tables' type filter — built from the breakdown so the
// options always match whatever types actually exist right now.
const dataTypeOptions = computed(() =>
  typeBreakdown.value.map((row) => ({ title: typeLabel(row.data_type), value: row.data_type }))
);

const ALL_TYPES_KEY = '__all__';
const submittingKeys = ref(new Set()); // keys with a reconcile/index POST currently in flight
const toast = ref({ open: false, ok: true, message: '' });

function showToast(ok, message) {
  toast.value = { open: true, ok, message };
}

function goToJobs() {
  toast.value = { ...toast.value, open: false };
  router.push({ name: 'jobs' });
}

function idnoSubmitKey(idno) {
  return `idno:${idno}`;
}

function canIndexRow(item) {
  return Boolean(metadataTypeFromDataType(item?.type));
}

function keyLabel(key) {
  return key === ALL_TYPES_KEY ? 'all types' : typeLabel(key);
}

function isSubmitting(key) {
  return submittingKeys.value.has(key);
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

/** From the By type table — filter Missing and scroll to that card. */
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
  loadTypeBreakdown();
  loadMissing({ page: missingPage.value, itemsPerPage: missingItemsPerPage.value });
  loadStale({ page: stalePage.value, itemsPerPage: staleItemsPerPage.value });
}

/**
 * `dataType` narrows to one surveys.type value; omit to reconcile all of
 * object_type. This only submits the job — status/progress/results live on
 * the Jobs tab already, no need to duplicate that tracking here.
 */
async function reconcileDiff(dataType = null) {
  const key = dataType || ALL_TYPES_KEY;
  const label = keyLabel(key);
  if (!window.confirm(`Index missing/stale entries for "${label}" now?`)) {
    return;
  }

  submittingKeys.value = new Set(submittingKeys.value).add(key);
  try {
    await reconcileDiffNow('survey', dataType);
    showToast(true, `Started reconciling "${label}". Look at Jobs for status.`);
  } catch (e) {
    if (e?.response?.status === 409) {
      showToast(true, `"${label}" is already being reconciled. Look at Jobs for status.`);
    } else {
      showToast(false, `Failed to start reconciling "${label}": ${e?.response?.data?.detail || e.message}`);
    }
  } finally {
    const next = new Set(submittingKeys.value);
    next.delete(key);
    submittingKeys.value = next;
  }
}

async function indexMissingRow(item) {
  const idno = item?.idno;
  if (!idno || !canIndexRow(item)) {
    return;
  }

  const key = idnoSubmitKey(idno);
  submittingKeys.value = new Set(submittingKeys.value).add(key);
  try {
    await indexByIdno({ idno, type: item.type, force: Boolean(item.last_error) });
    showToast(true, `Started indexing "${idno}". Look at Jobs for status.`);
  } catch (e) {
    if (e?.response?.status === 409) {
      showToast(true, `"${idno}" is already being indexed. Look at Jobs for status.`);
    } else {
      showToast(
        false,
        `Failed to start indexing "${idno}": ${e?.response?.data?.detail || e?.response?.data?.message || e.message}`
      );
    }
  } finally {
    const next = new Set(submittingKeys.value);
    next.delete(key);
    submittingKeys.value = next;
  }
}

onMounted(() => {
  loadTypeBreakdown();

  // Arrived from Overview KPI / error links — pre-apply that filter, then load.
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
  }
});
</script>
