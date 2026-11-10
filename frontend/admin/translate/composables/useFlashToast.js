import { reactive } from 'vue';

/**
 * Snackbar state plus a one-shot success message passed through router history state.
 */
export function useFlashToast() {
  const toast = reactive({ open: false, message: '', color: 'success' });

  function showToast(message, color = 'success') {
    toast.message = String(message);
    toast.color = color;
    toast.open = true;
  }

  function consumeFlash() {
    const flash = typeof history !== 'undefined' ? history.state?.flashSuccess : null;
    if (!flash) return;
    showToast(flash);
    const rest = { ...history.state };
    delete rest.flashSuccess;
    history.replaceState(rest, '');
  }

  return { toast, showToast, consumeFlash };
}
