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

          <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
            <div class="admin-catalog-filter-card__header">
              <span class="text-subtitle-2 font-weight-medium">{{ t('dd_embargo_filter', 'Embargo') }}</span>
            </div>
            <div class="admin-catalog-filter-card__body">
              <div class="filter-options-list">
                <button
                  v-for="tab in embargoTabs"
                  :key="tab.value"
                  type="button"
                  class="filter-option-row"
                  :class="{ 'filter-option-row--active': embargoTab === tab.value }"
                  @click="selectEmbargo(tab.value)"
                >
                  <span class="filter-option-row__name text-truncate" :title="tab.title">{{ tab.title }}</span>
                  <span
                    v-if="tab.embargoCount != null"
                    class="filter-option-row__count text-medium-emphasis tabular-nums text-right"
                  >
                    {{ tab.embargoCount }}
                  </span>
                </button>
              </div>
            </div>
          </v-card>

          <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
            <div class="admin-catalog-filter-card__header">
              <span class="text-subtitle-2 font-weight-medium">{{ t('dd_depositor', 'Depositor') }}</span>
            </div>
            <div class="admin-catalog-filter-card__body pa-3">
              <v-text-field
                v-model="createdBy"
                :placeholder="t('dd_depositor', 'Depositor')"
                density="compact"
                hide-details
                clearable
                variant="outlined"
              />
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
          <v-chip v-if="embargoFilterActive" closable @click:close="clearEmbargoFilter">
            {{ t('dd_embargo_filter', 'Embargo') }}: {{ embargoLabel(embargoTab) }}
          </v-chip>
          <v-chip v-if="depositorFilterActive" closable @click:close="clearDepositor">
            {{ t('dd_depositor', 'Depositor') }}: {{ createdBy }}
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
              <v-col cols="12" sm="6" class="d-flex align-center text-body-2">
                <span>{{ resultsSummary }}</span>
              </v-col>
              <v-col cols="12" sm="6" class="d-flex justify-sm-end">
                <v-pagination
                  v-if="totalPages > 1"
                  v-model="page"
                  :length="totalPages"
                  :total-visible="7"
                  density="compact"
                  color="primary"
                />
              </v-col>
            </v-row>
          </div>

          <div class="catalog-results-toolbar catalog-results-toolbar--padded">
            <v-row class="mb-0 align-center">
              <v-col cols="12" sm="6" class="d-flex align-center ga-1">
                <v-checkbox
                  v-if="canDelete"
                  v-model="selectAll"
                  :indeterminate="isIndeterminate"
                  hide-details
                  density="compact"
                  class="ma-0 pa-0"
                />
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
              <v-col cols="12" sm="6" class="d-flex justify-sm-end align-center">
                <v-menu>
                  <template #activator="{ props: menuProps }">
                    <v-btn variant="text" v-bind="menuProps">
                      {{ t('sort_by', 'Sort by') }}
                      <v-icon end>mdi-chevron-down</v-icon>
                    </v-btn>
                  </template>
                  <v-list density="compact" class="menu-list-compact">
                    <v-list-item
                      v-for="opt in sortOptions"
                      :key="opt.value"
                      :active="currentSort === opt.value"
                      @click="onSortChange(opt.value)"
                    >
                      <v-list-item-title class="text-caption">{{ opt.label }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </v-col>
            </v-row>
          </div>

          <v-progress-linear v-if="loading" indeterminate color="primary" />

          <v-table class="admin-catalog-table" hover density="comfortable">
            <tbody>
              <tr v-for="item in rows" :key="item.id">
                <td v-if="canDelete" class="text-center align-top">
                  <v-checkbox
                    v-model="selected"
                    :value="item.id"
                    hide-details
                    density="compact"
                    class="ma-0 pa-0"
                  />
                </td>
                <td class="align-top">
                  <div class="study-row-detail">
                    <div class="study-row-detail__title-line text-title-medium font-weight-bold">
                      <router-link
                        :to="{ name: 'admin-deposit-workspace', params: { id: String(item.id) } }"
                        class="deposit-row-title"
                      >
                        {{ item.title }}
                      </router-link>
                      <v-chip size="x-small" variant="tonal" :color="statusColor(item.status)">
                        {{ statusLabel(item.status) }}
                      </v-chip>
                      <v-chip
                        v-if="isEmbargoed(item)"
                        size="x-small"
                        variant="tonal"
                        color="warning"
                        prepend-icon="mdi-lock-outline"
                      >
                        {{ t('embargoed', 'Embargoed') }}
                      </v-chip>
                    </div>
                    <div v-if="item.shortname" class="study-row-detail__meta text-caption text-medium-emphasis">
                      {{ item.shortname }}
                    </div>
                    <div class="study-meta-bar text-caption">
                      <span class="study-meta-bar__item">
                        <span class="study-meta-bar__key">{{ t('dd_created', 'Created') }}:</span>
                        <span class="study-meta-bar__value">{{ formatDate(item.created_on) || '—' }}</span>
                      </span>
                      <span class="study-meta-bar__sep" aria-hidden="true">·</span>
                      <span class="study-meta-bar__item">
                        <span class="study-meta-bar__key">{{ t('dd_changed', 'Changed') }}:</span>
                        <span class="study-meta-bar__value">{{ formatDate(item.last_modified) || '—' }}</span>
                      </span>
                      <span class="study-meta-bar__sep" aria-hidden="true">·</span>
                      <span class="study-meta-bar__item">
                        <span class="study-meta-bar__key">{{ t('dd_creator', 'Creator') }}:</span>
                        <span class="study-meta-bar__value">{{ item.created_by || '—' }}</span>
                      </span>
                      <template v-if="item.task_id && item.task_user">
                        <span class="study-meta-bar__sep" aria-hidden="true">·</span>
                        <span class="study-meta-bar__item">
                          <span class="study-meta-bar__key">{{ t('dd_assigned_to', 'Assigned to') }}:</span>
                          <router-link
                            :to="{ name: 'admin-deposit-task', params: { id: String(item.task_id) } }"
                            class="study-meta-bar__value text-decoration-none"
                            :title="taskTitle(item)"
                          >
                            {{ item.task_user }}
                          </router-link>
                        </span>
                      </template>
                    </div>
                  </div>
                </td>
                <td class="text-center align-top">
                  <div class="study-actions-inline">
                    <v-menu location="bottom end">
                      <template #activator="{ props: menuProps }">
                        <v-btn icon="mdi-dots-vertical" variant="text" size="small" v-bind="menuProps" />
                      </template>
                      <v-list density="compact" class="menu-list-compact">
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
                          v-if="canEdit"
                          prepend-icon="mdi-folder-zip-outline"
                          :title="t('dd_export_package', 'Download package')"
                          :href="exportUrl(item.id, 'zip')"
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
                </td>
              </tr>
              <tr v-if="!loading && !rows.length">
                <td :colspan="canDelete ? 3 : 2" class="pa-6 text-medium-emphasis">
                  {{ t('no_records_found', 'No projects were found.') }}
                </td>
              </tr>
            </tbody>
          </v-table>

          <div class="admin-catalog-results-footer">
            <v-row class="mt-2 align-center">
              <v-col cols="12" sm="auto" class="d-flex align-center justify-center justify-sm-start ga-2 pb-2 pb-sm-0">
                <span class="text-body-2 text-medium-emphasis text-no-wrap">{{ t('items_per_page', 'Per page') }}</span>
                <v-select
                  v-model="itemsPerPage"
                  :items="PAGE_SIZES"
                  density="compact"
                  variant="outlined"
                  hide-details
                  class="admin-catalog-page-size-select"
                />
              </v-col>
              <v-col cols="12" sm class="d-flex justify-center">
                <v-pagination
                  v-if="totalPages > 1"
                  v-model="page"
                  :length="totalPages"
                  :total-visible="10"
                  density="compact"
                  color="primary"
                />
              </v-col>
            </v-row>
          </div>
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
const { loading, searchProjects, deleteProjects, exportUrl } = useAdminDepositApi();

const STATUS_FILTERS = ['all', 'draft', 'submitted', 'processed', 'accepted', 'closed', 'requested'];
const EMBARGO_FILTERS = ['all', 'yes', 'no'];
const PAGE_SIZES = [15, 25, 50, 100];
const SORT_VALUES = {
  title_asc: { sort_by: 'title', sort_order: 'asc' },
  title_desc: { sort_by: 'title', sort_order: 'desc' },
  created_on_desc: { sort_by: 'created_on', sort_order: 'desc' },
  created_on_asc: { sort_by: 'created_on', sort_order: 'asc' },
  last_modified_desc: { sort_by: 'last_modified', sort_order: 'desc' },
  last_modified_asc: { sort_by: 'last_modified', sort_order: 'asc' },
  created_by_asc: { sort_by: 'created_by', sort_order: 'asc' },
  created_by_desc: { sort_by: 'created_by', sort_order: 'desc' },
  status_asc: { sort_by: 'status', sort_order: 'asc' },
  status_desc: { sort_by: 'status', sort_order: 'desc' },
};

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

const embargoTabs = computed(() => [
  { value: 'all', title: t('all', 'All'), embargoCount: null },
  {
    value: 'yes',
    title: t('embargoed', 'Embargoed'),
    embargoCount: typeof counts.value?.embargoed === 'number' ? counts.value.embargoed : null,
  },
  { value: 'no', title: t('dd_not_embargoed', 'Not embargoed'), embargoCount: null },
]);

const rows = ref([]);
const selected = ref([]);
const deleting = ref(false);
const total = ref(0);
const counts = ref({});
const accessDenied = ref(false);
const loadError = ref('');
const keywords = ref('');
const createdBy = ref('');
const statusTab = ref('all');
const embargoTab = ref('all');
const page = ref(1);
const itemsPerPage = ref(25);
const currentSort = ref('created_on_desc');

let searchTimer = null;
let depositorTimer = null;
let loadSeq = 0;
let applyingRoute = false;

const sortOptions = computed(() => [
  { label: t('sort_title_asc', 'Title A–Z'), value: 'title_asc' },
  { label: t('sort_title_desc', 'Title Z–A'), value: 'title_desc' },
  { label: t('sort_created_desc', 'Created (newest)'), value: 'created_on_desc' },
  { label: t('sort_created_asc', 'Created (oldest)'), value: 'created_on_asc' },
  { label: t('sort_modified_desc', 'Changed (newest)'), value: 'last_modified_desc' },
  { label: t('sort_modified_asc', 'Changed (oldest)'), value: 'last_modified_asc' },
  { label: t('sort_creator_asc', 'Creator A–Z'), value: 'created_by_asc' },
  { label: t('sort_creator_desc', 'Creator Z–A'), value: 'created_by_desc' },
  { label: t('sort_status_asc', 'Status A–Z'), value: 'status_asc' },
  { label: t('sort_status_desc', 'Status Z–A'), value: 'status_desc' },
]);

const totalPages = computed(() => Math.ceil(total.value / itemsPerPage.value) || 1);

const firstItem = computed(() => {
  if (!total.value) return 0;
  return (page.value - 1) * itemsPerPage.value + 1;
});

const lastItem = computed(() => {
  if (!total.value) return 0;
  return Math.min(page.value * itemsPerPage.value, total.value);
});

const resultsSummary = computed(() => {
  if (!total.value) {
    return `${t('dd_showing_projects', 'Showing')} 0 ${t('projects', 'projects')}`;
  }
  const noun = total.value === 1 ? t('project', 'project') : t('projects', 'projects');
  return `${t('dd_showing_projects', 'Showing')} ${firstItem.value}–${lastItem.value} of ${total.value} ${noun}`;
});

const selectAll = computed({
  get: () => selected.value.length === rows.value.length && rows.value.length > 0,
  set: (val) => {
    selected.value = val ? rows.value.map((row) => row.id) : [];
  },
});

const isIndeterminate = computed(
  () => selected.value.length > 0 && selected.value.length < rows.value.length
);

function tabCount(value) {
  const n = counts.value?.[value];
  return typeof n === 'number' ? n : null;
}

function statusLabel(status) {
  const key = String(status || '').toLowerCase();
  const tab = statusTabs.value.find((item) => item.value === key);
  return tab ? tab.title : status ? String(status) : '';
}

function embargoLabel(value) {
  const tab = embargoTabs.value.find((item) => item.value === value);
  return tab ? tab.title : value;
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

function isEmbargoed(item) {
  return Number(item?.is_embargoed) === 1;
}

function formatDate(unix) {
  if (!unix) return '';
  const d = new Date(Number(unix) * 1000);
  if (Number.isNaN(d.getTime())) return '';
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${mm}-${dd}-${d.getFullYear()}`;
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

function normalizeEmbargo(raw) {
  const value = String(raw || '').toLowerCase();
  return EMBARGO_FILTERS.includes(value) ? value : 'all';
}

function normalizePageSize(raw) {
  const n = Number(raw);
  return PAGE_SIZES.includes(n) ? n : 25;
}

function normalizeSort(raw) {
  const value = String(raw || '').trim();
  return Object.prototype.hasOwnProperty.call(SORT_VALUES, value) ? value : 'created_on_desc';
}

function resolveSort(value) {
  return SORT_VALUES[normalizeSort(value)] || SORT_VALUES.created_on_desc;
}

function applyRouteQuery(query) {
  applyingRoute = true;
  statusTab.value = normalizeFilter(query.filter);
  embargoTab.value = normalizeEmbargo(query.embargo);
  const nextKeywords = query.keywords != null ? String(query.keywords) : '';
  if (keywords.value !== nextKeywords) {
    keywords.value = nextKeywords;
  }
  const nextCreatedBy = query.created_by != null ? String(query.created_by) : '';
  if (createdBy.value !== nextCreatedBy) {
    createdBy.value = nextCreatedBy;
  }
  currentSort.value = normalizeSort(query.sort);
  page.value = Math.max(1, Number(query.page) || 1);
  itemsPerPage.value = normalizePageSize(query.ps);
  applyingRoute = false;
}

function buildListQuery() {
  const query = {};
  if (statusTab.value && statusTab.value !== 'all') {
    query.filter = statusTab.value;
  }
  if (embargoTab.value && embargoTab.value !== 'all') {
    query.embargo = embargoTab.value;
  }
  const depositor = String(createdBy.value || '').trim();
  if (depositor) {
    query.created_by = depositor;
  }
  const kw = String(keywords.value || '').trim();
  if (kw) {
    query.keywords = kw;
  }
  if (page.value > 1) {
    query.page = String(page.value);
  }
  if (itemsPerPage.value !== 25) {
    query.ps = String(itemsPerPage.value);
  }
  if (currentSort.value && currentSort.value !== 'created_on_desc') {
    query.sort = currentSort.value;
  }
  return query;
}

function onSortChange(value) {
  const next = normalizeSort(value);
  if (currentSort.value === next) {
    return;
  }
  currentSort.value = next;
  resetPageAndUpdateUrl();
}

function updateUrl() {
  router.replace({ name: 'admin-deposit-list', query: buildListQuery() });
}

const statusFilterActive = computed(() => statusTab.value && statusTab.value !== 'all');
const embargoFilterActive = computed(() => embargoTab.value && embargoTab.value !== 'all');
const depositorFilterActive = computed(() => String(createdBy.value || '').trim() !== '');
const keywordsFilterActive = computed(() => String(keywords.value || '').trim() !== '');
const hasActiveFilters = computed(
  () =>
    statusFilterActive.value ||
    embargoFilterActive.value ||
    depositorFilterActive.value ||
    keywordsFilterActive.value
);

function resetPageAndUpdateUrl() {
  page.value = 1;
  updateUrl();
}

function selectStatus(value) {
  const next = normalizeFilter(value);
  if (statusTab.value === next) {
    return;
  }
  statusTab.value = next;
  resetPageAndUpdateUrl();
}

function selectEmbargo(value) {
  const next = normalizeEmbargo(value);
  if (embargoTab.value === next) {
    return;
  }
  embargoTab.value = next;
  resetPageAndUpdateUrl();
}

function clearStatusFilter() {
  selectStatus('all');
}

function clearEmbargoFilter() {
  selectEmbargo('all');
}

function clearDepositor() {
  if (depositorTimer) {
    clearTimeout(depositorTimer);
  }
  applyingRoute = true;
  createdBy.value = '';
  applyingRoute = false;
  resetPageAndUpdateUrl();
}

function clearKeywords() {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
  applyingRoute = true;
  keywords.value = '';
  applyingRoute = false;
  resetPageAndUpdateUrl();
}

function resetFilters() {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
  if (depositorTimer) {
    clearTimeout(depositorTimer);
  }
  applyingRoute = true;
  statusTab.value = 'all';
  embargoTab.value = 'all';
  keywords.value = '';
  createdBy.value = '';
  currentSort.value = 'created_on_desc';
  page.value = 1;
  applyingRoute = false;
  updateUrl();
}

async function load() {
  const seq = ++loadSeq;
  accessDenied.value = false;
  loadError.value = '';
  const sort = resolveSort(currentSort.value);

  try {
    const result = await searchProjects({
      filter: statusTab.value || 'all',
      keywords: keywords.value || undefined,
      embargo: embargoTab.value || 'all',
      created_by: String(createdBy.value || '').trim() || undefined,
      sort_by: sort.sort_by,
      sort_order: sort.sort_order,
      page: page.value,
      ps: itemsPerPage.value,
    });
    if (seq !== loadSeq) {
      return;
    }
    rows.value = result.items || [];
    total.value = result.total ?? rows.value.length;
    counts.value = result.counts || {};
    if (result.page != null) {
      page.value = Number(result.page) || 1;
    }
    if (result.page_size != null) {
      itemsPerPage.value = normalizePageSize(result.page_size);
    }
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

watch([page, itemsPerPage], () => {
  if (applyingRoute) {
    return;
  }
  updateUrl();
});

watch(keywords, () => {
  if (applyingRoute) {
    return;
  }
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
  searchTimer = setTimeout(() => {
    page.value = 1;
    updateUrl();
  }, 300);
});

watch(createdBy, () => {
  if (applyingRoute) {
    return;
  }
  if (depositorTimer) {
    clearTimeout(depositorTimer);
  }
  depositorTimer = setTimeout(() => {
    page.value = 1;
    updateUrl();
  }, 300);
});

onUnmounted(() => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
  if (depositorTimer) {
    clearTimeout(depositorTimer);
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

.menu-list-compact :deep(.v-list-item) {
  min-height: 32px;
  padding-top: 2px;
  padding-bottom: 2px;
}

.deposit-row-title {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
  text-decoration: none;
  word-break: break-word;
}

.deposit-row-title:hover {
  text-decoration: underline;
  text-underline-offset: 2px;
}

.admin-catalog-page-size-select {
  width: 88px;
  flex-shrink: 0;
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
