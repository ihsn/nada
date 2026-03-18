import { ref } from 'vue';

const visible = ref(false);
const title = ref('Confirm');
const message = ref('');
const confirmText = ref('Confirm');
const cancelText = ref('Cancel');
const mode = ref('confirm'); // 'confirm' | 'alert'

let resolvePromise = null;

/**
 * Global dialog singleton. Use in components:
 *   import $dialog from '@/shared/composables/dialog';
 *   const ok = await $dialog.confirm({ message: '...' });
 *   await $dialog.alert({ message: '...' });
 */
export const $dialog = {
  state: { visible, title, message, confirmText, cancelText, mode },

  /**
   * @param {Object} options
   * @param {string} [options.title='Confirm']
   * @param {string} options.message
   * @param {string} [options.confirmText='Confirm']
   * @param {string} [options.cancelText='Cancel']
   * @returns {Promise<boolean>} true if confirmed, false if cancelled
   */
  confirm(options = {}) {
    return new Promise((resolve) => {
      resolvePromise = resolve;
      title.value = options.title ?? 'Confirm';
      message.value = options.message ?? '';
      confirmText.value = options.confirmText ?? 'Confirm';
      cancelText.value = options.cancelText ?? 'Cancel';
      mode.value = 'confirm';
      visible.value = true;
    });
  },

  /**
   * @param {Object} options
   * @param {string} [options.title='Alert']
   * @param {string} options.message
   * @param {string} [options.buttonText='OK']
   * @returns {Promise<void>}
   */
  alert(options = {}) {
    return new Promise((resolve) => {
      resolvePromise = () => resolve();
      title.value = options.title ?? 'Alert';
      message.value = options.message ?? '';
      confirmText.value = options.buttonText ?? 'OK';
      cancelText.value = '';
      mode.value = 'alert';
      visible.value = true;
    });
  },

  onConfirm() {
    if (typeof resolvePromise === 'function') resolvePromise(true);
    resolvePromise = null;
    visible.value = false;
  },

  onCancel() {
    if (typeof resolvePromise === 'function') resolvePromise(false);
    resolvePromise = null;
    visible.value = false;
  },

  onAlertClose() {
    if (typeof resolvePromise === 'function') resolvePromise();
    resolvePromise = null;
    visible.value = false;
  },
};

export default $dialog;
