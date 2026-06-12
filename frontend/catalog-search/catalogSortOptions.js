/**
 * Legacy catalog sort dropdown options (search_nav_bar.php / var_search_nav_bar.php).
 * Each entry maps to sort_by + sort_order; value is a composite key for v-select.
 */

export const CATALOG_SORT_OPTION_DEFS = [
  { sortBy: 'relevance',  sortOrder: 'desc', labelKey: 'Relevance',           labelFallback: 'Relevance' },
  { sortBy: 'popularity', sortOrder: 'desc', labelKey: 'Popularity',          labelFallback: 'Popularity' },
  { sortBy: 'year',       sortOrder: 'desc', labelKey: 'Year (Recent &uarr;)', labelFallback: 'Year (Recent ↑)' },
  { sortBy: 'year',       sortOrder: 'asc',  labelKey: 'Year (Oldest &darr;)', labelFallback: 'Year (Oldest ↓)' },
  { sortBy: 'title',      sortOrder: 'asc',  labelKey: 'Title (A-Z)',         labelFallback: 'Title (A-Z)' },
  { sortBy: 'title',      sortOrder: 'desc', labelKey: 'Title (Z-A)',         labelFallback: 'Title (Z-A)' },
  { sortBy: 'country',    sortOrder: 'asc',  labelKey: 'Country (A-Z)',       labelFallback: 'Country (A-Z)' },
  { sortBy: 'country',    sortOrder: 'desc', labelKey: 'Country (Z-A)',       labelFallback: 'Country (Z-A)' },
];

/** @param {(key: string, fallback?: string) => string} t */
export function buildCatalogSortOptions(t) {
  return CATALOG_SORT_OPTION_DEFS.map((def) => ({
    value: `${def.sortBy}:${def.sortOrder}`,
    sortBy: def.sortBy,
    sortOrder: def.sortOrder,
    label: t(def.labelKey, def.labelFallback),
  }));
}

/** Normalize nation → country for legacy parity. */
export function catalogSortSelectionKey(sortBy, sortOrder) {
  const by = sortBy === 'nation' ? 'country' : (sortBy || 'relevance');
  const order = sortOrder || defaultSortOrderForField(by);
  return `${by}:${order}`;
}

function defaultSortOrderForField(sortBy) {
  if (sortBy === 'title' || sortBy === 'country') return 'asc';
  return 'desc';
}

export function parseCatalogSortValue(composite) {
  const [sortBy, sortOrder] = String(composite).split(':');
  return { sortBy, sortOrder };
}
