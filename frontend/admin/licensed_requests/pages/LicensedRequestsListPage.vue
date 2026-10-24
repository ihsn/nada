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
      <div v-if="showBatchDelete" class="pa-3 d-flex flex-wrap align-center gap-2 border-b">
        <v-select
          v-model="batchAction"
          :items="batchItems"
          item-title="title"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          style="max-width: 220px"
        />
        <v-btn size="small" variant="tonal" :disabled="!selected.length || batchAction === '-1'" @click="onBatchApply">
          {{ t('apply', 'Apply') }}
        </v-btn>
      </div>

      <v-data-table
        v-model="selected"
        v-model:sort-by="sortBy"
        :headers="headers"
        :items="rows"
        :loading="loading"
        :items-per-page="-1"
        hide-default-footer
        item-value="id"
        :show-select="showBatchDelete"
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
          <v-btn
            v-if="item.can_delete"
            size="small"
            variant="text"
            color="error"
            @click="openDeleteSingle(item)"
          >
            {{ t('delete', 'Delete') }}
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

    <v-dialog v-model="deleteDialog.open" max-width="420">
      <v-card>
        <v-card-title class="text-h6">{{ t('confirm_delete', 'Confirm delete') }}</v-card-title>
        <v-card-text>
          {{ deleteDialog.message }}
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.saving" @click="confirmDelete">
            {{ t('delete', 'Delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
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
const { loading, search, fetchBootstrap, deleteRequests } = useLicensedRequestsApi();

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
const selected = ref([]);
const batchAction = ref('-1');
const canDeleteAny = ref(false);

const batchItems = computed(() => [
  { title: t('batch_actions', 'Batch actions'), value: '-1' },
  { title: t('delete', 'Delete'), value: 'delete' },
]);

const showBatchDelete = computed(
  () => canDeleteAny.value || rows.value.some((r) => r.can_delete)
);

const deleteDialog = ref({
  open: false,
  message: '',
  ids: [],
  saving: false,
});

const toast = ref({ open: false, message: '', color: 'success' });

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
  { title: t('actions', 'Actions'), key: 'actions', sortable: false, width: '160px' },
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

function openDeleteSingle(item) {
  const title = item.request_title ? `“${item.request_title}”` : `#${item.id}`;
  deleteDialog.value = {
    open: true,
    message: t('js_confirm_delete', 'Are you sure you want to delete the selected item(s)?') + ` ${title}`,
    ids: [item.id],
    saving: false,
  };
}

function onBatchApply() {
  if (batchAction.value !== 'delete') return;
  const deletable = selected.value.filter((id) => {
    const row = rows.value.find((r) => String(r.id) === String(id));
    return !!row?.can_delete;
  });
  if (!deletable.length) return;
  deleteDialog.value = {
    open: true,
    message: t('js_confirm_delete', 'Are you sure you want to delete the selected item(s)?'),
    ids: deletable,
    saving: false,
  };
}

async function confirmDelete() {
  deleteDialog.value.saving = true;
  try {
    await deleteRequests(deleteDialog.value.ids);
    deleteDialog.value.open = false;
    selected.value = [];
    batchAction.value = '-1';
    toast.value = { open: true, message: t('deleted', 'Deleted'), color: 'success' };
    await load();
  } catch (e) {
    toast.value = {
      open: true,
      message: e?.response?.data?.message || e?.message || 'Error',
      color: 'error',
    };
  } finally {
    deleteDialog.value.saving = false;
  }
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
    selected.value = [];
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
    canDeleteAny.value = !!boot.can_delete;
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

.border-b {
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
