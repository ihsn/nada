import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { cloneDeep, isEqual } from 'lodash';

const DEFAULT_LEAVE_MESSAGE = 'You have unsaved changes. Leave this page?';
const DEFAULT_RELOAD_MESSAGE = 'You have unsaved changes. Reload and discard them?';

function resolveMessage(message, fallback) {
  if (typeof message === 'function') return message() || fallback;
  return message || fallback;
}

export function useUnsavedChangesGuard({
  getCurrent,
  getWatchSource,
  isEnabled = () => true,
  leaveMessage = DEFAULT_LEAVE_MESSAGE,
  reloadMessage = DEFAULT_RELOAD_MESSAGE,
}) {
  const savedBaseline = ref(null);
  const dirtyTick = ref(0);

  function markClean() {
    savedBaseline.value = cloneDeep(getCurrent() || {});
    dirtyTick.value += 1;
  }

  function bumpDirtyCheck() {
    dirtyTick.value += 1;
  }

  const isDirty = computed(() => {
    dirtyTick.value;
    if (!isEnabled() || savedBaseline.value == null) return false;
    return !isEqual(getCurrent() || {}, savedBaseline.value);
  });

  watch(getWatchSource, bumpDirtyCheck, { deep: true });

  function confirmReload(message = reloadMessage) {
    if (!isDirty.value) return true;
    return window.confirm(resolveMessage(message, DEFAULT_RELOAD_MESSAGE));
  }

  function confirmLeave(message = leaveMessage) {
    if (!isDirty.value) return true;
    return window.confirm(resolveMessage(message, DEFAULT_LEAVE_MESSAGE));
  }

  function onBeforeUnload(e) {
    if (!isDirty.value) return;
    e.preventDefault();
    e.returnValue = '';
  }

  function onDocumentClick(e) {
    if (!isDirty.value) return;

    const link = e.target?.closest?.('a[href]');
    if (!link) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

    let targetUrl;
    try {
      targetUrl = new URL(link.href, window.location.href);
    } catch {
      return;
    }

    if (targetUrl.href === window.location.href) return;

    if (!window.confirm(resolveMessage(leaveMessage, DEFAULT_LEAVE_MESSAGE))) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  }

  onMounted(() => {
    window.addEventListener('beforeunload', onBeforeUnload);
    document.addEventListener('click', onDocumentClick, true);
  });

  onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
    document.removeEventListener('click', onDocumentClick, true);
  });

  return {
    isDirty,
    markClean,
    confirmReload,
    confirmLeave,
  };
}
