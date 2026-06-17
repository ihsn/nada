import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useMenuApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error   = ref(null);

  function base() {
    return apiBaseUrl.value || '';
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

  async function deleteMenu(id) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.post(`${base()}menu_delete/${id}`);
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
        { headers: { 'Content-Type': 'application/json' } }
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
          headers: { 'Content-Type': 'application/json' },
        }
      );
      if (data.status !== 'success') throw new Error(data.message || 'Reorder failed');
      return true;
    } catch (e) {
      error.value = e;
      throw e;
    }
  }

  return {
    loading, error,
    listMenus, deleteMenu, publishMenu, reorderMenus,
  };
}
