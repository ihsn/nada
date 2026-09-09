import { onBeforeUnmount, onMounted, unref } from 'vue';
import { onBeforeRouteLeave } from 'vue-router';

const DEFAULT_MESSAGE = 'You have unsaved changes. Leave this page?';

/**
 * Warn before refresh/close (browser dialog) and in-app navigation (confirm).
 *
 * @param {import('vue').MaybeRefOrGetter<boolean>} isDirty
 * @param {{ message?: string|(() => string) }} [options]
 */
export function useDirtyLeaveGuard(isDirty, options = {}) {
  function resolveMessage() {
    const message = options.message;
    if (typeof message === 'function') return message() || DEFAULT_MESSAGE;
    return message || DEFAULT_MESSAGE;
  }

  function isActive() {
    return !!unref(isDirty);
  }

  function confirmLeave() {
    if (!isActive()) return true;
    return window.confirm(resolveMessage());
  }

  function onBeforeUnload(e) {
    if (!isActive()) return;
    e.preventDefault();
    e.returnValue = '';
  }

  onMounted(() => {
    window.addEventListener('beforeunload', onBeforeUnload);
  });

  onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
  });

  onBeforeRouteLeave(() => confirmLeave());

  return { confirmLeave };
}
