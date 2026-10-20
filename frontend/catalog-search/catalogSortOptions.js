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

/**
 * The variable view sorts variables: relevance, their study's title, or the variable name. Each sorts both ways, which
 * is how the end of a long list is reached (the pager has no "last page").
 */
export const VARIABLE_SORT_OPTION_DEFS = [
  { sortBy: 'relevance', sortOrder: 'desc', labelKey: 'Relevance',            labelFallback: 'Relevance' },
  { sortBy: 'title',     sortOrder: 'asc',  labelKey: 'sort_study_title_az',  labelFallback: 'Study title (A-Z)' },
  { sortBy: 'title',     sortOrder: 'desc', labelKey: 'sort_study_title_za',  labelFallback: 'Study title (Z-A)' },
  { sortBy: 'name',      sortOrder: 'asc',  labelKey: 'sort_variable_name_az', labelFallback: 'Variable name (A-Z)' },
  { sortBy: 'name',      sortOrder: 'desc', labelKey: 'sort_variable_name_za', labelFallback: 'Variable name (Z-A)' },
];

/**
 * @param {(key: string, fallback?: string) => string} t
 * @param {{ variables?: boolean }} [options] variables: the variable view's sorts
 */
export function buildCatalogSortOptions(t, { variables = false } = {}) {
  const defs = variables ? VARIABLE_SORT_OPTION_DEFS : CATALOG_SORT_OPTION_DEFS;
  return defs.map((def) => ({
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
  if (sortBy === 'title' || sortBy === 'country' || sortBy === 'name') return 'asc';
  return 'desc';
}

export function parseCatalogSortValue(composite) {
  const [sortBy, sortOrder] = String(composite).split(':');
  return { sortBy, sortOrder };
}
