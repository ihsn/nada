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
            {{ t('catalog_maintenance', 'Manage Studies') }}
          </h1>
          <AdminCatalogToolbar class="catalog-page-header__actions" :owner-repo="filters.owner_repo" />
        </div>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12" md="3" class="admin-catalog-filters-column">
        <AdminCatalogFilters
          :filters="filters"
          :owner-repo="filters.owner_repo"
          @filter-change="onFilterChange"
          @filter-options="onFilterOptions"
        />
      </v-col>
      <v-col cols="12" md="9" class="admin-catalog-main-column">
        <AdminCatalogSearchBar :loading="loading" :initial-keywords="urlKeywords" @search="onSearch" />
        <div v-if="hasActiveFilters" class="admin-catalog-filter-chips filter-chips">
          <v-chip
            v-if="publishedFilterActive"
            closable
            @click:close="removeFilter('published')"
          >
            {{ getFilterLabel('published', filters.published) }}
          </v-chip>
          <v-chip
            v-if="idnoFilterActive"
            closable
            @click:close="removeFilter('idno')"
          >
            {{ getFilterLabel('idno', filters.idno) }}
          </v-chip>
          <template v-for="(arr, key) in filters" :key="key">
            <v-chip
              v-for="val in (Array.isArray(arr) ? arr : [])"
              v-show="Array.isArray(arr) && arr.length > 0"
              :key="`${key}-${val}`"
              closable
              @click:close="removeFilter(key, val)"
            >
              {{ getFilterLabel(key, val) }}
            </v-chip>
          </template>
        </div>
        <AdminCatalogResults
          :studies="studies"
          :loading="loading"
          :pagination="pagination"
          :total-studies="totalStudies"
          :current-sort="currentSort"
          @pagination-change="onPaginationChange"
          @sort-change="onSortChange"
          @publish-change="onPublishChange"
          @refresh="fetchStudies"
        />
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminCatalogSearchBar from '../components/AdminCatalogSearchBar.vue';
import AdminCatalogToolbar from '../components/AdminCatalogToolbar.vue';
import AdminCatalogFilters from '../components/AdminCatalogFilters.vue';
import AdminCatalogResults from '../components/AdminCatalogResults.vue';
import { useCatalogApi } from '../composables/useCatalogApi';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'CatalogListPage' });

const ADMIN_CATALOG_PAGE_SIZES = [15, 50, 100];

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const { siteUrl } = useAppConfig();
const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: t('catalog_maintenance', 'Manage Studies'), disabled: true },
]);
const { loading, search: apiSearch, updateOptions } = useCatalogApi();

const studies = ref([]);
const filters = reactive({
  owner_repo: '',
  idno: '',
  published: '',
  countries: [],
  collections: [],
  tags: [],
  dataAccess: [],
  dataTypes: [],
});
const searchParams = ref({});
const urlKeywords = ref('');
const currentSort = ref('');
const pagination = reactive({
  page: 1,
  itemsPerPage: 15,
});
const totalStudies = ref(0);
const filterOptions = ref({
  countries: [],
  collections: [],
  tags: [],
  dataAccess: [],
  dataTypes: [],
});

function isPublishedFilterActive(published) {
  if (published === '' || published === null || published === undefined) {
    return false;
  }
  return String(published) === '0' || String(published) === '1';
}

const publishedFilterActive = computed(() => isPublishedFilterActive(filters.published));

const idnoFilterActive = computed(() => String(filters.idno ?? '').trim() !== '');

const hasActiveFilters = computed(
  () =>
    publishedFilterActive.value ||
    idnoFilterActive.value ||
    Object.values(filters).some((v) => Array.isArray(v) && v.length > 0)
);

function splitParam(val) {
  if (val == null || val === '') return [];
  return String(val)
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
}

function applyRouteQueryToState(q) {
  const keywordsFromUrl = q.keywords;
  if (keywordsFromUrl != null && keywordsFromUrl !== '') {
    urlKeywords.value = keywordsFromUrl;
    searchParams.value = { ...searchParams.value, keywords: keywordsFromUrl };
  } else {
    urlKeywords.value = '';
    const sp = { ...searchParams.value };
    delete sp.keywords;
    searchParams.value = sp;
  }

  filters.idno = q.idno != null && q.idno !== '' ? String(q.idno) : '';
  filters.owner_repo = q.owner_repo != null && q.owner_repo !== '' ? String(q.owner_repo) : '';

  const published = q.published;
  if (published === '0' || published === '1') {
    filters.published = published;
  } else {
    filters.published = '';
  }

  if (q.page) {
    const p = parseInt(String(q.page), 10);
    if (p >= 1) pagination.page = p;
  }
  if (q.ps) {
    const n = parseInt(String(q.ps), 10);
    if (ADMIN_CATALOG_PAGE_SIZES.includes(n)) pagination.itemsPerPage = n;
  }
  currentSort.value = q.sort != null && q.sort !== '' ? String(q.sort) : '';

  filters.dataAccess = splitParam(q.data_access);
  filters.dataTypes = splitParam(q.dataset_types);
  filters.countries = splitParam(q.countries);
  filters.collections = splitParam(q.collections);
  filters.tags = splitParam(q.tags);
}

function buildListQuery() {
  const q = {};
  if (searchParams.value?.keywords?.trim()) q.keywords = searchParams.value.keywords.trim();
  if (filters.idno) q.idno = filters.idno;
  if (filters.owner_repo) q.owner_repo = filters.owner_repo;
  if (isPublishedFilterActive(filters.published)) q.published = String(filters.published);
  ['countries', 'collections', 'tags'].forEach((k) => {
    if (Array.isArray(filters[k]) && filters[k].length) {
      q[k] = filters[k].join(',');
    }
  });
  if (Array.isArray(filters.dataAccess) && filters.dataAccess.length) {
    q.data_access = filters.dataAccess.join(',');
  }
  if (Array.isArray(filters.dataTypes) && filters.dataTypes.length) {
    q.dataset_types = filters.dataTypes.join(',');
  }
  if (pagination.page > 1) q.page = String(pagination.page);
  if (pagination.itemsPerPage !== 15) q.ps = String(pagination.itemsPerPage);
  if (currentSort.value) q.sort = currentSort.value;
  return q;
}

function updateUrl() {
  router.replace({ name: 'catalog-list', query: buildListQuery() });
}

function onSearch(params) {
  searchParams.value = params;
  pagination.page = 1;
  updateUrl();
}

function onFilterChange(newFilters) {
  Object.assign(filters, newFilters);
  pagination.page = 1;
  updateUrl();
}

function onFilterOptions(opts) {
  if (opts && typeof opts === 'object') {
    filterOptions.value = {
      countries: opts.countries ?? [],
      collections: opts.collections ?? [],
      tags: opts.tags ?? [],
      dataAccess: opts.dataAccess ?? [],
      dataTypes: opts.dataTypes ?? [],
    };
  }
}

function onPaginationChange(next) {
  pagination.page = next.page;
  pagination.itemsPerPage = next.itemsPerPage ?? 15;
  updateUrl();
}

function onSortChange(payload) {
  currentSort.value = payload?.sort ?? '';
  pagination.page = 1;
  updateUrl();
}

async function onPublishChange(payload) {
  const { studyId, published } = payload || {};
  if (studyId == null) return;
  try {
    await updateOptions(studyId, { published: published ?? 0 });
    await fetchStudies();
  } catch (e) {
    console.error('Publish update failed:', e);
  }
}

function removeFilter(key, val) {
  if (Array.isArray(filters[key])) {
    filters[key] = filters[key].filter((v) => v !== val);
  } else if (key === 'published') {
    filters.published = '';
  } else {
    filters[key] = key === 'idno' ? '' : filters[key];
  }
  onFilterChange(filters);
}

function getFilterLabel(key, val) {
  if (key === 'published') {
    return String(val) === '1' ? t('published') : t('unpublished');
  }
  if (key === 'idno') {
    const v = String(val ?? '').trim();
    return v ? `${t('idno')}: ${v}` : t('idno');
  }
  const options = filterOptions.value[key];
  if (Array.isArray(options)) {
    const option = options.find((o) => o.id === val || o.name === val);
    if (option && option.name) return option.name;
  }
  return val;
}

async function fetchStudies() {
  const keyword = searchParams.value?.keywords ?? urlKeywords.value ?? undefined;
  const params = {
    ...searchParams.value,
    keywords: keyword && String(keyword).trim() ? String(keyword).trim() : undefined,
    idno: Array.isArray(filters.idno) ? filters.idno[0] : filters.idno,
    owner_repo: filters.owner_repo || undefined,
    published: isPublishedFilterActive(filters.published) ? String(filters.published) : undefined,
    countries: Array.isArray(filters.countries) && filters.countries.length ? filters.countries.join(',') : undefined,
    collections: Array.isArray(filters.collections) && filters.collections.length ? filters.collections.join(',') : undefined,
    tags: Array.isArray(filters.tags) && filters.tags.length ? filters.tags.join(',') : undefined,
    data_access: Array.isArray(filters.dataAccess) && filters.dataAccess.length ? filters.dataAccess.join(',') : undefined,
    dataset_types: Array.isArray(filters.dataTypes) && filters.dataTypes.length ? filters.dataTypes.join(',') : undefined,
    sort: currentSort.value || undefined,
    page: pagination.page,
    ps: pagination.itemsPerPage,
  };

  const result = await apiSearch(params);
  studies.value = result?.rows ?? [];
  totalStudies.value = result?.total ?? 0;
  if (result?.page != null) pagination.page = result.page;
  if (result?.page_size != null) {
    const ps = Number(result.page_size);
    if (ADMIN_CATALOG_PAGE_SIZES.includes(ps)) pagination.itemsPerPage = ps;
  }
}

watch(
  () => ({ name: route.name, query: { ...route.query } }),
  ({ name, query }) => {
    if (name !== 'catalog-list') return;
    applyRouteQueryToState(query);
    fetchStudies();
  },
  { deep: true, immediate: true }
);
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

.filter-chips :deep(.v-chip__close) {
  margin-left: 6px;
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
