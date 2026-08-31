import { inject, provide, ref } from 'vue';

export const METADATA_FORM_UI_KEY = Symbol('metadataFormUi');

export function createMetadataFormUi() {
  const fieldFilter = ref('all');
  const treeQuery = ref('');
  const showAllHelp = ref(false);

  function filterState() {
    return {
      mode: fieldFilter.value,
      query: treeQuery.value,
    };
  }

  return {
    fieldFilter,
    treeQuery,
    showAllHelp,
    filterState,
  };
}

export function provideMetadataFormUi(ui) {
  provide(METADATA_FORM_UI_KEY, ui);
  return ui;
}

export function useMetadataFormUi() {
  return inject(METADATA_FORM_UI_KEY, null);
}
