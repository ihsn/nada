import { inject, computed } from 'vue';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';

/**
 * Public catalog search shell only: repo slug and display info from window.APP_CONFIG
 * (set by the Catalog PHP shell). Not used by admin apps.
 */
export function usePublicCatalogConfig() {
  const provided = inject(APP_CONFIG_KEY, null);
  const config = computed(
    () => provided ?? (typeof window !== 'undefined' && window.APP_CONFIG) ?? {}
  );
  return {
    activeRepo: computed(() => config.value?.activeRepo ?? ''),
    activeRepoInfo: computed(() => config.value?.activeRepoInfo ?? null),
  };
}
