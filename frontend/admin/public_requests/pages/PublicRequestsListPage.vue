<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="pr-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">
          {{ t('public_requests', 'Public requests') }}
        </h1>
      </v-col>
      <v-col cols="12" md="4" class="text-md-end">
        <v-btn
          variant="text"
          color="primary"
          size="small"
          :href="csvUrl"
          prepend-icon="mdi-download"
        >
          {{ t('export_to_csv', 'Export to CSV') }}
        </v-btn>
      </v-col>
    </v-row>

    <v-alert v-if="accessDenied" type="error" class="mb-4" density="compact">
      {{ t('ACCESS_DENIED', 'Access denied') }}
    </v-alert>

    <v-card class="pa-4 mb-4" elevation="1">
      <v-row dense align="end" class="gy-4">
        <v-col cols="12" md="4">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">
            {{ t('search', 'Search') }}
          </div>
          <v-text-field
            v-model="keywords"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            prepend-inner-icon="mdi-magnify"
            @keyup.enter="applyFilters"
          />
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">
            {{ t('from', 'From') }}
          </div>
          <v-text-field
            v-model="dateFrom"
            type="date"
            density="compact"
            variant="outlined"
            hide-details
            clearable
          />
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">
            {{ t('to', 'To') }}
          </div>
          <v-text-field
            v-model="dateTo"
            type="date"
            density="compact"
            variant="outlined"
            hide-details
            clearable
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-btn color="primary" block class="mb-1" @click="applyFilters">
            {{ t('search', 'Search') }}
          </v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card elevation="1">
      <v-data-table
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="rows"
        :loading="loading"
        :items-per-page="-1"
        hide-default-footer
        item-value="id"
        class="elevation-0"
      >
        <template #item.posted="{ item }">
          {{ formatTs(item.posted) }}
        </template>
        <template #item.title="{ item }">
          <a :href="detailUrl(item.id)" class="text-primary text-decoration-none">
            {{ item.title || item.survey_title || `Request #${item.id}` }}
          </a>
        </template>
        <template #item.abstract="{ item }">
          <span class="text-caption">{{ truncate(item.abstract, 120) }}</span>
        </template>
        <template #item.actions="{ item }">
          <v-btn size="small" variant="text" color="primary" :href="detailUrl(item.id)">
            {{ t('details', 'Details') }}
          </v-btn>
        </template>
      </v-data-table>
      <v-divider />
      <div class="pa-3 d-flex align-center justify-space-between flex-wrap gap-2">
        <div class="text-caption text-medium-emphasis">
          {{ total }} {{ t('total', 'total') }}
        </div>
        <div class="d-flex align-center gap-3 flex-wrap">
          <v-select
            v-model="pageSize"
            hide-details
            density="compact"
            variant="outlined"
            style="max-width: 88px"
            :items="[15, 30, 50, 100]"
            @update:model-value="onPageSizeChange"
          />
          <v-pagination
            v-model="page"
            :length="pageCount"
            :total-visible="7"
            size="small"
            @update:model-value="load"
          />
        </div>
      </div>
    </v-card>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { usePublicRequestsApi } from '../composables/usePublicRequestsApi';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'PublicRequestsListPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { loading, search, exportUrl } = usePublicRequestsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBaseUrl.value}/admin` },
  { title: t('public_requests', 'Public requests'), disabled: true },
]);

const rows = ref([]);
const total = ref(0);
const accessDenied = ref(false);
const keywords = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const page = ref(1);
const pageSize = ref(30);
/** @type {import('vue').Ref<{ key: string, order?: string }[]>} */
const sortBy = ref([{ key: 'posted', order: 'desc' }]);
const toast = ref({ open: false, message: '', color: 'error' });

const headers = computed(() => [
  { title: 'ID', key: 'id', sortable: true, width: '72px' },
  { title: t('title', 'Title'), key: 'title', sortable: true },
  { title: t('username', 'User'), key: 'username', sortable: true },
  { title: t('organization', 'Organization'), key: 'company', sortable: false },
  { title: t('country', 'Country'), key: 'country', sortable: true },
  { title: t('dated', 'Date'), key: 'posted', sortable: true },
  { title: t('intended_use', 'Intended use'), key: 'abstract', sortable: false },
  { title: t('actions', 'Actions'), key: 'actions', sortable: false, width: '110px' },
]);

const pageCount = computed(() => {
  if (total.value <= 0) return 1;
  return Math.max(1, Math.ceil(total.value / pageSize.value));
});

const csvUrl = computed(() =>
  exportUrl({
    keywords: keywords.value || undefined,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
  })
);

watch(total, (n) => {
  const maxPage = Math.max(1, Math.ceil(n / pageSize.value) || 1);
  if (page.value > maxPage) page.value = maxPage;
});

watch(
  sortBy,
  () => {
    page.value = 1;
    load();
  },
  { deep: true }
);

function formatTs(u) {
  if (!u) return '';
  const d = new Date(Number(u) * 1000);
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleString();
}

function truncate(text, max) {
  const s = String(text || '');
  if (s.length <= max) return s;
  return `${s.slice(0, max)}…`;
}

function detailUrl(id) {
  return `${siteBaseUrl.value}/admin/public_requests/${id}`;
}

function applyFilters() {
  page.value = 1;
  load();
}

function onPageSizeChange() {
  page.value = 1;
  load();
}

async function load() {
  accessDenied.value = false;
  try {
    const sort = sortBy.value?.[0] || { key: 'posted', order: 'desc' };
    const result = await search({
      page: page.value,
      ps: pageSize.value,
      keywords: keywords.value || undefined,
      date_from: dateFrom.value || undefined,
      date_to: dateTo.value || undefined,
      sort_by: sort.key,
      sort_order: sort.order === 'asc' ? 'asc' : 'desc',
    });
    rows.value = result.rows || [];
    total.value = result.total || 0;
  } catch (e) {
    const status = e?.response?.status;
    if (status === 403) {
      accessDenied.value = true;
      rows.value = [];
      total.value = 0;
      return;
    }
    toast.value = {
      open: true,
      color: 'error',
      message: e?.response?.data?.message || e.message || t('request_failed', 'Request failed'),
    };
  }
}

onMounted(load);
</script>

<style>
.pr-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.pr-breadcrumbs .v-breadcrumbs-item,
.pr-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}
</style>
