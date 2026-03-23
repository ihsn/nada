<template>
  <v-app>
    <GlobalDialog />
    <v-main>
      <v-container fluid>
        <v-row>
          <!-- Left: Filters -->
          <v-col cols="12" md="3">
            
              <AdminCatalogFilters
                :filters="filters"
                :owner-repo="filters.owner_repo"
                @filter-change="onFilterChange"
                @filter-options="onFilterOptions"
              />
            
          </v-col>
          <!-- Right: Search + Results -->
          <v-col cols="12" md="9">
            <AdminCatalogSearchBar :loading="loading" :initial-keywords="urlKeywords" @search="onSearch" />
            <div v-if="hasActiveFilters" class="filter-chips mb-3">
              <v-chip
                v-if="filters.published === '1' || filters.published === '0'"
                class="ma-1 mr-2 mb-2"
                closable
                @click:close="removeFilter('published')"
              >
                {{ getFilterLabel('published', filters.published) }}
              </v-chip>
              <template v-for="(arr, key) in filters" :key="key">
                <v-chip
                  v-for="val in (Array.isArray(arr) ? arr : [])"
                  v-show="Array.isArray(arr) && arr.length > 0"
                  :key="`${key}-${val}`"
                  class="ma-1 mr-2 mb-2"
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
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import AdminCatalogSearchBar from './components/AdminCatalogSearchBar.vue';
import AdminCatalogFilters from './components/AdminCatalogFilters.vue';
import AdminCatalogResults from './components/AdminCatalogResults.vue';
import GlobalDialog from './components/GlobalDialog.vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCatalogApi } from './composables/useCatalogApi';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'AdminCatalogApp' });

const { activeRepo } = useAppConfig();
const { t } = useI18n();
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
// Sync owner_repo from app config (injected after mount)
if (typeof activeRepo?.value !== 'undefined' && activeRepo.value) {
  filters.owner_repo = activeRepo.value;
}
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

const hasActiveFilters = computed(() =>
  (filters.published === '1' || filters.published === '0') ||
  Object.values(filters).some(
    (v) => Array.isArray(v) && v.length > 0
  )
);

function onSearch(params) {
  searchParams.value = params;
  pagination.page = 1;
  updateUrl();
  fetchStudies();
}

function onFilterChange(newFilters) {
  Object.assign(filters, {
    ...newFilters,
    owner_repo: (filters.owner_repo || activeRepo?.value) ?? '',
  });
  pagination.page = 1;
  updateUrl();
  fetchStudies();
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
  fetchStudies();
}

function onSortChange(payload) {
  currentSort.value = payload?.sort ?? '';
  pagination.page = 1;
  updateUrl();
  fetchStudies();
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
  if (key === 'published') return val === '1' ? t('published') : t('unpublished');
  const options = filterOptions.value[key];
  if (Array.isArray(options)) {
    const option = options.find((o) => o.id === val || o.name === val);
    if (option && option.name) return option.name;
  }
  return val;
}

function updateUrl() {
  const params = new URLSearchParams();
  if (searchParams.value?.keywords?.trim()) params.set('keywords', searchParams.value.keywords.trim());
  if (filters.idno) params.set('idno', filters.idno);
  if (filters.owner_repo) params.set('owner_repo', filters.owner_repo);
  if (filters.published === '1' || filters.published === '0') params.set('published', filters.published);
  ['countries', 'collections', 'tags'].forEach((k) => {
    if (Array.isArray(filters[k]) && filters[k].length)
      params.set(k, filters[k].join(','));
  });
  if (Array.isArray(filters.dataAccess) && filters.dataAccess.length)
    params.set('data_access', filters.dataAccess.join(','));
  if (Array.isArray(filters.dataTypes) && filters.dataTypes.length)
    params.set('dataset_types', filters.dataTypes.join(','));
  if (pagination.page > 1) params.set('page', String(pagination.page));
  if (pagination.itemsPerPage !== 15) params.set('ps', String(pagination.itemsPerPage));
  if (currentSort.value) params.set('sort', currentSort.value);
  const qs = params.toString();
  const path = window.location.pathname;
  window.history.replaceState({}, '', qs ? `${path}?${qs}` : path);
}

async function fetchStudies() {
  const keyword = searchParams.value?.keywords ?? urlKeywords.value ?? undefined;
  const params = {
    ...searchParams.value,
    keywords: keyword && String(keyword).trim() ? String(keyword).trim() : undefined,
    idno: Array.isArray(filters.idno) ? filters.idno[0] : filters.idno,
    owner_repo: filters.owner_repo || undefined,
    published: filters.published === '1' || filters.published === '0' ? filters.published : undefined,
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
  if (result?.page_size != null) pagination.itemsPerPage = result.page_size;
}

onMounted(() => {
  const url = new URL(window.location.href);
  const keywordsFromUrl = url.searchParams.get('keywords');
  if (keywordsFromUrl != null && keywordsFromUrl !== '') {
    urlKeywords.value = keywordsFromUrl;
    searchParams.value = { ...(searchParams.value || {}), keywords: keywordsFromUrl };
  }
  if (activeRepo?.value) filters.owner_repo = activeRepo.value;
  const idno = url.searchParams.get('idno');
  const ownerRepo = url.searchParams.get('owner_repo');
  const page = url.searchParams.get('page');
  const ps = url.searchParams.get('ps');
  const sort = url.searchParams.get('sort');
  const published = url.searchParams.get('published');
  if (idno) filters.idno = idno;
  if (ownerRepo) filters.owner_repo = ownerRepo;
  if (published === '0' || published === '1') filters.published = published;
  if (page) {
    const p = parseInt(page, 10);
    if (p >= 1) pagination.page = p;
  }
  if (ps) {
    const n = parseInt(ps, 10);
    if (n >= 1) pagination.itemsPerPage = Math.min(n, 100);
  }
  if (sort) currentSort.value = sort;
  const dataAccess = url.searchParams.get('data_access');
  if (dataAccess) filters.dataAccess = dataAccess.split(',').map((s) => s.trim()).filter(Boolean);
  const datasetTypes = url.searchParams.get('dataset_types');
  if (datasetTypes) filters.dataTypes = datasetTypes.split(',').map((s) => s.trim()).filter(Boolean);
  const countries = url.searchParams.get('countries');
  if (countries) filters.countries = countries.split(',').map((s) => s.trim()).filter(Boolean);
  const collections = url.searchParams.get('collections');
  if (collections) filters.collections = collections.split(',').map((s) => s.trim()).filter(Boolean);
  const tags = url.searchParams.get('tags');
  if (tags) filters.tags = tags.split(',').map((s) => s.trim()).filter(Boolean);
  fetchStudies();
});
</script>

<style scoped>
.filter-chips :deep(.v-chip__close) {
  margin-left: 6px;
}
</style>
