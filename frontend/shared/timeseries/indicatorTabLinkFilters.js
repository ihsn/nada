/**
 * Carry indicator filters across the catalog's Chart & data and Table tabs.
 *
 * Each tab keeps its filters in the page URL (from, to, geo, period, c[…]); the tab links are
 * plain server-rendered hrefs. Before a tab link is followed (click, middle-click, keyboard,
 * touch), copy the current filter params onto it. Chart-only and table-layout params stay put.
 */
import { Q_FROM, Q_GEO, Q_PERIOD, Q_TO } from './indicatorFilterQueryParams.js';

const TAB_PATH = /\/catalog\/\d+\/indicator-(chart|table)\/?$/;

function isFilterKey(key) {
  return (
    key === Q_FROM ||
    key === Q_TO ||
    key === Q_GEO ||
    key === Q_PERIOD ||
    key.startsWith('c_') ||
    /^c\[[^\]]+\]$/.test(key)
  );
}

function applyFiltersToLink(anchor) {
  if (!anchor || !anchor.href) return;
  let target;
  try {
    target = new URL(anchor.href, window.location.href);
  } catch (e) {
    return;
  }
  if (target.origin !== window.location.origin || !TAB_PATH.test(target.pathname)) return;

  const current = new URLSearchParams(window.location.search || '');
  for (const key of [...target.searchParams.keys()]) {
    if (isFilterKey(key)) target.searchParams.delete(key);
  }
  for (const [key, value] of current.entries()) {
    if (isFilterKey(key)) target.searchParams.append(key, value);
  }
  anchor.href = target.toString();
}

function onEvent(event) {
  const anchor = event.target && event.target.closest ? event.target.closest('a[href]') : null;
  applyFiltersToLink(anchor);
}

let installed = false;

export function installIndicatorTabLinkFilters() {
  if (installed || typeof document === 'undefined') return;
  installed = true;
  for (const type of ['mousedown', 'touchstart', 'focusin']) {
    document.addEventListener(type, onEvent, { capture: true, passive: true });
  }
}
