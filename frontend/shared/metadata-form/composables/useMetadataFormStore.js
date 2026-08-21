import { inject, provide } from 'vue';

export const METADATA_FORM_STORE_KEY = Symbol('METADATA_FORM_STORE');

export function provideMetadataFormStore(store) {
  provide(METADATA_FORM_STORE_KEY, store);
}

export function useMetadataFormStore() {
  const store = inject(METADATA_FORM_STORE_KEY, null);
  if (!store) {
    throw new Error('Metadata form store not provided');
  }
  return store;
}
