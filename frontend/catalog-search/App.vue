<template>
  <v-app>
    <v-main>
      <!-- Hero header -->
      <div class="catalog-hero">
        <v-container>
          <div class="d-flex align-center justify-space-between flex-wrap mb-3">
            <div>
              <div v-if="activeRepoInfo" class="text-overline text-medium-emphasis mb-1">
                {{ t('collection') }}
              </div>
              <h1 class="text-h5 font-weight-bold">
                {{ activeRepoInfo ? activeRepoInfo.title : t('data_catalog') }}
              </h1>
              <p v-if="activeRepoInfo?.short_text" class="text-body-2 text-medium-emphasis mt-1 mb-0">
                {{ activeRepoInfo.short_text }}
              </p>
            </div>
          </div>
          <CatalogSearchBar
            v-model="query.sk"
            :loading="loading"
            :result-count="results?.found"
            @search="resetPage"
          />
        </v-container>
      </div>

      <!-- Tabs + active filters bar -->
      <div class="catalog-tabs-bar">
        <v-container>
          <CatalogTabs
            v-model="query.tab_type"
            :tabs="tabs"
            @change="resetPage"
          />
        </v-container>
      </div>

      <!-- Main content -->
      <v-container class="catalog-body pb-12">
        <!-- Active filter chips -->
        <div v-if="activeFilters.length" class="d-flex flex-wrap align-center ga-2 mb-4">
          <span class="text-caption text-medium-emphasis">{{ t('filter') }}:</span>
          <v-chip
            v-for="f in activeFilters"
            :key="f.key"
            size="small"
            closable
            color="primary"
            variant="tonal"
            @click:close="onClearFilter(f.key)"
          >
            <span class="text-caption font-weight-medium">{{ f.label }}:</span>
            &nbsp;{{ f.value }}
          </v-chip>
          <v-btn
            v-if="activeFilters.length > 1"
            size="x-small"
            variant="text"
            color="error"
            @click="onClearAll"
          >
            {{ t('reset_search') }}
          </v-btn>
        </div>

        <v-row>
          <!-- Facets sidebar -->
          <v-col v-if="facets" cols="12" md="3" lg="3">
            <CatalogFacets
              :facets="facets"
              :query="query"
              @change="resetPage"
            />
          </v-col>

          <!-- Results -->
          <v-col cols="12" :md="facets ? 9 : 12" :lg="facets ? 9 : 12">
            <v-alert
              v-if="error"
              type="error"
              variant="tonal"
              class="mb-4"
              :text="error"
              closable
            />

            <!-- Loading skeleton -->
            <template v-if="loading">
              <v-skeleton-loader
                v-for="i in 5"
                :key="i"
                type="list-item-three-line"
                class="mb-3 rounded"
              />
            </template>

            <template v-else>
              <CatalogResultsList
                v-if="results?.found"
                :results="results"
                :query="query"
                @sort="setSort"
                @page="onPage"
                @page-size="onPageSize"
              />
              <CatalogResultsEmpty v-else-if="hasSearched" :keyword="query.sk" @reset="onResetAll" />
            </template>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePublicCatalogConfig } from './composables/usePublicCatalogConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useCatalogSearch } from './composables/useCatalogSearch';
import CatalogSearchBar    from './components/CatalogSearchBar.vue';
import CatalogTabs         from './components/CatalogTabs.vue';
import CatalogFacets       from './components/CatalogFacets.vue';
import CatalogResultsList  from './components/CatalogResultsList.vue';
import CatalogResultsEmpty from './components/CatalogResultsEmpty.vue';

defineOptions({ name: 'PublicCatalogSearchApp' });

const { activeRepoInfo } = usePublicCatalogConfig();
const { t } = useI18n();

const {
  query, results, facets, tabs, loading, error,
  activeFilters, hasActiveFilters,
  search, resetPage, goToPage, setSort, clearFilter, clearAllFilters, loadFromUrl,
} = useCatalogSearch();

const hasSearched = ref(false);

onMounted(async () => {
  loadFromUrl();           // restore state from URL before first search
  await search(true);      // replaceState so the initial load doesn't add a history entry
  hasSearched.value = true;
});

function onClearFilter(key) {
  clearFilter(key);
  resetPage();
}

function onClearAll() {
  clearAllFilters();
  resetPage();
}

function onResetAll() {
  query.sk = '';
  clearAllFilters();
  resetPage();
}

function onPage(page) {
  goToPage(page);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function onPageSize(ps) {
  query.ps   = ps;
  query.page = 1;
  search();
}
</script>

<style scoped>
.catalog-hero {
  background: linear-gradient(160deg, #e8f0fe 0%, #f5f7ff 50%, #ffffff 100%);
  border-bottom: 1px solid rgba(25, 118, 210, 0.12);
  padding: 28px 0 24px;
}

.catalog-tabs-bar {
  background: #fff;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 10;
}

.catalog-body {
  padding-top: 24px;
}

.ga-2 {
  gap: 8px;
}
</style>
