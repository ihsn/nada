<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="facets-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">Facet Indexer</h1>
      </v-col>
      <v-col cols="12" md="4" class="d-flex justify-end">
        <v-btn variant="text" color="primary" @click="router.push('/')">Back to Facets</v-btn>
      </v-col>
    </v-row>

    <!-- Controls card -->
    <v-card elevation="1" class="mb-4">
      <v-card-text class="pa-5">
        <p class="text-body-2 text-medium-emphasis mb-4">
          The facet index is not updated automatically. After making changes to facets or catalog data, run a re-index to update the search filters.
        </p>

        <div class="d-flex ga-3">
          <v-btn
            color="primary"
            variant="flat"
            prepend-icon="mdi-database-refresh"
            :loading="indexing"
            :disabled="clearing"
            @click="startReindex"
          >
            Reindex Facets
          </v-btn>
          <v-btn
            v-if="indexing"
            color="warning"
            variant="flat"
            prepend-icon="mdi-stop"
            @click="stopReindex"
          >
            Stop
          </v-btn>
          <v-btn
            v-else
            color="error"
            variant="outlined"
            prepend-icon="mdi-delete-sweep"
            :loading="clearing"
            :disabled="indexing"
            @click="startClear"
          >
            Clear Index
          </v-btn>
        </div>

        <v-alert v-if="status.message" :type="status.type" density="compact" closable style="margin-top: 24px" @click:close="status.message = ''">
          {{ status.message }}
          <strong v-if="status.count !== null"> — {{ status.count }} entries indexed</strong>
        </v-alert>
      </v-card-text>
    </v-card>

    <!-- Stats card -->
    <h2 class="text-subtitle-1 font-weight-semibold mb-3">Index Stats</h2>

    <v-card v-if="loadingStats" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" class="mb-4" />
        <p class="text-medium-emphasis">Loading stats…</p>
      </v-card-text>
    </v-card>

    <v-card v-else-if="!stats.length" elevation="1">
      <v-card-text class="text-center py-12">
        <v-icon size="64" color="grey" class="mb-4">mdi-database-off-outline</v-icon>
        <h2 class="text-h6 mb-2">Index is empty</h2>
        <p class="text-medium-emphasis">Run Reindex Facets to populate the index.</p>
      </v-card-text>
    </v-card>

    <v-card v-else elevation="1">
      <v-data-table
        :headers="statsHeaders"
        :items="stats"
        item-value="id"
        class="elevation-0"
        hide-default-footer
        :items-per-page="-1"
      />
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useFacetsApi } from '../composables/useFacetsApi';

const router = useRouter();
const { siteUrl } = useAppConfig();
const { getStats, reindex, clearIndex } = useFacetsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin',   href: `${siteBaseUrl.value}/admin` },
  { title: 'Facets',  href: `${siteBaseUrl.value}/admin/facets` },
  { title: 'Indexer', disabled: true },
]);

const statsHeaders = [
  { title: 'ID',    key: 'id',    sortable: false, width: 80 },
  { title: 'Facet', key: 'name',  sortable: false },
  { title: 'Rows',  key: 'total', sortable: false, align: 'end' },
];

const stats        = ref([]);
const loadingStats = ref(true);
const indexing     = ref(false);
const clearing     = ref(false);
const status       = reactive({ message: '', type: 'info', count: null });

let abortController = null;
let shouldStop      = false;

onMounted(async () => {
  try {
    const result = await getStats();
    stats.value  = result.stats;
  } catch (e) {
    status.message = e?.response?.data?.message || e.message;
    status.type    = 'error';
  } finally {
    loadingStats.value = false;
  }
});

async function refreshStats() {
  try {
    const result = await getStats();
    stats.value  = result.stats;
  } catch { /* non-fatal */ }
}

function stopReindex() {
  shouldStop = true;
  abortController?.abort();
}

async function startReindex() {
  shouldStop      = false;
  abortController = new AbortController();
  indexing.value  = true;
  status.message  = 'Indexing…';
  status.type     = 'info';
  status.count    = null;
  let processed   = 0;
  let startRow    = 0;
  try {
    while (!shouldStop) {
      const result = await reindex(startRow, 30, abortController.signal);
      if (!result?.last_row_id) break;
      processed    += result.rows_processed || 0;
      startRow      = result.last_row_id;
      status.count  = processed;
    }
    if (shouldStop) {
      status.message = 'Stopped';
      status.type    = 'warning';
      status.count   = processed;
    } else {
      status.message = 'Completed';
      status.type    = 'success';
      status.count   = processed;
    }
    await refreshStats();
  } catch (e) {
    if (e.name === 'CanceledError' || e.code === 'ERR_CANCELED') {
      status.message = 'Stopped';
      status.type    = 'warning';
      status.count   = processed;
      await refreshStats();
    } else {
      status.message = e?.response?.data?.message || e.message || 'Indexing failed';
      status.type    = 'error';
    }
  } finally {
    indexing.value  = false;
    abortController = null;
  }
}

onBeforeUnmount(() => {
  shouldStop = true;
  abortController?.abort();
});

async function startClear() {
  clearing.value = true;
  status.message = 'Clearing…';
  status.type    = 'info';
  status.count   = null;
  try {
    await clearIndex();
    status.message = 'Index cleared';
    status.type    = 'success';
    await refreshStats();
  } catch (e) {
    status.message = e?.response?.data?.message || e.message || 'Clear failed';
    status.type    = 'error';
  } finally {
    clearing.value = false;
  }
}
</script>

<style scoped>
.facets-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.facets-breadcrumbs :deep(.v-breadcrumbs-item),
.facets-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
