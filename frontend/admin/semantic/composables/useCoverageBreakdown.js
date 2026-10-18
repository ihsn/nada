import { ref, computed } from 'vue';
import { useSemanticApi } from './useSemanticApi.js';

/**
 * Catalog-vs-index coverage of studies per data type (catalog_total/indexed/missing/stale/errors). Used by
 * OverviewPage (the one-line Studies summary) and IndexingPage (the table and the actions to fix it), so the
 * totals and the segment math live in exactly one place.
 */
export function useCoverageBreakdown() {
  const {
    loading: loadingTypeBreakdown,
    error: typeBreakdownError,
    errorMessage: typeBreakdownErrorMessage,
    getTypeBreakdown,
  } = useSemanticApi();

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
  function rowSegments(row) {
    const total = row.catalog_total || 0;
    if (!total) return { indexedPct: 0, missingPct: 0, errorsPct: 0 };
    return {
      indexedPct: (row.indexed / total) * 100,
      missingPct: (Math.max(0, row.missing - row.errors) / total) * 100,
      errorsPct: (row.errors / total) * 100,
    };
  }

  async function loadTypeBreakdown() {
    try {
      const data = await getTypeBreakdown('survey');
      typeBreakdown.value = data?.items || [];
    } catch {
      // typeBreakdownError ref already set
    }
  }

  return {
    loadingTypeBreakdown,
    typeBreakdownError,
    typeBreakdownErrorMessage,
    typeBreakdown,
    typeBreakdownTotal,
    sortedTypeBreakdown,
    rowSegments,
    loadTypeBreakdown,
  };
}
