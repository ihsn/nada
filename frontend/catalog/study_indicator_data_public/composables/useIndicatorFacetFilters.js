import { reactive } from 'vue';

export const FACET_LIST_SEARCH_THRESHOLD = 20;

export function facetListNeedsSearch(items) {
  return Array.isArray(items) && items.length > FACET_LIST_SEARCH_THRESHOLD;
}

export function facetListItemsFiltered(componentName, items, searchMap = {}) {
  const list = Array.isArray(items) ? items : [];
  const q = String(searchMap[componentName] ?? '').trim().toLowerCase();
  if (!q) return list;
  return list.filter((opt) => {
    const t = String(opt?.title ?? '').toLowerCase();
    const v = String(opt?.value ?? '').toLowerCase();
    return t.includes(q) || v.includes(q);
  });
}

export function facetCodesInclude(arr, code) {
  if (!Array.isArray(arr)) return false;
  const s = String(code ?? '');
  return arr.some((x) => String(x ?? '') === s);
}

export function facetSetCodeSelected(arr, code, on) {
  if (!Array.isArray(arr)) return;
  const s = String(code ?? '');
  const idx = arr.findIndex((x) => String(x ?? '') === s);
  if (on && idx < 0) {
    arr.push(s);
  }
  if (!on && idx >= 0) {
    arr.splice(idx, 1);
  }
}

export function facetToggleCode(arr, code) {
  facetSetCodeSelected(arr, code, !facetCodesInclude(arr, code));
}

export function selectAllVisible(selection, componentName, items, searchMap = {}) {
  if (!Array.isArray(selection)) return;
  const visible = facetListItemsFiltered(componentName, items, searchMap);
  for (const opt of visible) {
    facetSetCodeSelected(selection, opt.value, true);
  }
}

export function deselectAllVisible(selection, componentName, items, searchMap = {}) {
  if (!Array.isArray(selection)) return;
  const visible = facetListItemsFiltered(componentName, items, searchMap);
  for (const opt of visible) {
    facetSetCodeSelected(selection, opt.value, false);
  }
}

/**
 * @param {{ from: string; to: string; dGeography: string[]; dPeriodicity: string[]; c: Record<string, string[]> }} filterDraft
 */
export function useIndicatorFacetFilters(filterDraft) {
  const facetListSearch = reactive({});

  function clearFacetGeography() {
    filterDraft.dGeography.splice(0, filterDraft.dGeography.length);
  }

  function clearFacetPeriodicity() {
    filterDraft.dPeriodicity.splice(0, filterDraft.dPeriodicity.length);
  }

  function onPeriodicityRadioChange(val) {
    filterDraft.dPeriodicity.splice(0);
    if (val) filterDraft.dPeriodicity.push(String(val));
  }

  function clearFacetDimension(colName) {
    const arr = filterDraft.c[colName];
    if (Array.isArray(arr)) {
      arr.splice(0, arr.length);
    }
  }

  return {
    facetListSearch,
    facetListNeedsSearch,
    facetListItemsFiltered: (componentName, items) => facetListItemsFiltered(componentName, items, facetListSearch),
    facetCodesInclude,
    facetSetCodeSelected,
    facetToggleCode,
    selectAllVisible: (selection, componentName, items) =>
      selectAllVisible(selection, componentName, items, facetListSearch),
    deselectAllVisible: (selection, componentName, items) =>
      deselectAllVisible(selection, componentName, items, facetListSearch),
    clearFacetGeography,
    clearFacetPeriodicity,
    onPeriodicityRadioChange,
    clearFacetDimension,
  };
}
