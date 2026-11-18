import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** @type {Promise<object>|null} */
let optionsPromise = null;

/**
 * Client for api/admin/users. Every POST carries the session CSRF token in X-CSRF-TOKEN.
 */
export function useUsersApi() {
  const { apiBaseUrl, csrfToken } = useAppConfig();

  function url(path = '') {
    return `${String(apiBaseUrl.value || '').replace(/\/?$/, '/')}${path}`;
  }

  async function get(path, params) {
    const { data } = await axios.get(url(path), { params });
    return data;
  }

  async function post(path, body = {}) {
    const { data } = await axios.post(url(path), body, {
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.value || '' },
    });
    return data;
  }

  /** Roles, countries and the caller's permissions; loaded once per page. */
  function options() {
    if (!optionsPromise) {
      optionsPromise = get('options')
        .then((d) => d.options)
        .catch((e) => {
          optionsPromise = null;
          throw e;
        });
    }
    return optionsPromise;
  }

  return {
    options,
    listUsers: (params) => get('', params).then((d) => d.result),
    getUser: (id) => get(String(id)).then((d) => d.user),
    createUser: (body) => post('', body).then((d) => d.user),
    updateUser: (id, body) => post(String(id), body).then((d) => d.user),
    deleteUser: (id) => post(`${id}/delete`),
    batch: (body) => post('batch', body),
    userApiKeys: (id) => get(`${id}/api_keys`).then((d) => d.keys),
    createApiKey: (id, body) => post(`${id}/api_keys`, body),
    listApiKeys: (params) => get('api_keys', params).then((d) => d.result),
    revokeApiKey: (keyId) => post(`api_keys/${keyId}/revoke`).then((d) => d.key),
    extendApiKey: (keyId, months) => post(`api_keys/${keyId}/extend`, { months }).then((d) => d.key),
    deleteApiKey: (keyId) => post(`api_keys/${keyId}/delete`),
  };
}

const ERROR_MESSAGES = {
  SESSION_REQUIRED: ['session_expired', 'Your session has expired. Sign in again.'],
  'SESSION-REQUIRED': ['session_expired', 'Your session has expired. Sign in again.'],
  'CSRF-TOKEN-INVALID': ['csrf_token_invalid', 'The page is out of date. Reload it and try again.'],
  ACCESS_DENIED: ['ACCESS_DENIED', 'Access denied'],
  NOT_FOUND: ['not_found', 'Not found.'],
  PROTECTED_ACCOUNT: ['protected_account', 'Only a site administrator can change an administrator account.'],
  CANNOT_CHANGE_ROLES: ['cannot_change_own_roles', 'You cannot change your own roles.'],
  CANNOT_REMOVE_OWN_ADMIN_ROLE: ['cannot_remove_own_admin_role', 'You cannot remove your own administrator role.'],
  CANNOT_DISABLE_OWN_ACCOUNT: ['cannot_disable_own_account', 'You cannot disable your own account.'],
  CANNOT_DELETE_OWN_ACCOUNT: ['cannot_delete_own_account', 'You cannot delete your own account.'],
  NOTHING_TO_UPDATE: ['nothing_to_update', 'There are no changes to save.'],
  NO_ASSIGNABLE_ROLES: ['no_assignable_roles', 'None of the selected roles can be assigned by you.'],
  NO_USERS_SELECTED: ['no_users_selected', 'Select at least one user.'],
  TOO_MANY_USERS: ['too_many_users_selected', 'Too many users selected.'],
  MAX_API_KEYS_REACHED: ['max_api_keys_reached', 'This user already has the maximum number of API keys.'],
  ALREADY_REVOKED: ['api_key_already_revoked', 'This key is already revoked.'],
  KEY_REVOKED: ['api_key_already_revoked', 'This key is already revoked.'],
  LEGACY_KEY_HAS_NO_EXPIRY: ['legacy_key_no_expiry', 'Legacy keys have no expiry date to extend.'],
  VALIDATION_FAILED: ['validation_failed', 'Please correct the highlighted fields.'],
};

/**
 * @param {any} e axios error or Error
 * @param {(key: string, fallback?: string) => string} t
 * @returns {string}
 */
export function apiErrorMessage(e, t) {
  const status = e?.response?.status;
  const code = e?.response?.data?.message;
  if (code && ERROR_MESSAGES[code]) {
    const [key, fallback] = ERROR_MESSAGES[code];
    return t(key, fallback);
  }
  if (status === 403) return t('ACCESS_DENIED', 'Access denied');
  if (status === 401) return t('session_expired', 'Your session has expired. Sign in again.');
  return t('request_failed', 'Request failed');
}

/**
 * Field errors from a VALIDATION_FAILED response. Values come from server-side form validation and
 * may contain markup, so tags are removed before they are shown as text.
 *
 * @param {any} e
 * @returns {Record<string, string>}
 */
export function apiFieldErrors(e) {
  const errors = e?.response?.data?.errors;
  if (!errors || typeof errors !== 'object') return {};
  const out = {};
  Object.entries(errors).forEach(([field, msg]) => {
    out[field] = String(msg ?? '').replace(/<[^>]*>/g, '').trim();
  });
  return out;
}

/**
 * @param {number|null|undefined} ts unix seconds
 * @param {boolean} [withTime]
 */
export function formatDate(ts, withTime = false) {
  if (!ts) return '';
  const d = new Date(Number(ts) * 1000);
  if (Number.isNaN(d.getTime())) return '';
  return withTime ? d.toLocaleString() : d.toLocaleDateString();
}
