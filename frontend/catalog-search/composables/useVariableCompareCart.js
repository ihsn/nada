import { computed, ref } from 'vue';
import { joinSiteUrl } from '../catalogUrls';

const COOKIE_NAME = 'variable-compare';
const MAX_ITEMS = 10;
const COOKIE_DAYS = 1;

/** Shared cart state across catalog search components. */
const selectedIds = ref([]);
let initialized = false;
let cartItems = ref([]);
let cartLoading = ref(false);
let cartFetchToken = 0;

function parseCookieValue(raw) {
  if (!raw) return [];
  return raw
    .split(',')
    .map((s) => s.trim())
    .filter((s) => s.includes('/'));
}

function readCookie() {
  if (typeof document === 'undefined') return null;
  const prefix = `${COOKIE_NAME}=`;
  const parts = document.cookie.split(';');
  for (const part of parts) {
    const c = part.trim();
    if (c.startsWith(prefix)) {
      return decodeURIComponent(c.substring(prefix.length));
    }
  }
  return null;
}

function writeCookie(ids) {
  if (typeof document === 'undefined') return;
  if (!ids.length) {
    document.cookie = `${COOKIE_NAME}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
    return;
  }
  const expires = new Date();
  expires.setTime(expires.getTime() + COOKIE_DAYS * 24 * 60 * 60 * 1000);
  const value = encodeURIComponent(ids.join(','));
  document.cookie = `${COOKIE_NAME}=${value}; path=/; expires=${expires.toUTCString()}`;
}

function syncFromCookie() {
  selectedIds.value = parseCookieValue(readCookie());
}

function initCart() {
  if (initialized) return;
  initialized = true;
  syncFromCookie();
  if (typeof document !== 'undefined') {
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        syncFromCookie();
      }
    });
  }
}

export function compareItemKey(sid, vid) {
  return `${sid}/${vid}`;
}

export function useVariableCompareCart() {
  initCart();

  const count = computed(() => selectedIds.value.length);

  const studyCount = computed(() => {
    const studies = new Set();
    for (const id of selectedIds.value) {
      const slash = id.indexOf('/');
      if (slash > 0) studies.add(id.substring(0, slash));
    }
    return studies.size;
  });

  function isSelected(sid, vid) {
    return selectedIds.value.includes(compareItemKey(sid, vid));
  }

  function add(sid, vid) {
    const key = compareItemKey(sid, vid);
    if (selectedIds.value.includes(key)) return { ok: true };
    if (selectedIds.value.length >= MAX_ITEMS) {
      return { ok: false, reason: 'max' };
    }
    const next = [...selectedIds.value, key];
    selectedIds.value = next;
    writeCookie(next);
    return { ok: true };
  }

  function remove(sid, vid) {
    const key = compareItemKey(sid, vid);
    const next = selectedIds.value.filter((id) => id !== key);
    selectedIds.value = next;
    writeCookie(next);
    return { ok: true };
  }

  function removeByKey(key) {
    const next = selectedIds.value.filter((id) => id !== key);
    selectedIds.value = next;
    writeCookie(next);
  }

  function clear() {
    selectedIds.value = [];
    writeCookie([]);
    cartItems.value = [];
  }

  function setSelected(sid, vid, checked) {
    if (checked) return add(sid, vid);
    remove(sid, vid);
    return { ok: true };
  }

  /** Toggle selection; shows alert when max limit is reached (classic parity). */
  function toggleSelection(sid, vid, checked, t) {
    const result = setSelected(sid, vid, checked);
    if (!result.ok && result.reason === 'max' && typeof t === 'function') {
      window.alert(
        t(
          'js_compare_variable_max_limit',
          'You have selected the maximum variables to compare'
        )
      );
    }
    return result;
  }

  function tryOpenCompare(siteUrl, t) {
    const result = openCompare(siteUrl);
    if (!result.ok && result.reason === 'min' && typeof t === 'function') {
      window.alert(
        t(
          'js_compare_variable_select_atleast_2',
          'Select two or more variables to compare'
        )
      );
    }
    return result;
  }

  async function refreshCartDetails(siteUrl) {
    if (!selectedIds.value.length) {
      cartItems.value = [];
      return;
    }

    const token = ++cartFetchToken;
    cartLoading.value = true;
    const url = joinSiteUrl(siteUrl, 'catalog/variable_cart');

    try {
      const res = await fetch(url, { credentials: 'same-origin' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      if (token !== cartFetchToken) return;
      cartItems.value = Array.isArray(data) ? data : [];
      if (!cartItems.value.length && selectedIds.value.length) {
        clear();
      }
    } catch {
      if (token === cartFetchToken) {
        cartItems.value = [];
      }
    } finally {
      if (token === cartFetchToken) {
        cartLoading.value = false;
      }
    }
  }

  function comparePageUrl(siteUrl) {
    return joinSiteUrl(siteUrl, 'catalog/compare');
  }

  function openCompare(siteUrl) {
    if (selectedIds.value.length < 2) {
      return { ok: false, reason: 'min' };
    }
    window.open(comparePageUrl(siteUrl), 'compare', 'noopener');
    return { ok: true };
  }

  /**
   * @param {(key: string, fallback?: string, ...args: unknown[]) => string} t
   */
  function summaryText(t) {
    if (!count.value) {
      return t(
        'js_compare_variable_select_atleast_2',
        'Select two or more variables to compare'
      );
    }
    const varsLabel = t('variables selected from', 'variables selected from');
    const studiesLabel = t('studies', 'studies');
    return `${count.value} ${varsLabel} ${studyCount.value} ${studiesLabel}`;
  }

  return {
    selectedIds,
    cartItems,
    cartLoading,
    count,
    studyCount,
    isSelected,
    add,
    remove,
    removeByKey,
    clear,
    setSelected,
    toggleSelection,
    refreshCartDetails,
    comparePageUrl,
    openCompare,
    tryOpenCompare,
    summaryText,
    syncFromCookie,
    MAX_ITEMS,
  };
}
