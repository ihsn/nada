import { reactive, ref, computed } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { usePublicCatalogConfig } from './usePublicCatalogConfig';

const FILTER_KEYS = ['country', 'region', 'dtype', 'data_class', 'tag', 'from', 'to', 'collection', 'sk'];

// Keys that are standard — user-defined facet keys are anything beyond these
const STANDARD_KEYS = new Set([
  'sk', 'tab_type', 'collection', 'region', 'dtype', 'data_class',
  'country', 'tag', 'from', 'to', 'sort_by', 'sort_order', 'page', 'ps',
]);

// Keys that are omitted from the URL when at their default value
const DEFAULT_VALUES = {
  sk: '', tab_type: '', collection: '', region: '', dtype: '', data_class: '',
  country: '', tag: '', from: '', to: '', sort_by: '', sort_order: '',
  page: 1, ps: 15,
};

export function useCatalogSearch() {
  const { apiBaseUrl } = useAppConfig();
  const { activeRepo } = usePublicCatalogConfig();

  const query = reactive({ ...DEFAULT_VALUES });

  const results = ref(null);
  const facets  = ref(null);
  const tabs    = ref(null);
  const site    = ref(null);
  const loading = ref(false);
  const error   = ref(null);

  // ── URL sync ────────────────────────────────────────────────────────────────

  /** Read current page URL params into query (called once on mount). */
  function loadFromUrl() {
    const params = new URLSearchParams(window.location.search);
    for (const [key, val] of params.entries()) {
      if (key === 'page') {
        query.page = Math.max(1, parseInt(val) || 1);
      } else if (key === 'ps') {
        query.ps = parseInt(val) || 15;
      } else {
        query[key] = val;
      }
    }
  }

  /**
   * Push or replace the browser URL to reflect the current query state.
   * Page changes use replaceState (no extra history entry).
   * All other changes use pushState so the back button works.
   */
  function syncToUrl(replace = false) {
    const params = new URLSearchParams();
    for (const [key, def] of Object.entries(DEFAULT_VALUES)) {
      const val = query[key];
      if (val !== def && val !== '' && val != null) {
        params.set(key, val);
      }
    }
    // User-defined facet keys
    for (const key of Object.keys(query)) {
      if (!STANDARD_KEYS.has(key) && query[key] !== '' && query[key] != null) {
        params.set(key, query[key]);
      }
    }
    const qs = params.toString();
    const newUrl = window.location.pathname + (qs ? '?' + qs : '');
    if (replace) {
      history.replaceState(null, '', newUrl);
    } else {
      history.pushState(null, '', newUrl);
    }
  }

  // ── Filters ──────────────────────────────────────────────────────────────────

  const hasActiveFilters = computed(() =>
    ['country', 'region', 'dtype', 'data_class', 'tag', 'from', 'to', 'collection'].some(
      (k) => query[k] !== '' && query[k] != null
    )
  );

  const activeFilters = computed(() => {
    const out = [];
    const labels = {
      sk: 'Keyword', country: 'Country', region: 'Region', dtype: 'Data access',
      data_class: 'Classification', tag: 'Tag', collection: 'Collection',
    };
    for (const key of FILTER_KEYS) {
      if (key === 'from' || key === 'to') continue;
      if (query[key]) out.push({ key, label: labels[key] || key, value: query[key] });
    }
    if (query.from || query.to) {
      out.push({ key: 'year', label: 'Year', value: [query.from, query.to].filter(Boolean).join('–'), isYear: true });
    }
    return out;
  });

  function clearFilter(key) {
    if (key === 'year') {
      query.from = '';
      query.to   = '';
    } else {
      query[key] = '';
    }
  }

  function clearAllFilters() {
    ['country', 'region', 'dtype', 'data_class', 'tag', 'collection', 'from', 'to'].forEach(
      (k) => { query[k] = ''; }
    );
  }

  function setSort(by, order) {
    query.sort_by    = by;
    query.sort_order = order;
    query.page       = 1;
    return search();
  }

  // ── Search ───────────────────────────────────────────────────────────────────

  async function search(replaceUrl = false) {
    loading.value = true;
    error.value   = null;

    // Sync URL before fetching so the address bar updates immediately
    syncToUrl(replaceUrl);

    const apiParams = new URLSearchParams();
    apiParams.set('include_facets', '1');

    const repo = activeRepo.value;
    if (repo) apiParams.set('repo', repo);

    const strKeys = ['sk', 'tab_type', 'collection', 'region', 'dtype', 'data_class',
                     'country', 'tag', 'from', 'to', 'sort_by', 'sort_order'];
    for (const key of strKeys) {
      if (query[key] !== '' && query[key] != null) apiParams.set(key, query[key]);
    }

    // User-defined facet keys
    for (const key of Object.keys(query)) {
      if (!STANDARD_KEYS.has(key) && query[key] !== '' && query[key] != null) {
        apiParams.set(key, query[key]);
      }
    }

    apiParams.set('page', query.page);
    apiParams.set('ps',   query.ps);

    const url = apiBaseUrl.value + 'search?' + apiParams.toString();

    try {
      const res = await fetch(url, { credentials: 'same-origin' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      if (json.status !== 'success') throw new Error(json.message || 'Search failed');
      results.value = json.result ?? { rows: [], found: 0, total: 0, page: 1 };
      facets.value  = json.facets ?? null;
      tabs.value    = json.tabs   ?? null;
      site.value    = json.site   ?? null;
    } catch (e) {
      error.value = e.message;
    } finally {
      loading.value = false;
    }
  }

  /** User-initiated filter/search change — reset to page 1, push new history entry. */
  function resetPage() {
    query.page = 1;
    return search(false);
  }

  /** Pagination click — keep history entry count down with replaceState. */
  function goToPage(page) {
    query.page = page;
    return search(true);
  }

  return {
    query, results, facets, tabs, site, loading, error,
    hasActiveFilters, activeFilters,
    search, resetPage, goToPage, setSort, clearFilter, clearAllFilters, loadFromUrl,
  };
}
