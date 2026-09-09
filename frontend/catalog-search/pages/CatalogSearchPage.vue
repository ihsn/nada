<template>
  <v-app>
    <v-main class="catalog-search-main">
      <v-container class="catalog-search-container pb-12 pt-0">
        <template v-if="!searchInline">
          <div v-if="activeRepoInfo" class="collection-info d-flex mb-2">
            <div class="collection-thumbnail-container d-none d-sm-block">
              <img
                :src="repoThumbnail"
                class="rounded"
                alt=""
                @error="repoThumbFailed = true"
              />
            </div>

            <div class="collection-info-body">
              <h1 class="collection-title">{{ activeRepoInfo.title }}</h1>
              <div class="mb-2">
                <a :href="aboutRepoUrl" class="repo-badge">{{ t('about') }}</a>
                <a :href="centralCatalogUrl" class="repo-badge">
                  <v-icon size="12" class="me-1">$mdi-arrow-left</v-icon>
                  {{ t('central_data_catalog') }}
                </a>
              </div>
              <p v-if="activeRepoInfo.short_text" class="collection-description mb-0">
                {{ activeRepoInfo.short_text }}
              </p>
            </div>
          </div>

          <CatalogSearchBar
            v-model="query.sk"
            :loading="loading"
            :search-provider="searchProvider"
            class="catalog-search-bar-block"
            @search="resetPage"
            @reset="onResetAll"
          />

          <CatalogTabs
            v-if="showTypeTabs && tabs && !isVariableView"
            v-model="query.tab_type"
            :tabs="tabs"
            class="catalog-type-tabs"
            @change="resetPage"
          />
        </template>

        <v-row class="catalog-results-row">
          <v-col v-if="showSidebarFacets" cols="12" md="3" lg="3">
            <CatalogFacets
              v-if="facets"
              :facets="facets"
              :query="query"
              :enabled-filters="enabledFilters"
              :central-catalog="!activeRepoInfo"
              @change="resetPage"
            />
          </v-col>

          <v-col cols="12" :md="showSidebarFacets ? 9 : 12" :lg="showSidebarFacets ? 9 : 12">
            <template v-if="searchInline">
              <CatalogSearchBar
                v-model="query.sk"
                :loading="loading"
                :search-provider="searchProvider"
                class="mb-4"
                @search="resetPage"
                @reset="onResetAll"
              />
              <CatalogTabs
                v-if="showTypeTabs && tabs && !isVariableView"
                v-model="query.tab_type"
                :tabs="tabs"
                class="catalog-type-tabs mb-4"
                @change="resetPage"
              />
            </template>

            <div
              v-if="showResultsOptionsBar"
              class="results-options-bar d-flex align-center justify-space-between mb-3"
            >
              <CatalogViewToggle
                v-if="showVariableToggle"
                :model-value="isVariableView ? 'variable' : 'study'"
                @update:model-value="setSearchView"
              />
              <div v-else />

              <div class="results-options-switches d-flex align-center flex-wrap">
                <v-switch
                  v-if="abstractToggleVisible && !isVariableView"
                  v-model="showAbstract"
                  density="compact"
                  hide-details
                  color="primary"
                  :label="t('Show abstract', 'Show abstract')"
                  class="results-option-switch flex-shrink-0"
                />
                <v-switch
                  v-if="debugModeAvailable && !isVariableView"
                  v-model="showDebug"
                  density="compact"
                  hide-details
                  color="primary"
                  label="Show debug"
                  class="results-option-switch flex-shrink-0"
                />
              </div>
            </div>

            <div v-if="activeFilters.length" class="active-filters-bar d-flex flex-wrap align-center ga-2 mb-4">
              <v-chip
                v-for="f in activeFilters"
                :key="f.chipKey"
                class="active-filter-chip"
                size="small"
                closable
                variant="flat"
                :style="filterChipStyle(f.colorIndex)"
                @click:close="onClearFilterChip(f)"
              >
                {{ f.value }}
              </v-chip>
              <v-btn
                size="x-small"
                variant="text"
                color="error"
                @click="onResetAll"
              >
                {{ t('reset_search') }}
              </v-btn>
            </div>

            <v-alert
              v-if="error"
              type="error"
              variant="tonal"
              class="mb-4"
              :text="error"
              closable
            />

            <SemanticSearchDebug
              v-if="!loading && showDebug && semanticDebug"
              :debug="semanticDebug"
              class="mb-4"
            />

            <template v-if="loading">
              <v-skeleton-loader
                v-for="i in 5"
                :key="i"
                type="list-item-three-line"
                class="mb-3 rounded"
              />
            </template>

            <template v-else>
              <CatalogVariableResultsList
                v-if="isVariableView && displayRowCount > 0"
                :results="results"
                :query="query"
                @sort="setSort"
                @page="onPage"
                @page-size="setPageSize"
              />
              <CatalogResultsList
                v-else-if="!isVariableView && displayRowCount > 0"
                :results="results"
                :query="query"
                :related-collections="relatedCollections"
                :citations="results?.citations"
                :data-classifications="facets?.data_class"
                :show-abstract="showAbstract && abstractToggleVisible"
                :show-debug="showDebug"
                @sort="setSort"
                @page="onPage"
                @page-size="setPageSize"
                @image-view="setImageView"
              />
              <CatalogResultsEmpty v-else-if="hasSearched" :keyword="query.sk" @reset="onResetAll" />
            </template>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <CatalogVariableCompareCart v-if="compareCartCount > 0" />
  </v-app>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { usePublicCatalogConfig } from '../composables/usePublicCatalogConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useCatalogSearch } from '../composables/useCatalogSearch';
import { useVariableCompareCart } from '../composables/useVariableCompareCart';
import CatalogSearchBar    from '../components/CatalogSearchBar.vue';
import CatalogTabs         from '../components/CatalogTabs.vue';
import CatalogFacets       from '../components/CatalogFacets.vue';
import CatalogViewToggle          from '../components/CatalogViewToggle.vue';
import CatalogResultsList         from '../components/CatalogResultsList.vue';
import CatalogResultsEmpty        from '../components/CatalogResultsEmpty.vue';
import { joinSiteUrl, catalogSearchUrl } from '../catalogUrls';
import { activeFilterChipBackground } from '../catalogFilterChipColors';
import { defaultCatalogIconUrl, resolveCollectionThumbnailUrl } from '../catalogThumbnail';

const CatalogVariableResultsList = defineAsyncComponent(
  () => import('../components/CatalogVariableResultsList.vue')
);
const SemanticSearchDebug = defineAsyncComponent(
  () => import('../components/SemanticSearchDebug.vue')
);
const CatalogVariableCompareCart = defineAsyncComponent(
  () => import('../components/CatalogVariableCompareCart.vue')
);

defineOptions({ name: 'CatalogSearchPage' });

const { siteConfig, baseUrl } = useAppConfig();
const { activeRepoInfo } = usePublicCatalogConfig();
const { t } = useI18n();
const { count: compareCartCount } = useVariableCompareCart();

const {
  query, results, facets, tabs, site, semanticDebug,
  enabledFilters, relatedCollections,
  loading, error, showTypeTabs, hasSearched,
  activeFilters, isVariableView, showVariableToggle,
  resetPage, goToPage, setSort, setPageSize, setImageView, clearFilter, clearFilterValue, clearAllFilters, setSearchView,
  siteUrl,
} = useCatalogSearch();

const showSidebarFacets = computed(() => Boolean(facets.value));

const showAbstract = ref(false);
const showDebug = ref(false);

const displayRowCount = computed(() => results.value?.rows?.length ?? 0);

const abstractToggleVisible = computed(() => site.value?.catalog_show_abstract !== 'no');

function isTruthyConfigFlag(value) {
  return value === true || value === 1 || value === '1' || value === 'yes';
}

const debugModeAvailable = computed(() =>
  isTruthyConfigFlag(site.value?.catalog_search_debug)
  || isTruthyConfigFlag(siteConfig.value?.catalog_search_debug)
);

const searchProvider = computed(
  () => site.value?.search_provider ?? siteConfig.value?.search_provider ?? 'db'
);

const showResultsOptionsBar = computed(() =>
  showVariableToggle.value
  || (abstractToggleVisible.value && !isVariableView.value)
  || debugModeAvailable.value
);

const searchInline = computed(() => {
  const orientation = site.value?.search_box_orientation
    ?? siteConfig.value?.search_box_orientation
    ?? 'default';
  return orientation === 'inline';
});

const repoThumbFailed = ref(false);

watch(
  () => activeRepoInfo.value?.repositoryid,
  () => { repoThumbFailed.value = false; }
);

const repoThumbnail = computed(() => {
  if (repoThumbFailed.value) {
    return defaultCatalogIconUrl(baseUrl.value, siteUrl.value);
  }
  return resolveCollectionThumbnailUrl(
    activeRepoInfo.value?.thumbnail,
    baseUrl.value,
    siteUrl.value
  );
});

const aboutRepoUrl = computed(() => {
  const id = activeRepoInfo.value?.repositoryid;
  return id ? joinSiteUrl(siteUrl.value, `collections/${id}/about`) : '#';
});

const centralCatalogUrl = computed(() => catalogSearchUrl(siteUrl.value, null));

function filterChipStyle(colorIndex) {
  return {
    '--chip-bg': activeFilterChipBackground(colorIndex),
    color: '#fff',
  };
}

function onClearFilterChip(chip) {
  if (chip.isYear) {
    clearFilter('year');
  } else if (chip.rawId != null && chip.rawId !== '') {
    clearFilterValue(chip.key, chip.rawId);
  } else {
    clearFilter(chip.key);
  }
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
</script>

<style scoped>
.catalog-search-main {
  padding-top: 0 !important;
}

/* Tight top spacing below site header / breadcrumbs — no extra title band. */
.catalog-search-container {
  margin-top: 0;
  padding-top: 0 !important;
}

.collection-thumbnail-container {
  display: inline-block;
  width: 100px;
  height: 100px;
  overflow: hidden;
  margin-right: 20px;
  flex-shrink: 0;
}

.collection-thumbnail-container img {
  width: 100%;
  position: relative;
  top: 50%;
  transform: translateY(-50%);
}

.collection-title {
  font-size: var(--catalog-font-count, 1.5rem);
  font-weight: 600;
  line-height: 1.3;
  margin-bottom: 0.25rem;
  color: var(--catalog-text, #1a2332);
}

.collection-description {
  font-size: var(--catalog-font-body, 1rem);
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
}

.repo-badge {
  display: inline-block;
  font-weight: normal;
  font-size: var(--catalog-font-ui, 0.875rem);
  border: 1px solid gainsboro;
  border-radius: 10rem;
  padding: 0.25em 0.6em;
  margin-right: 0.35rem;
  color: inherit;
  text-decoration: none;
  background: #f8f9fa;
}

.repo-badge:hover {
  background: #e9ecef;
  text-decoration: none;
}

.catalog-search-bar-block {
  margin-bottom: 1rem;
}

.catalog-type-tabs {
  margin-top: 0;
  margin-bottom: 1.25rem;
  min-width: 0;
  max-width: 100%;
}

.catalog-results-row {
  margin-top: 0;
}

.ga-2 {
  gap: 8px;
}

.active-filters-bar {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  padding-bottom: 12px;
}

.active-filter-chip {
  border-radius: 4px !important;
  background-color: var(--chip-bg) !important;
  color: #fff !important;
}

.active-filter-chip :deep(.v-chip__close) {
  margin-inline-start: 8px;
  color: #fff !important;
  opacity: 0.92;
}

.active-filter-chip :deep(.v-chip__close:hover) {
  opacity: 0.75;
}

.results-options-bar {
  width: 100%;
}

.results-options-switches {
  gap: 4px 16px;
  justify-content: flex-end;
}

.results-option-switch :deep(.v-label) {
  font-size: var(--catalog-font-ui, 0.875rem);
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}
</style>
