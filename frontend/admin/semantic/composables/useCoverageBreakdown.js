import { ref, computed } from 'vue';
import { useSemanticApi } from './useSemanticApi.js';

/**
 * Shared catalog-vs-index coverage data: the overall summary (catalog_total,
 * state.indexed/pending/failed) plus the per-data-type breakdown (indexed/
 * missing/stale/errors). Used by both OverviewPage (read-only glance) and
 * DiffPage/Index (the same numbers, plus the actions to fix them) so the
 * percentage/segment math lives in exactly one place.
 */
export function useCoverageBreakdown() {
  const {
    loading: loadingSummary,
    error: summaryError,
    errorMessage: summaryErrorMessage,
    getDiffSummary,
  } = useSemanticApi();
  const {
    loading: loadingTypeBreakdown,
    error: typeBreakdownError,
    errorMessage: typeBreakdownErrorMessage,
    getTypeBreakdown,
  } = useSemanticApi();

  const summary = ref({ catalog_total: 0, state: {} });
  const typeBreakdown = ref([]);

  const typeBreakdownTotal = computed(() =>
    typeBreakdown.value.reduce(
      (acc, row) => ({
        catalog_total: acc.catalog_total + row.catalog_total,
        indexed: acc.indexed + row.indexed,
        missing: acc.missing + row.missing,
        stale: acc.stale + row.stale,
        errors: acc.errors + row.errors,
      }),
      { catalog_total: 0, indexed: 0, missing: 0, stale: 0, errors: 0 }
    )
  );

  // Errors-then-missing so the type most in need of attention leads, not alphabetical order.
  const sortedTypeBreakdown = computed(() =>
    [...typeBreakdown.value].sort((a, b) => b.errors - a.errors || b.missing - a.missing)
  );

  /** Indexed / missing-without-error / errors as a % of catalog_total — errors is a subset of missing. */
  function segmentsOf(catalogTotal, indexed, missingIncludingErrors, errors) {
    const total = catalogTotal || 0;
    if (!total) return { indexedPct: 0, missingPct: 0, errorsPct: 0 };
    return {
      indexedPct: (indexed / total) * 100,
      missingPct: (Math.max(0, missingIncludingErrors - errors) / total) * 100,
      errorsPct: (errors / total) * 100,
    };
  }

  function rowSegments(row) {
    return segmentsOf(row.catalog_total, row.indexed, row.missing, row.errors);
  }

  const kpiTiles = computed(() => {
    const catalogTotal = summary.value.catalog_total || 0;
    const indexed = summary.value.state.indexed ?? 0;
    const errors = typeBreakdownTotal.value.errors;
    const missing = Math.max(0, typeBreakdownTotal.value.missing - errors);
    const stale = typeBreakdownTotal.value.stale;
    const pct = catalogTotal ? Math.round((indexed / catalogTotal) * 1000) / 10 : 0;
    return [
      {
        key: 'indexed',
        label: 'Indexed',
        value: indexed,
        caption: catalogTotal
          ? `${pct}% of ${catalogTotal.toLocaleString()} catalog records`
          : 'No catalog records yet',
        icon: 'mdi-database-check-outline',
        tone: 'success',
        attention: false,
        to: null,
      },
      {
        key: 'missing',
        label: 'Missing',
        value: missing,
        caption: 'In the catalog, not indexed',
        icon: 'mdi-database-remove-outline',
        tone: 'warning',
        attention: missing > 0,
        to: '/diff',
      },
      {
        key: 'errors',
        label: 'Errors',
        value: errors,
        caption: 'Failed to index',
        icon: 'mdi-alert-circle-outline',
        tone: 'error',
        attention: errors > 0,
        to: { path: '/diff', query: { hasError: '1' } },
      },
      {
        key: 'stale',
        label: 'Stale',
        value: stale,
        caption: 'Indexed, no longer in the catalog',
        icon: 'mdi-database-clock-outline',
        tone: 'stale',
        attention: stale > 0,
        to: { path: '/diff', query: { view: 'stale' } },
      },
    ];
  });

  async function loadSummary() {
    try {
      const data = await getDiffSummary('survey');
      summary.value = data || { catalog_total: 0, state: {} };
    } catch {
      // summaryError ref already set
    }
  }

  async function loadTypeBreakdown() {
    try {
      const data = await getTypeBreakdown('survey');
      typeBreakdown.value = data?.items || [];
    } catch {
      // typeBreakdownError ref already set
    }
  }

  function loadCoverage() {
    loadSummary();
    loadTypeBreakdown();
  }

  return {
    loadingSummary,
    summaryError,
    summaryErrorMessage,
    loadingTypeBreakdown,
    typeBreakdownError,
    typeBreakdownErrorMessage,
    summary,
    typeBreakdown,
    typeBreakdownTotal,
    sortedTypeBreakdown,
    rowSegments,
    kpiTiles,
    loadSummary,
    loadTypeBreakdown,
    loadCoverage,
  };
}
