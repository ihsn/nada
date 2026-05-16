<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="bda-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex flex-wrap align-center gap-2 mb-2">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">
        {{ t('attach_studies_to_da_collection', 'Attach studies to collection') }}
      </h1>
      <v-chip v-if="collectionTitle" size="small" variant="tonal">{{ collectionTitle }}</v-chip>
    </div>

    <p class="text-body-2 text-medium-emphasis mb-4">{{ t('msg_copy_studies', 'Select studies for bulk data access.') }}</p>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact">{{ loadError }}</v-alert>

    <div class="bda-search-block">
      <v-card class="pa-4 pb-2" elevation="1">
        <v-row dense align="end">
          <v-col cols="12" md="9">
            <div class="text-body-2 font-weight-medium mb-1">{{ t('search', 'Search') }}</div>
            <v-text-field
              v-model="keywords"
              density="compact"
              variant="outlined"
              hide-details
              clearable
              prepend-inner-icon="mdi-magnify"
              @keyup.enter="applySearch"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-btn color="primary" block class="mb-1" @click="applySearch">{{ t('search', 'Search') }}</v-btn>
          </v-col>
        </v-row>
        <v-checkbox
          v-model="selectedOnly"
          class="mt-1 mb-0"
          hide-details
          density="compact"
          :label="t('show_selected_only', 'Show selected only')"
          @update:model-value="load"
        />
      </v-card>
    </div>

    <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-2">
      <div class="text-body-2">
        {{ t('studies_linked_count', 'Studies attached') }}: <strong>{{ linkedCount }}</strong>
      </div>
      <div class="text-caption text-medium-emphasis">
        {{ showingRange }}
      </div>
    </div>

    <v-card elevation="1">
      <v-data-table
        v-model:sort-by="tableSortBy"
        :headers="tableHeaders"
        :items="rows"
        :loading="loading"
        item-value="id"
        class="elevation-0"
        :items-per-page="-1"
        hide-default-footer
        :multi-sort="false"
        must-sort
      >
        <template v-if="regionalSearch" #item.repositoryid="{ item }">
          {{ String(item.repositoryid || '').toUpperCase() }}
        </template>
        <template v-if="regionalSearch" #item.nation="{ item }">
          {{ item.nation }}
        </template>
        <template #item.title="{ item }">
          {{ studyTitle(item) }}
        </template>
        <template #item.changed="{ item }">
          {{ formatChanged(item.changed) }}
        </template>
        <template #item.actions="{ item }">
          <v-btn
            v-if="!isLinked(item.id)"
            size="small"
            color="success"
            variant="flat"
            :loading="rowBusy === item.id"
            @click="toggleLink(item, true)"
          >
            {{ t('link_study', 'Attach') }}
          </v-btn>
          <v-btn
            v-else
            size="small"
            color="error"
            variant="flat"
            :loading="rowBusy === item.id"
            @click="toggleLink(item, false)"
          >
            {{ t('unlink_study', 'Remove') }}
          </v-btn>
        </template>
      </v-data-table>

      <div class="pa-3 d-flex flex-wrap align-center justify-space-between gap-3">
        <div class="d-flex align-center gap-2">
          <span class="text-caption">{{ t('select_number_of_records_per_page', 'Per page') }}</span>
          <v-select
            v-model="pageSize"
            :items="pageSizeItems"
            density="compact"
            variant="outlined"
            hide-details
            style="max-width: 88px"
            @update:model-value="onPageSize"
          />
        </div>
        <v-pagination v-model="page" :length="pageCount" :total-visible="7" size="small" @update:model-value="load" />
      </div>

      <v-card-text v-if="!loading && !rows.length" class="text-medium-emphasis">
        {{ t('no_records_found', 'No records found') }}
      </v-card-text>
    </v-card>

    <v-snackbar v-model="toast.open" :color="toast.color">{{ toast.message }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useBulkDataAccessApi } from '../composables/useBulkDataAccessApi';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'BulkDataAccessAttachPage' });

const props = defineProps({
  id: { type: String, required: true },
});

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { loading, searchStudies, setStudyLink } = useBulkDataAccessApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => {
  const base = siteBaseUrl.value;
  return [
    { title: t('home', 'Home'), href: `${base}/admin` },
    {
      title: t('bulk_da_collections', 'Bulk data access collections'),
      href: `${base}/admin/da_collections`,
    },
    { title: t('attach_studies', 'Attach studies'), disabled: true },
  ];
});

const rows = ref([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(50);
const keywords = ref('');
const selectedOnly = ref(false);
const linkedIds = ref([]);
const collectionTitle = ref('');
const loadError = ref('');
const regionalSearch = ref(false);
const rowBusy = ref(null);
const toast = ref({ open: false, message: '', color: 'error' });

const pageSizeItems = [15, 30, 50, 100, 500];

/** Server-side sort (API): title only — matches Bulk_data_access::search $db_fields */
/** @type {import('vue').Ref<{ key: string, order?: string }[]>} */
const tableSortBy = ref([{ key: 'title', order: 'asc' }]);

const linkedCount = computed(() => linkedIds.value.length);

const pageCount = computed(() => {
  if (total.value <= 0) return 1;
  return Math.max(1, Math.ceil(total.value / pageSize.value));
});

const showingRange = computed(() => {
  if (!total.value) return '';
  const from = (page.value - 1) * pageSize.value + 1;
  const to = Math.min(page.value * pageSize.value, total.value);
  return `${from}–${to} / ${total.value}`;
});

const tableHeaders = computed(() => {
  const h = [];
  if (regionalSearch.value) {
    h.push({ title: t('repository', 'Collection'), key: 'repositoryid', sortable: false });
    h.push({ title: t('country', 'Country'), key: 'nation', sortable: false });
  }
  h.push({ title: t('title', 'Title'), key: 'title', sortable: true });
  h.push({ title: t('modified', 'Modified'), key: 'changed', sortable: false });
  h.push({ title: t('actions', 'Actions'), key: 'actions', sortable: false, width: '140px' });
  return h;
});

function studyTitle(item) {
  const ys = item.year_start != null ? item.year_start : '';
  return `${item.title || ''}${ys !== '' ? ` — ${ys}` : ''}`;
}

function formatChanged(ts) {
  if (ts == null || ts === '') return '';
  const n = Number(ts);
  if (!Number.isFinite(n)) return String(ts);
  const ms = n > 1e12 ? n : n * 1000;
  try {
    return new Date(ms).toLocaleString();
  } catch {
    return String(ts);
  }
}

function isLinked(sid) {
  return linkedIds.value.includes(Number(sid));
}

/** Maps Vuetify sort-by to API query (backend sorts by surveys.title) */
function titleSortQueryParams() {
  const first = tableSortBy.value?.[0];
  if (first?.key === 'title' && (first.order === 'asc' || first.order === 'desc')) {
    return {
      sort_by: 'title',
      sort_order: first.order === 'desc' ? 'DESC' : 'ASC',
    };
  }
  return { sort_by: 'title', sort_order: 'ASC' };
}

async function load() {
  loadError.value = '';
  try {
    const res = await searchStudies(props.id, {
      page: page.value,
      ps: pageSize.value,
      keywords: (keywords.value ?? '').trim() || undefined,
      selected_only: selectedOnly.value ? 1 : 0,
      ...titleSortQueryParams(),
    });
    rows.value = res.rows || [];
    total.value = res.total || 0;
    linkedIds.value = (res.linked_study_ids || []).map((x) => Number(x));
    collectionTitle.value = res.collection?.title || '';
    regionalSearch.value = !!res.regional_search;
  } catch (e) {
    loadError.value = e?.message || 'Error';
  }
}

function applySearch() {
  page.value = 1;
  load();
}

function onPageSize() {
  page.value = 1;
  load();
}

async function toggleLink(item, linked) {
  rowBusy.value = item.id;
  try {
    await setStudyLink(Number(props.id), Number(item.id), linked);
    if (linked) {
      if (!linkedIds.value.includes(Number(item.id))) linkedIds.value.push(Number(item.id));
    } else {
      linkedIds.value = linkedIds.value.filter((x) => x !== Number(item.id));
    }
    if (selectedOnly.value && !linked) {
      await load();
    }
  } catch (e) {
    toast.value = { open: true, message: e?.message || 'Error', color: 'error' };
  } finally {
    rowBusy.value = null;
  }
}

watch(total, (t) => {
  const maxPage = Math.max(1, Math.ceil(t / pageSize.value) || 1);
  if (page.value > maxPage) page.value = maxPage;
});

watch(
  tableSortBy,
  () => {
    page.value = 1;
    load();
  },
  { deep: true }
);

onMounted(load);
</script>

<style scoped>
/* Explicit gap below search card — utility mb-* on v-card was not leaving visible space in this shell */
.bda-search-block {
  margin-bottom: 1.5rem;
}

.bda-breadcrumbs :deep(.v-breadcrumbs-item),
.bda-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.875rem;
}
</style>
