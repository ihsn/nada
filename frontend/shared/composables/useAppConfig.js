import { inject, computed } from 'vue';

const APP_CONFIG_KEY = Symbol('APP_CONFIG');

/**
 * Shared app config composable. Use in any Vue app where the PHP view sets window.APP_CONFIG.
 * Prefer provide(APP_CONFIG_KEY, window.APP_CONFIG) in main.js so config is injectable (e.g. tests).
 * Falls back to window.APP_CONFIG when not provided.
 *
 * @returns {Object} { config, siteUrl, baseUrl, apiBaseUrl, codelistsApiBaseUrl, assetsBase, activeRepo, csrfToken, siteConfig, activeRepoInfo }
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
    assetsBase: computed(() => config.value?.assetsBase ?? ''),
    activeRepo: computed(() => config.value?.activeRepo ?? ''),
    csrfToken: computed(() => config.value?.csrfToken ?? ''),
    siteConfig: computed(() => config.value?.siteConfig ?? {}),
    activeRepoInfo: computed(() => config.value?.activeRepoInfo ?? null),
  };
}

export { APP_CONFIG_KEY };
