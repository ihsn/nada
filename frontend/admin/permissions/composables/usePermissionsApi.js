import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function usePermissionsApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  function base() {
    return String(apiBaseUrl.value || '').replace(/\/?$/, '/');
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    return tok ? { [name]: tok } : {};
  }

  function apiError(e, fallback) {
    return e?.response?.data?.message || e?.message || fallback;
  }

  async function listResources() {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${base()}resources`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load resources');
      return data.resources || [];
    } catch (e) {
      error.value = e;
      throw new Error(apiError(e, 'Failed to load resources'));
    } finally {
      loading.value = false;
    }
  }

  async function listRoles() {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${base()}roles`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load roles');
      return data.roles || [];
    } catch (e) {
      error.value = e;
      throw new Error(apiError(e, 'Failed to load roles'));
    } finally {
      loading.value = false;
    }
  }

  async function getRole(id) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${base()}roles/${encodeURIComponent(String(id))}`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load role');
      return data.role;
    } catch (e) {
      error.value = e;
      throw new Error(apiError(e, 'Failed to load role'));
    } finally {
      loading.value = false;
    }
  }

  async function createRole(payload) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.post(`${base()}roles`, payload, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Create failed');
      return data.role;
    } catch (e) {
      error.value = e;
      throw new Error(apiError(e, 'Create failed'));
    } finally {
      loading.value = false;
    }
  }

  async function updateRole(id, payload) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.post(
        `${base()}roles/${encodeURIComponent(String(id))}`,
        payload,
        { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Update failed');
      return data.role;
    } catch (e) {
      error.value = e;
      throw new Error(apiError(e, 'Update failed'));
    } finally {
      loading.value = false;
    }
  }

  async function deleteRole(id) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.post(
        `${base()}roles/${encodeURIComponent(String(id))}/delete`,
        null,
        { headers: { ...csrfHeaders() } }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
      return true;
    } catch (e) {
      error.value = e;
      throw new Error(apiError(e, 'Delete failed'));
    } finally {
      loading.value = false;
    }
  }

  async function getRolePermissions(id) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(
        `${base()}roles/${encodeURIComponent(String(id))}/permissions`
      );
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load permissions');
      return {
        role: data.role,
        resources: data.resources && typeof data.resources === 'object' ? data.resources : {},
      };
    } catch (e) {
      error.value = e;
      throw new Error(apiError(e, 'Failed to load permissions'));
    } finally {
      loading.value = false;
    }
  }

  async function saveRolePermissions(id, resources) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.post(
        `${base()}roles/${encodeURIComponent(String(id))}/permissions`,
        { resources },
        { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Save failed');
      return {
        role: data.role,
        resources: data.resources && typeof data.resources === 'object' ? data.resources : {},
      };
    } catch (e) {
      error.value = e;
      throw new Error(apiError(e, 'Save failed'));
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    error,
    listResources,
    listRoles,
    getRole,
    createRole,
    updateRole,
    deleteRole,
    getRolePermissions,
    saveRolePermissions,
  };
}
