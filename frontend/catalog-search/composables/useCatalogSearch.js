import { reactive, ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { usePublicCatalogConfig } from './usePublicCatalogConfig';
import { useI18n } from '@/shared/composables/useI18n';
import {
  DEFAULT_QUERY,
  STANDARD_QUERY_KEYS,
  parseRouteQuery,
  serializeRouteQuery,
  catalogQueryFingerprint,
  normalizeYearRange,
  isVariableViewTab,
  isCatalogVariableViewEnabled,
} from '../catalogQuery';
import { activeFilterChipColorIndex } from '../catalogFilterChipColors';
import {
  formatSingleFilterDisplayValue,
  parseMultiFilterValue,
} from '../catalogFilterLabels';

const FILTER_KEYS = ['country', 'region', 'dtype', 'data_class', 'type', 'tag', 'from', 'to', 'collection', 'sk', 'database'];

/** Merge featured studies at top and dedupe, matching legacy surveys.php behavior. */
function mergeFeaturedRows(result, featuredStudies) {
  if (!result?.rows || !Array.isArray(featuredStudies) || !featuredStudies.length) {
    return result;
  }

  const featuredIds = new Set(featuredStudies.map((s) => s.id));
  const rows = [];

  for (const study of featuredStudies) {
    rows.push({ ...study, featured: true });
  }

  for (const row of result.rows) {
    if (!featuredIds.has(row.id)) {
      rows.push(row);
    }
  }

  return { ...result, rows };
}

let activeAbortController = null;

/** Repo + data-type tab — sidebar facets only change when this changes. */
function facetsContextKey(activeRepo, tabType) {
  return `${activeRepo || ''}|${tabType || ''}`;
}

export function useCatalogSearch() {
  const route = useRoute();
  const router = useRouter();
  const { apiBaseUrl, siteUrl, siteConfig, config } = useAppConfig();
  const { activeRepo } = usePublicCatalogConfig();
  const { t } = useI18n();

  /** Mutable mirror of route query — updated whenever the URL changes (including back/forward). */
  const query = reactive({ ...DEFAULT_QUERY });

  const results            = ref(null);
  const searchType         = ref('study');
  const facets             = ref(null);
  const tabs               = ref(null);
  const site               = ref(null);
  const enabledFilters     = ref([]);
  const relatedCollections = ref({});
  const loading            = ref(false);
  const error              = ref(null);
  const hasSearched        = ref(false);
  const semanticDebug      = ref(null);
  let cachedFacetsContextKey = null;
  let initialBootstrapConsumed = false;

  function applyRouteToQuery() {
    const parsed = parseRouteQuery(route.query);
    for (const key of Object.keys(query)) {
      if (!STANDARD_QUERY_KEYS.has(key) && !(key in DEFAULT_QUERY)) {
        delete query[key];
      }
    }
    Object.assign(query, { ...DEFAULT_QUERY, ...parsed });
  }

  /**
   * Push or replace router query from current `query` state.
   * Navigation triggers the route watcher → fresh API search.
   */
  function navigateFromState(replace = false) {
    const { from, to } = normalizeYearRange(query.from, query.to);
    query.from = from;
    query.to = to;
    const nextQuery = serializeRouteQuery(query);
    return replace
      ? router.replace({ query: nextQuery })
      : router.push({ query: nextQuery });
  }

  const hasActiveFilters = computed(() =>
    ['country', 'region', 'dtype', 'data_class', 'database', 'type', 'tag', 'from', 'to', 'collection'].some(
      (k) => query[k] !== '' && query[k] != null
    )
  );

  function pushMultiValueFilterChips(out, key, label, rawValue, facetData, labelResolver = null) {
    const parts = parseMultiFilterValue(rawValue);
    for (const id of parts) {
      const chipKey = `${key}:${id}`;
      const display = labelResolver
        ? labelResolver(id)
        : formatSingleFilterDisplayValue(key, id, facetData, t);
      out.push({
        key,
        chipKey,
        label,
        value: display,
        rawId: id,
        colorIndex: activeFilterChipColorIndex(key, chipKey),
      });
    }
  }

  const activeFilters = computed(() => {
    const out = [];
    const facetData = facets.value;
    const fieldLabels = {
      sk: t('keywords'),
      country: t('filter_by_country', 'Country'),
      region: t('filter_by_region', 'Region'),
      dtype: t('filter_by_dtype', 'Data access'),
      data_class: t('data_classification', 'Classification'),
      database: t('filter_by_database', 'Dataset'),
      type: t('filter_by_type', 'Data type'),
      tag: t('filter_by_tag', 'Tag'),
      collection: t('filter_by_collection', 'Collection'),
    };

    for (const key of FILTER_KEYS) {
      if (key === 'from' || key === 'to') continue;
      if (!query[key]) continue;
      if (key === 'sk') {
        out.push({
          key,
          chipKey: key,
          label: fieldLabels[key],
          value: query[key],
          rawId: null,
          colorIndex: activeFilterChipColorIndex(key, key),
        });
        continue;
      }
      pushMultiValueFilterChips(out, key, fieldLabels[key] || key, query[key], facetData);
    }

    if (facetData) {
      for (const [facetKey, facet] of Object.entries(facetData)) {
        if (!facet || facet.type !== 'user' || !query[facetKey]) continue;
        pushMultiValueFilterChips(
          out,
          facetKey,
          facet.title || facetKey,
          query[facetKey],
          facetData
        );
      }
    }

    if (query.from || query.to) {
      out.push({
        key: 'year',
        chipKey: 'year',
        label: t('filter_by_year', 'Year'),
        value: [query.from, query.to].filter(Boolean).join('–'),
        isYear: true,
        rawId: null,
        colorIndex: activeFilterChipColorIndex('year', 'year', { isYear: true }),
      });
    }
    return out;
  });

  const showTypeTabs = computed(() => {
    const cfg = site.value?.data_types_nav_bar ?? '';
    return cfg === 'yes' || cfg === true;
  });

  const isVariableView = computed(() => query.view === 'v');

  const showVariableToggle = computed(() => {
    const cfg = site.value ?? siteConfig.value ?? {};
    if (!isCatalogVariableViewEnabled(cfg)) return false;
    return isVariableViewTab(query.tab_type);
  });

  function setSearchView(mode) {
    if (mode === 'variable') {
      query.view = 'v';
    } else {
      query.view = '';
      query.vk   = '';
      query.vf   = '';
    }
    query.page = 1;
    return navigateFromState(false);
  }

  function clearFilter(key) {
    if (key === 'year') {
      query.from = '';
      query.to   = '';
    } else {
      query[key] = '';
    }
  }

  /** Remove one value from a comma-separated multi-select filter. */
  function clearFilterValue(key, rawId) {
    if (key === 'year') {
      query.from = '';
      query.to   = '';
      return;
    }
    if (rawId == null || rawId === '') {
      query[key] = '';
      return;
    }
    const parts = parseMultiFilterValue(query[key]);
    const next = parts.filter((part) => String(part) !== String(rawId));
    query[key] = next.join(',');
  }

  function clearAllFilters() {
    ['country', 'region', 'dtype', 'data_class', 'database', 'type', 'tag', 'collection', 'from', 'to', 'view', 'vk', 'vf'].forEach(
      (k) => { query[k] = ''; }
    );
    for (const key of Object.keys(query)) {
      if (!STANDARD_QUERY_KEYS.has(key)) {
        query[key] = '';
      }
    }
  }

  function setSort(by, order) {
    query.sort_by    = by;
    query.sort_order = order;
    query.page       = 1;
    return navigateFromState(false);
  }

  function resetPage() {
    query.page = 1;
    return navigateFromState(false);
  }

  function goToPage(page) {
    query.page = page;
    return navigateFromState(true);
  }

  function setPageSize(ps) {
    query.ps   = ps;
    query.page = 1;
    return navigateFromState(false);
  }

  function setImageView(mode) {
    query.image_view = mode === 'gallery' ? 'thumbnail' : '';
    query.page = 1;
    return navigateFromState(false);
  }

  function applyBrowseResponse(json, { updateResults = true } = {}) {
    if (!json || json.status !== 'success') {
      return false;
    }

    let result = json.result ?? { rows: [], found: 0, total: 0, page: 1 };
    searchType.value = json.search_type ?? 'study';
    if (updateResults && json.featured_studies && searchType.value !== 'variable') {
      result = mergeFeaturedRows(result, json.featured_studies);
    }

    if (updateResults) {
      results.value = result;
      semanticDebug.value = result.debug ?? null;
      relatedCollections.value = json.related_collections ?? {};
    }

    site.value = json.site ?? site.value;

    if (json.facets != null) {
      facets.value = json.facets;
      cachedFacetsContextKey = facetsContextKey(activeRepo.value, query.tab_type);
    }

    if (Array.isArray(json.enabled_filters)) {
      enabledFilters.value = json.enabled_filters;
    }

    const searchTabs = json.tabs ?? {};
    tabs.value = {
      types: searchTabs.types ?? facets.value?.types ?? tabs.value?.types,
      search_counts_by_type: searchTabs.search_counts_by_type ?? {},
      active_tab: searchTabs.active_tab ?? json.tab_type ?? query.tab_type ?? '',
    };

    return true;
  }

  function tryConsumeInitialBootstrap() {
    if (initialBootstrapConsumed) {
      return false;
    }

    const bootstrap = config.value?.initialSearch;
    const expectedKey = config.value?.initialSearchQueryKey;
    if (!bootstrap || bootstrap.status !== 'success' || !expectedKey) {
      return false;
    }

    const currentKey = catalogQueryFingerprint(route.query);
    const bootstrapKey = bootstrap.queryKey ?? expectedKey;
    if (currentKey !== bootstrapKey && currentKey !== expectedKey) {
      return false;
    }

    initialBootstrapConsumed = true;
    error.value = null;
    semanticDebug.value = null;
    applyBrowseResponse(bootstrap, { updateResults: true });
    loading.value = false;
    hasSearched.value = true;
    return true;
  }

  async function fetchSearch({ silent = false, facetsOnly = false } = {}) {
    if (activeAbortController) {
      activeAbortController.abort();
    }
    activeAbortController = new AbortController();
    const { signal } = activeAbortController;

    if (!silent) {
      loading.value = true;
    }
    error.value   = null;
    if (!facetsOnly) {
      semanticDebug.value = null;
    }

    const apiParams = new URLSearchParams();
    apiParams.set('catalog_browse', '1');

    const repo = activeRepo.value;
    if (repo) apiParams.set('repo', repo);

    const contextKey = facetsContextKey(repo, query.tab_type);
    const needCatalogFacets = contextKey !== cachedFacetsContextKey || facets.value == null;
    if (needCatalogFacets) {
      apiParams.set('include_facets', '1');
    }

    const strKeys = [
      'sk', 'tab_type', 'type', 'collection', 'region', 'dtype', 'data_class', 'database',
      'country', 'tag', 'from', 'to', 'sort_by', 'sort_order',
      'view', 'vk', 'vf', 'image_view',
    ];
    for (const key of strKeys) {
      if (query[key] !== '' && query[key] != null) apiParams.set(key, query[key]);
    }

    for (const key of Object.keys(query)) {
      if (!STANDARD_QUERY_KEYS.has(key) && query[key] !== '' && query[key] != null) {
        apiParams.set(key, query[key]);
      }
    }

    apiParams.set('page', query.page);
    apiParams.set('ps',   query.ps);

    const url = apiBaseUrl.value + 'search?' + apiParams.toString();

    try {
      const res = await fetch(url, { credentials: 'same-origin', signal });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      if (json.status !== 'success') {
        let msg = json.message || json.errors || 'Search failed';
        if (json.semantic_debug) {
          semanticDebug.value = json.semantic_debug;
        }
        throw new Error(msg);
      }

      applyBrowseResponse(json, { updateResults: !facetsOnly });
    } catch (e) {
      if (e.name === 'AbortError') return;
      error.value = e.message;
    } finally {
      if (!signal.aborted) {
        if (!silent) {
          loading.value = false;
        }
        hasSearched.value = true;
      }
    }
  }

  /**
   * URL is the source of truth: any change (including back/forward) re-runs search.
   * SSR bootstrap is used once when it matches the current query.
   */
  watch(
    () => route.query,
    async () => {
      applyRouteToQuery();
      if (tryConsumeInitialBootstrap()) {
        return;
      }
      await fetchSearch();
    },
    { deep: true, immediate: true }
  );

  /** Leaving study/geospatial tab clears variable view (legacy only offers toggle on those tabs). */
  watch(
    () => query.tab_type,
    (tab) => {
      if (!isVariableViewTab(tab) && query.view === 'v') {
        query.view = '';
        query.vk   = '';
        query.vf   = '';
        navigateFromState(true);
      }
    }
  );

  return {
    query, results, searchType, facets, tabs, site, semanticDebug,
    enabledFilters, relatedCollections,
    loading, error, showTypeTabs, hasSearched,
    hasActiveFilters, activeFilters, isVariableView, showVariableToggle,
    resetPage, goToPage, setSort, setPageSize, setImageView, clearFilter, clearFilterValue, clearAllFilters, setSearchView,
    siteUrl,
  };
}
