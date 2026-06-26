<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="lr-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="8">
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0">
          {{ t('licensed_requests', 'Licensed requests') }}
        </h1>
      </v-col>
      <v-col cols="12" md="4" class="text-md-end">
        <v-btn
          v-if="siteUrl"
          variant="text"
          color="primary"
          size="small"
          :href="`${siteUrl}/admin/licensed_requests/export`"
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
        <v-col cols="12" md="5">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('search', 'Search') }}</div>
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
        <v-col cols="12" md="5">
          <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('collection', 'Collection') }}</div>
          <v-select
            v-model="ownerRepo"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            :items="collectionItems"
            item-title="title"
            item-value="repositoryid"
          />
        </v-col>
        <v-col cols="12" md="2" class="d-flex gap-2">
          <v-btn color="primary" block class="mb-1" @click="applyFilters">
            {{ t('search', 'Search') }}
          </v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-tabs v-model="statusTab" class="mb-4" color="primary" @update:model-value="onStatusTab">
      <v-tab value="">{{ t('all_requests', 'All') }}</v-tab>
      <v-tab value="PENDING">{{ t('pending', 'Pending') }}</v-tab>
      <v-tab value="APPROVED">{{ t('approved', 'Approved') }}</v-tab>
      <v-tab value="DENIED">{{ t('denied', 'Denied') }}</v-tab>
      <v-tab value="MOREINFO">{{ t('request_more_info', 'More info') }}</v-tab>
      <v-tab value="CANCELLED">{{ t('cancelled', 'Cancelled') }}</v-tab>
    </v-tabs>

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
        <template #item.created="{ item }">
          {{ formatTs(item.created) }}
        </template>
        <template #item.request_title="{ item }">
          <a
            :href="editUrl(item.id)"
            class="text-primary text-decoration-none"
          >
            {{ item.request_title }}
          </a>
        </template>
        <template #item.status="{ item }">
          <v-chip size="small" variant="tonal">{{ item.status }}</v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-btn size="small" variant="text" color="primary" :href="editUrl(item.id)">
            {{ t('edit', 'Edit') }}
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useLicensedRequestsApi } from '../composables/useLicensedRequestsApi';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'LicensedRequestsListPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { loading, search, fetchBootstrap } = useLicensedRequestsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => [
  {
    title: t('home', 'Home'),
    href: `${siteBaseUrl.value}/admin`,
  },
  {
    title: t('licensed_requests', 'Licensed requests'),
    disabled: true,
  },
]);

const rows = ref([]);
const total = ref(0);
const accessDenied = ref(false);
const keywords = ref('');
const ownerRepo = ref('');
const collectionItems = ref([]);
const statusTab = ref('');

const page = ref(1);
const pageSize = ref(30);
/** @type {import('vue').Ref<{ key: string, order?: string }[]>} */
const sortBy = ref([{ key: 'created', order: 'desc' }]);

const headers = computed(() => [
  { title: 'ID', key: 'id', sortable: true, width: '80px' },
  {
    title: t('request_title', 'Title'),
    key: 'request_title',
    sortable: true,
  },
  { title: t('username', 'User'), key: 'username', sortable: true },
  { title: t('status', 'Status'), key: 'status', sortable: true },
  {
    title: t('studies', 'Studies'),
    key: 'survey_count',
    sortable: false,
    width: '100px',
  },
  { title: t('created', 'Created'), key: 'created', sortable: true },
  { title: '', key: 'actions', sortable: false, width: '100px' },
]);

const pageCount = computed(() => {
  if (total.value <= 0) return 1;
  return Math.max(1, Math.ceil(total.value / pageSize.value));
});

watch(total, (t) => {
  const maxPage = Math.max(1, Math.ceil(t / pageSize.value) || 1);
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

function editUrl(id) {
  const base = siteUrl.value?.replace(/\/$/, '') || '';
  return `${base}/admin/licensed_requests/edit/${id}`;
}

function onStatusTab() {
  page.value = 1;
  load();
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
  const sb = sortBy.value[0];
  let sortKey = sb?.key || 'created';
  if (sortKey === 'survey_count') sortKey = 'created';
  const sortOrder = sb?.order === 'asc' ? 'asc' : 'desc';

  const params = {
    page: page.value,
    ps: pageSize.value,
    keywords: keywords.value || undefined,
    status: statusTab.value || undefined,
    owner_repo: ownerRepo.value || undefined,
    sort_by: sortKey,
    sort_order: sortOrder,
  };

  try {
    const result = await search(params);
    rows.value = result.rows || [];
    total.value = result.total ?? 0;
  } catch (e) {
    rows.value = [];
    total.value = 0;
    if (e?.response?.status === 403) {
      accessDenied.value = true;
    }
  }
}

onMounted(async () => {
  try {
    const boot = await fetchBootstrap();
    collectionItems.value = boot.collections || [];
  } catch (e) {
    if (e?.response?.status === 403) {
      accessDenied.value = true;
    }
  }
  await load();
});
</script>

<style scoped>
.lr-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.lr-breadcrumbs :deep(.v-breadcrumbs-item),
.lr-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
