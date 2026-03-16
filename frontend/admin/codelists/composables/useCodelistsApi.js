import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Composable for admin codelists API.
 */
export function useCodelistsApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  const base = () => (apiBaseUrl.value || '').replace(/\/$/, '');

  async function fetchCodelists(withCounts = true) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${base()}`, {
        params: { with_counts: withCounts ? '1' : '0' },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
      return data.result?.codelists ?? [];
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function fetchCodelist(id) {
    if (id == null) throw new Error('id required');
    const { data } = await axios.get(`${base()}/item/${encodeURIComponent(id)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
    return data.result?.codelist ?? null;
  }

  async function createCodelist(payload) {
    if (!payload?.name?.trim()) throw new Error('Name is required');
    const { data } = await axios.post(`${base()}`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Create failed');
    return data.result?.codelist;
  }

  async function updateCodelist(id, payload) {
    if (id == null) throw new Error('id required');
    const { data } = await axios.put(`${base()}/item/${encodeURIComponent(id)}`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Update failed');
    return data.result?.codelist;
  }

  async function deleteCodelist(id) {
    if (id == null) throw new Error('id required');
    const { data } = await axios.delete(`${base()}/item/${encodeURIComponent(id)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
    return data.result;
  }

  async function fetchItems(codelistId) {
    if (codelistId == null) return [];
    const { data } = await axios.get(`${base()}/item_items/${encodeURIComponent(codelistId)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch items');
    return data.result?.items ?? [];
  }

  async function createItem(codelistId, payload) {
    if (codelistId == null || !payload?.code?.trim()) throw new Error('Codelist id and code required');
    const { data } = await axios.post(
      `${base()}/item_items/${encodeURIComponent(codelistId)}`,
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Create item failed');
    return data.result?.item;
  }

  async function updateItem(itemId, payload) {
    if (itemId == null) throw new Error('item id required');
    const { data } = await axios.put(
      `${base()}/items/${encodeURIComponent(itemId)}`,
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Update item failed');
    return data.result?.item;
  }

  async function deleteItem(itemId) {
    if (itemId == null) throw new Error('item id required');
    const { data } = await axios.delete(`${base()}/items/${encodeURIComponent(itemId)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Delete item failed');
    return data.result;
  }

  async function saveItemTranslation(itemId, lang, title) {
    if (itemId == null || !lang?.trim()) throw new Error('item id and lang required');
    const { data } = await axios.post(
      `${base()}/items_translations/${encodeURIComponent(itemId)}`,
      { lang: lang.trim(), title: String(title ?? '').trim() },
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Save translation failed');
    return data.result?.translations ?? {};
  }

  async function deleteItemTranslation(itemId, lang) {
    if (itemId == null || !lang) throw new Error('item id and lang required');
    const { data } = await axios.delete(
      `${base()}/items_translation/${encodeURIComponent(itemId)}/${encodeURIComponent(lang)}`
    );
    if (data.status !== 'success') throw new Error(data.message || 'Delete translation failed');
    return data.result;
  }

  async function fetchGroups(codelistId) {
    if (codelistId == null) return [];
    const { data } = await axios.get(`${base()}/item_groups/${encodeURIComponent(codelistId)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch groups');
    return data.result?.groups ?? [];
  }

  async function createGroup(codelistId, payload) {
    if (codelistId == null || !payload?.name?.trim()) throw new Error('Codelist id and name required');
    const { data } = await axios.post(
      `${base()}/item_groups/${encodeURIComponent(codelistId)}`,
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Create group failed');
    return data.result?.group;
  }

  async function updateGroup(groupId, payload) {
    if (groupId == null) throw new Error('group id required');
    const { data } = await axios.put(
      `${base()}/groups/${encodeURIComponent(groupId)}`,
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Update group failed');
    return data.result?.group;
  }

  async function deleteGroup(groupId) {
    if (groupId == null) throw new Error('group id required');
    const { data } = await axios.delete(`${base()}/groups/${encodeURIComponent(groupId)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Delete group failed');
    return data.result;
  }

  async function addGroupItem(groupId, codelistItemId, sortOrder = 0) {
    if (groupId == null || codelistItemId == null) throw new Error('group id and item id required');
    const { data } = await axios.post(
      `${base()}/groups_items/${encodeURIComponent(groupId)}`,
      { codelist_item_id: codelistItemId, sort_order: sortOrder },
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Add item to group failed');
    return data.result?.group;
  }

  async function removeGroupItem(groupId, codelistItemId) {
    if (groupId == null || codelistItemId == null) throw new Error('group id and item id required');
    const { data } = await axios.delete(
      `${base()}/groups_items_remove/${encodeURIComponent(groupId)}/${encodeURIComponent(codelistItemId)}`
    );
    if (data.status !== 'success') throw new Error(data.message || 'Remove item from group failed');
    return data.result;
  }

  async function saveGroupTranslation(groupId, lang, title) {
    if (groupId == null || !lang?.trim()) throw new Error('group id and lang required');
    const { data } = await axios.post(
      `${base()}/groups_translations/${encodeURIComponent(groupId)}`,
      { lang: lang.trim(), title: String(title ?? '').trim() },
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Save group translation failed');
    return data.result?.translations ?? {};
  }

  async function deleteGroupTranslation(groupId, lang) {
    if (groupId == null || !lang) throw new Error('group id and lang required');
    const { data } = await axios.delete(
      `${base()}/groups_translation/${encodeURIComponent(groupId)}/${encodeURIComponent(lang)}`
    );
    if (data.status !== 'success') throw new Error(data.message || 'Delete group translation failed');
    return data.result;
  }

  return {
    loading,
    error,
    fetchCodelists,
    fetchCodelist,
    createCodelist,
    updateCodelist,
    deleteCodelist,
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
    saveItemTranslation,
    deleteItemTranslation,
    fetchGroups,
    createGroup,
    updateGroup,
    deleteGroup,
    addGroupItem,
    removeGroupItem,
    saveGroupTranslation,
    deleteGroupTranslation,
  };
}
