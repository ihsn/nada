import { inject, computed } from 'vue';

const APP_CONFIG_KEY = Symbol('APP_CONFIG');

/**
 * Shared app config composable. Use in any Vue app where the PHP view sets window.APP_CONFIG.
 * Prefer provide(APP_CONFIG_KEY, window.APP_CONFIG) in main.js so config is injectable (e.g. tests).
 * Falls back to window.APP_CONFIG when not provided.
 *
 * @returns {Object} { config, siteUrl, baseUrl, apiBaseUrl, datasetsApiBaseUrl, …, csrfToken, csrfTokenName, siteConfig }
 */
export function useAppConfig() {
  const provided = inject(APP_CONFIG_KEY, null);
  const config = computed(
    () => provided ?? (typeof window !== 'undefined' && window.APP_CONFIG) ?? {}
  );
  return {
    config,
    siteUrl: computed(() => config.value?.siteUrl ?? ''),
    baseUrl: computed(() => config.value?.baseUrl ?? ''),
    apiBaseUrl: computed(() => config.value?.apiBaseUrl ?? ''),
    datasetsApiBaseUrl: computed(() => config.value?.datasetsApiBaseUrl ?? ''),
    dataStructuresApiBaseUrl: computed(() => config.value?.dataStructuresApiBaseUrl ?? ''),
    codelistsApiBaseUrl: computed(() => config.value?.codelistsApiBaseUrl ?? ''),
    catalogApiBaseUrl: computed(() => config.value?.catalogApiBaseUrl ?? ''),
    studyEditBaseUrl: computed(() => config.value?.studyEditBaseUrl ?? ''),
    assetsBase: computed(() => config.value?.assetsBase ?? ''),
    csrfToken: computed(() => config.value?.csrfToken ?? ''),
    csrfTokenName: computed(() => config.value?.csrfTokenName ?? 'ncsrf'),
    routerPathBase: computed(() => {
      const p = config.value?.routerPathBase ?? '';
      return String(p).replace(/\/$/, '');
    }),
    siteConfig: computed(() => config.value?.siteConfig ?? {}),
    canManageCollectionAccess: computed(() => !!config.value?.canManageCollectionAccess),
    canEdit: computed(() => !!config.value?.canEdit),
    canDelete: computed(() => !!config.value?.canDelete),
  };
}

export { APP_CONFIG_KEY };
