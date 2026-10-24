import { inject } from 'vue';

const SET_MESSAGE_KEY = Symbol('analyticsSetMessage');

export function provideAnalyticsMessages(setMessage) {
  return { [SET_MESSAGE_KEY]: setMessage };
}

export function useAnalyticsMessages() {
  const setMessage = inject(SET_MESSAGE_KEY, null);
  return {
    notifyError(message) {
      if (setMessage) setMessage(message, 'error');
      else console.error(message);
    },
    notifySuccess(message) {
      if (setMessage) setMessage(message, 'success');
    },
  };
}

export { SET_MESSAGE_KEY };
