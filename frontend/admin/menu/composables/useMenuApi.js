import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useMenuApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName } = useAppConfig();
  const loading = ref(false);
  const error   = ref(null);

  function base() {
    return apiBaseUrl.value || '';
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    return tok ? { [name]: tok } : {};
  }

  async function listMenus() {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.get(base());
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load menus');
      return data.menus || [];
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function getMenu(id) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.get(`${base()}item/${id}`);
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load menu item');
      return data.item;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function listParents(excludeId = null) {
    const params = {};
    if (excludeId != null && excludeId !== '') params.exclude_id = excludeId;
    const { data } = await axios.get(`${base()}parents`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to load parents');
    return data.parents || [];
  }

  async function createMenu(payload) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.post(base(), payload, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Create failed');
      return data.item;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function updateMenu(id, payload) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.post(`${base()}item/${id}`, payload, {
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Update failed');
      return data.item;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteMenu(id) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.post(`${base()}menu_delete/${id}`, null, {
        headers: { ...csrfHeaders() },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
      return true;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function publishMenu(id, published) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.post(
        `${base()}publish/${id}`,
        { published },
        { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Publish update failed');
      return true;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function reorderMenus(items) {
    error.value = null;
    try {
      const { data } = await axios.post(
        `${base()}reorder`,
        items,
        {
          withCredentials: true,
          headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
        }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Reorder failed');
      return true;
    } catch (e) {
      error.value = e;
      throw e;
    }
  }

  async function batchDeleteMenus(ids) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.post(
        `${base()}batch_delete`,
        { ids },
        { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Batch delete failed');
      return data;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function batchPublishMenus(ids, published) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.post(
        `${base()}batch_publish`,
        { ids, published },
        { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Batch publish failed');
      return data;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  return {
    loading, error,
    listMenus, getMenu, listParents, createMenu, updateMenu,
    deleteMenu, publishMenu, reorderMenus,
    batchDeleteMenus, batchPublishMenus,
  };
}
