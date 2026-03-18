import { computed } from 'vue';

/**
 * Shared i18n composable. Reads translations from window.APP_CONFIG.translations,
 * which any PHP view can set (e.g. admin/catalog, templates, etc.).
 * Use wherever the page has set APP_CONFIG.translations.
 *
 * @returns {{ t: (key: string, fallback?: string, ...replacements: any[]) => string }}
 */
export function useI18n() {
  const translations = computed(
    () => (typeof window !== 'undefined' && window.APP_CONFIG?.translations) ?? {}
  );

  /**
   * Translate a key. Supports %s placeholders when extra args are passed.
   * @param {string} key - Language key (e.g. 'published', 'idno')
   * @param {string} [fallback] - Fallback when key is missing
   * @param {...*} replacements - Values to replace %s in order
   * @returns {string}
   */
  function t(key, fallback, ...replacements) {
    const str = translations.value[key] ?? fallback ?? key;
    if (replacements.length === 0) return str;
    let i = 0;
    return String(str).replace(/%s/g, () =>
      replacements[i++] !== undefined ? replacements[i - 1] : '%s'
    );
  }

  return { t };
}
