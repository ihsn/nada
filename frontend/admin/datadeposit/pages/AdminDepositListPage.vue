<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="catalog-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <v-row align="center" class="mb-5 catalog-page-header">
      <v-col cols="12" class="pa-0">
        <div class="catalog-page-header__inner">
          <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 catalog-page-header__title">
            {{ t('dd_projects_heading', 'Data deposit projects') }}
          </h1>
          <div class="catalog-page-header__actions d-flex ga-2">
            <v-btn
              variant="text"
              color="primary"
              size="small"
              :to="{ name: 'admin-deposit-tasks' }"
              prepend-icon="mdi-clipboard-check-outline"
            >
              {{ t('dd_active_tasks', 'Tasks') }}
            </v-btn>
            <v-btn
              variant="text"
              color="primary"
              size="small"
              :to="{ name: 'admin-deposit-my-tasks' }"
              prepend-icon="mdi-account-check-outline"
            >
              {{ t('dd_my_tasks', 'My tasks') }}
            </v-btn>
          </div>
        </div>
      </v-col>
    </v-row>

    <v-alert v-if="accessDenied" type="error" class="mb-4" density="compact">
      {{ t('dd_access_denied', 'Access denied') }}
    </v-alert>
    <v-alert v-else-if="loadError" type="error" class="mb-4" density="compact">
      {{ loadError }}
    </v-alert>

    <v-row>
      <v-col cols="12" md="3" class="admin-catalog-filters-column">
        <div class="admin-catalog-filter-stack">
          <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
            <div class="admin-catalog-filter-card__header">
              <span class="text-subtitle-2 font-weight-medium">{{ t('dd_status', 'Status') }}</span>
            </div>
            <div class="admin-catalog-filter-card__body">
              <div class="filter-options-list">
                <button
                  v-for="tab in statusTabs"
                  :key="tab.value"
                  type="button"
                  class="filter-option-row"
                  :class="{ 'filter-option-row--active': statusTab === tab.value }"
                  @click="selectStatus(tab.value)"
                >
                  <span class="filter-option-row__name text-truncate" :title="tab.title">{{ tab.title }}</span>
                  <span
                    v-if="tabCount(tab.value) != null"
                    class="filter-option-row__count text-medium-emphasis tabular-nums text-right"
                  >
                    {{ tabCount(tab.value) }}
                  </span>
                </button>
              </div>
            </div>
          </v-card>
          <v-btn
            v-if="hasActiveFilters"
            block
            variant="text"
            color="primary"
            rounded="lg"
            @click="resetFilters"
          >
            {{ t('reset', 'Clear filters') }}
          </v-btn>
        </div>
      </v-col>

      <v-col cols="12" md="9" class="admin-catalog-main-column">
        <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
          <div class="admin-catalog-search-inner">
            <v-text-field
              v-model="keywords"
              :placeholder="t('search', 'Search')"
              prepend-inner-icon="mdi-magnify"
              density="comfortable"
              hide-details
              clearable
              variant="outlined"
              class="admin-catalog-search-field"
            />
          </div>
        </v-card>

        <div v-if="hasActiveFilters" class="admin-catalog-filter-chips filter-chips">
          <v-chip v-if="statusFilterActive" closable @click:close="clearStatusFilter">
            {{ t('dd_status', 'Status') }}: {{ statusLabel(statusTab) }}
          </v-chip>
          <v-chip v-if="keywordsFilterActive" closable @click:close="clearKeywords">
            {{ t('search', 'Search') }}: {{ keywords }}
          </v-chip>
          <v-btn variant="text" size="small" color="primary" class="ms-1" @click="resetFilters">
            {{ t('reset', 'Clear filters') }}
          </v-btn>
        </div>

        <v-card class="admin-catalog-results-card admin-catalog-surface" rounded="lg" elevation="1">
          <div class="catalog-results-toolbar catalog-results-toolbar--padded">
            <v-row class="mb-0 align-center">
              <v-col cols="12" class="d-flex align-center text-body-2">
                <span>
                  {{ t('dd_showing_projects', 'Showing') }} {{ total }}
                  {{ total === 1 ? t('project', 'project') : t('projects', 'projects') }}
                </span>
                <v-spacer />
                <v-btn
                  v-if="canDelete && selected.length"
                  color="error"
                  variant="text"
                  size="small"
                  :loading="deleting"
                  @click="confirmBulkDelete"
                >
                  {{ t('delete', 'Delete') }} ({{ selected.length }})
                </v-btn>
              </v-col>
            </v-row>
          </div>
          <v-data-table
            v-model="selected"
            v-model:sort-by="sortBy"
            :headers="headers"
            :items="rows"
            :loading="loading"
            :show-select="canDelete"
            :items-per-page="-1"
            hide-default-footer
            item-value="id"
            hover
            density="comfortable"
            class="admin-catalog-table elevation-0"
          >
            <template #item.status="{ item }">
              <v-chip size="small" variant="tonal" :color="statusColor(item.status)">
                {{ statusLabel(item.status) }}
              </v-chip>
            </template>
            <template #item.title="{ item }">
              <div class="study-row-detail">
                <div class="study-row-detail__title-line text-title-medium font-weight-bold">
                  <router-link
                    :to="{ name: 'admin-deposit-workspace', params: { id: String(item.id) } }"
                    class="text-decoration-none"
                  >
                    {{ item.title }}
                  </router-link>
                </div>
                <div v-if="item.shortname" class="study-row-detail__meta text-caption text-medium-emphasis">
                  {{ item.shortname }}
                </div>
              </div>
            </template>
            <template #item.last_modified="{ item }">
              {{ formatDate(item.last_modified) }}
            </template>
            <template #item.created_on="{ item }">
              {{ formatDate(item.created_on) }}
            </template>
            <template #item.task="{ item }">
              <router-link
                v-if="item.task_id && item.task_user"
                :to="{ name: 'admin-deposit-task', params: { id: String(item.task_id) } }"
                class="text-decoration-none"
              >
                <v-avatar
                  size="28"
                  :color="Number(item.task_status) === 1 ? 'success' : 'warning'"
                  :title="taskTitle(item)"
                >
                  <span class="text-caption font-weight-bold">{{ taskInitials(item.task_user) }}</span>
                </v-avatar>
              </router-link>
            </template>
            <template #item.actions="{ item }">
              <div class="text-end" @click.stop>
                <v-menu location="bottom end">
                  <template #activator="{ props: menuProps }">
                    <v-btn
                      icon="mdi-dots-vertical"
                      variant="text"
                      size="small"
                      v-bind="menuProps"
                    />
                  </template>
                  <v-list density="compact">
                    <v-list-item
                      v-if="canEdit"
                      prepend-icon="mdi-account-plus-outline"
                      :title="t('dd_assign', 'Assign')"
                      :to="{ name: 'admin-deposit-assign', params: { id: String(item.id) } }"
                    />
                    <v-list-item
                      :prepend-icon="canEdit ? 'mdi-pencil' : 'mdi-eye-outline'"
                      :title="canEdit ? t('edit', 'Edit') : t('view', 'View')"
                      :to="{ name: 'admin-deposit-workspace', params: { id: String(item.id) } }"
                    />
                    <v-list-item
                      v-if="canDelete"
                      prepend-icon="mdi-delete"
                      :title="t('delete', 'Delete')"
                      base-color="error"
                      @click="confirmDeleteOne(item)"
                    />
                  </v-list>
                </v-menu>
              </div>
            </template>
            <template #no-data>
              <div class="pa-6 text-medium-emphasis">
                {{ t('no_records_found', 'No projects were found.') }}
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import $dialog from '@/shared/composables/dialog';

defineOptions({ name: 'AdminDepositListPage' });

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { siteUrl, canEdit, canDelete } = useAppConfig();
const { loading, searchProjects, deleteProjects } = useAdminDepositApi();

const STATUS_FILTERS = ['all', 'draft', 'submitted', 'processed', 'accepted', 'closed', 'requested'];

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBaseUrl.value}/admin` },
  { title: t('title_project_management', 'Data deposit'), disabled: true },
]);

const statusTabs = computed(() => [
  { value: 'all', title: t('all', 'All') },
  { value: 'draft', title: t('draft', 'Draft') },
  { value: 'submitted', title: t('dd_submitted', 'Submitted') },
  { value: 'processed', title: t('dd_processed', 'Processed') },
  { value: 'accepted', title: t('dd_accepted', 'Accepted') },
  { value: 'closed', title: t('dd_closed', 'Closed') },
  { value: 'requested', title: t('dd_reopen_requested', 'Reopen requested') },
]);

const rows = ref([]);
const selected = ref([]);
const deleting = ref(false);
const total = ref(0);
const counts = ref({});
const accessDenied = ref(false);
const loadError = ref('');
const keywords = ref('');
const statusTab = ref('all');
/** @type {import('vue').Ref<{ key: string, order?: string }[]>} */
const sortBy = ref([{ key: 'created_on', order: 'desc' }]);

let searchTimer = null;
let loadSeq = 0;
let applyingRoute = false;

const headers = computed(() => [
  { title: t('dd_status', 'Status'), key: 'status', sortable: true, width: '130px' },
  { title: t('title', 'Title'), key: 'title', sortable: true },
  { title: t('dd_changed', 'Changed'), key: 'last_modified', sortable: true, width: '140px' },
  { title: t('dd_created', 'Created'), key: 'created_on', sortable: true, width: '140px' },
  { title: t('dd_creator', 'Creator'), key: 'created_by', sortable: true },
  { title: t('dd_assigned_to', 'Assigned to'), key: 'task', sortable: false, width: '120px' },
  { title: '', key: 'actions', sortable: false, width: '56px', align: 'end' },
]);

function tabCount(value) {
  const n = counts.value?.[value];
  return typeof n === 'number' ? n : null;
}

function statusLabel(status) {
  const key = String(status || '').toLowerCase();
  const tab = statusTabs.value.find((item) => item.value === key);
  return tab ? tab.title : status ? String(status) : '';
}

function statusColor(status) {
  switch (String(status || '').toLowerCase()) {
    case 'submitted':
      return 'info';
    case 'processed':
      return 'warning';
    case 'accepted':
      return 'success';
    case 'closed':
      return 'secondary';
    default:
      return 'default';
  }
}

function formatDate(unix) {
  if (!unix) return '';
  const d = new Date(Number(unix) * 1000);
  if (Number.isNaN(d.getTime())) return '';
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${mm}-${dd}-${d.getFullYear()}`;
}

function taskInitials(user) {
  return String(user || '')
    .split(/\s+/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');
}

function projectsByIds(ids) {
  const set = new Set((ids || []).map((id) => Number(id)));
  return rows.value.filter((row) => set.has(Number(row.id)));
}

function deleteConfirmMessage(items) {
  const titles = items.map((row) => row.title || `#${row.id}`).filter(Boolean);
  if (items.length === 1) {
    return t('dd_confirm_delete_project', 'Delete “%s”? This cannot be undone.', titles[0] || `#${items[0].id}`);
  }
  const list = titles.slice(0, 8).join('; ');
  const extra = titles.length > 8 ? ` (+${titles.length - 8})` : '';
  return t(
    'dd_confirm_delete_projects',
    'Delete %s projects? %s This cannot be undone.',
    items.length,
    list + extra
  );
}

async function runDelete(items) {
  if (!items.length) {
    return;
  }
  const ok = await $dialog.confirm({
    title: t('confirm_delete', 'Confirm delete'),
    message: deleteConfirmMessage(items),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) {
    return;
  }
  deleting.value = true;
  try {
    await deleteProjects(items.map((row) => row.id));
    selected.value = [];
    await load();
  } catch (e) {
    loadError.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
  } finally {
    deleting.value = false;
  }
}

function confirmDeleteOne(item) {
  return runDelete([item]);
}

function confirmBulkDelete() {
  const items = projectsByIds(selected.value);
  if (!items.length) {
    return $dialog.alert({
      title: t('dd_no_selection', 'No selection'),
      message: t('dd_select_project', 'Select at least one project.'),
    });
  }
  return runDelete(items);
}

function taskTitle(item) {
  const status =
    Number(item.task_status) === 1 ? t('dd_completed', 'Completed') : t('dd_wip', 'Work in progress');
  return `${status} - ${item.task_user}`;
}

function normalizeFilter(raw) {
  const value = String(raw || '').toLowerCase();
  return STATUS_FILTERS.includes(value) ? value : 'all';
}

function applyRouteQuery(query) {
  applyingRoute = true;
  statusTab.value = normalizeFilter(query.filter);
  const nextKeywords = query.keywords != null ? String(query.keywords) : '';
  if (keywords.value !== nextKeywords) {
    keywords.value = nextKeywords;
  }
  applyingRoute = false;
}

function buildListQuery() {
  const query = {};
  if (statusTab.value && statusTab.value !== 'all') {
    query.filter = statusTab.value;
  }
  const kw = String(keywords.value || '').trim();
  if (kw) {
    query.keywords = kw;
  }
  return query;
}

function updateUrl() {
  router.replace({ name: 'admin-deposit-list', query: buildListQuery() });
}

const statusFilterActive = computed(() => statusTab.value && statusTab.value !== 'all');
const keywordsFilterActive = computed(() => String(keywords.value || '').trim() !== '');
const hasActiveFilters = computed(() => statusFilterActive.value || keywordsFilterActive.value);

function selectStatus(value) {
  const next = normalizeFilter(value);
  if (statusTab.value === next) {
    return;
  }
  statusTab.value = next;
  updateUrl();
}

function clearStatusFilter() {
  selectStatus('all');
}

function clearKeywords() {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
  applyingRoute = true;
  keywords.value = '';
  applyingRoute = false;
  updateUrl();
}

function resetFilters() {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
  applyingRoute = true;
  statusTab.value = 'all';
  keywords.value = '';
  applyingRoute = false;
  updateUrl();
}

async function load() {
  const seq = ++loadSeq;
  accessDenied.value = false;
  loadError.value = '';
  const sb = sortBy.value[0];
  const sortKey = sb?.key || 'created_on';
  const sortOrder = sb?.order === 'asc' ? 'asc' : 'desc';

  try {
    const result = await searchProjects({
      filter: statusTab.value || 'all',
      keywords: keywords.value || undefined,
      sort_by: sortKey,
      sort_order: sortOrder,
    });
    if (seq !== loadSeq) {
      return;
    }
    rows.value = result.items || [];
    total.value = result.total ?? rows.value.length;
    counts.value = result.counts || {};
    const visible = new Set(rows.value.map((row) => Number(row.id)));
    selected.value = selected.value.filter((id) => visible.has(Number(id)));
  } catch (e) {
    if (seq !== loadSeq) {
      return;
    }
    rows.value = [];
    total.value = 0;
    if (e?.response?.status === 403) {
      accessDenied.value = true;
    } else {
      loadError.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
    }
  }
}

watch(
  () => route.query,
  (query) => {
    applyRouteQuery(query);
    load();
  },
  { deep: true, immediate: true }
);

watch(
  sortBy,
  () => {
    load();
  },
  { deep: true }
);

watch(keywords, () => {
  if (applyingRoute) {
    return;
  }
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
  searchTimer = setTimeout(() => {
    updateUrl();
  }, 300);
});

onUnmounted(() => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
});
</script>

<style scoped>
.catalog-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.catalog-breadcrumbs :deep(.v-breadcrumbs-item),
.catalog-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}

.catalog-page-header__inner {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.catalog-page-header__title {
  min-width: 0;
}

.filter-chips :deep(.v-chip__close) {
  margin-left: 6px;
}

:deep(.filter-options-list .filter-option-row) {
  grid-template-columns: minmax(0, 1fr) max-content;
  appearance: none;
  border: 0;
  background: transparent;
  text-align: left;
  font: inherit;
  color: inherit;
}

:deep(.filter-option-row--active) {
  background-color: rgba(var(--v-theme-primary), 0.08);
}

.filter-option-row--active .filter-option-row__name {
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
}

@media (max-width: 599px) {
  .catalog-page-header__inner {
    grid-template-columns: 1fr;
    justify-items: start;
  }

  .catalog-page-header__actions {
    justify-self: end;
  }
}
</style>
