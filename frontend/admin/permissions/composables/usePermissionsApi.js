import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';

const ERROR_MESSAGES = {
  'SESSION-REQUIRED': ['session_expired', 'Your session has expired. Sign in again.'],
  'CSRF-TOKEN-INVALID': ['csrf_token_invalid', 'The page is out of date. Reload it and try again.'],
  ACCESS_DENIED: ['ACCESS_DENIED', 'Access denied'],
  NOT_FOUND: ['role_not_found', 'Role not found.'],
  ROLE_LOCKED: ['system_role_locked', 'System roles cannot be edited.'],
  ROLE_REQUIRED: ['role_name_required', 'Enter a role name.'],
  INVALID_ROLE_NAME: ['role_name_invalid', 'Role names can only contain letters, numbers and spaces (up to 100 characters).'],
  ROLE_EXISTS: ['role_exists', 'A role with this name already exists.'],
  DESCRIPTION_TOO_LONG: ['role_description_too_long', 'The description can be up to 255 characters.'],
  INVALID_DESCRIPTION: ['role_description_invalid', 'The description cannot contain HTML.'],
  INVALID_WEIGHT: ['role_weight_invalid', 'Weight must be a whole number from 0 to 999.'],
  INVALID_RESOURCE: ['permissions_invalid', 'Some permissions are not recognised. Reload the page and try again.'],
  INVALID_PERMISSION: ['permissions_invalid', 'Some permissions are not recognised. Reload the page and try again.'],
  NOTHING_TO_UPDATE: ['nothing_to_update', 'There are no changes to save.'],
  SERVER_ERROR: ['server_error', 'Something went wrong. Try again.'],
};

export function usePermissionsApi() {
  const { apiBaseUrl, csrfToken } = useAppConfig();
  const { t } = useI18n();

  function url(path) {
    return String(apiBaseUrl.value || '').replace(/\/?$/, '/') + path;
  }

  function toError(e, fallback) {
    const code = e?.response?.data?.message || e?.message || '';
    const known = ERROR_MESSAGES[code];
    const err = new Error(known ? t(known[0], known[1]) : fallback);
    err.code = code;
    err.status = e?.response?.status || 0;
    return err;
  }

  async function get(path, fallback) {
    try {
      const { data } = await axios.get(url(path));
      if (data.status !== 'success') throw new Error(data.message || '');
      return data;
    } catch (e) {
      throw toError(e, fallback);
    }
  }

  async function post(path, body, fallback) {
    try {
      const { data } = await axios.post(url(path), body ?? {}, {
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.value || '' },
      });
      if (data.status !== 'success') throw new Error(data.message || '');
      return data;
    } catch (e) {
      throw toError(e, fallback);
    }
  }

  const roleUrl = (id) => `roles/${encodeURIComponent(String(id))}`;
  const resourcesOf = (data) =>
    data.resources && typeof data.resources === 'object' && !Array.isArray(data.resources) ? data.resources : {};

  return {
    async listResources() {
      return (await get('resources', 'Failed to load resources')).resources || [];
    },
    async listRoles() {
      return (await get('roles', 'Failed to load roles')).roles || [];
    },
    async getRole(id) {
      return (await get(roleUrl(id), 'Failed to load role')).role;
    },
    async createRole(payload) {
      return (await post('roles', payload, 'Create failed')).role;
    },
    /** Partial update: any of role, description, weight, resources (replaces all grants). */
    async saveRole(id, payload) {
      const data = await post(roleUrl(id), payload, 'Save failed');
      return { role: data.role, resources: resourcesOf(data) };
    },
    async deleteRole(id) {
      await post(`${roleUrl(id)}/delete`, {}, 'Delete failed');
      return true;
    },
    async getRolePermissions(id) {
      const data = await get(`${roleUrl(id)}/permissions`, 'Failed to load permissions');
      return { role: data.role, resources: resourcesOf(data) };
    },
  };
}
