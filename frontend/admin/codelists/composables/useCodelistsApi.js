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

  /**
   * List codelists (collapsed family heads by default).
   * @param {boolean|object} [opts]
   * Legacy: `true`/`false` = with_counts only, full unpaged array.
   * Object: { withCounts, flat, page, per_page, search, status }. When `page` ≥ 1, returns a paginated object.
   * @returns {Promise<Array|{ codelists: array, total: number, page: number, per_page: number }>}
   */
  async function fetchCodelists(opts = true) {
    loading.value = true;
    error.value = null;
    try {
      let withCounts = true;
      let flat = false;
      let page = null;
      let per_page = 50;
      let search = '';
      let status = null;

      if (typeof opts === 'boolean') {
        withCounts = opts;
      } else if (opts && typeof opts === 'object') {
        withCounts = opts.withCounts !== false;
        flat = !!opts.flat;
        if (opts.page != null && opts.page !== '' && Number(opts.page) >= 1) {
          page = opts.page;
        }
        per_page = opts.per_page ?? 50;
        search = typeof opts.search === 'string' ? opts.search : '';
        status = opts.status;
      }

      const params = { with_counts: withCounts ? '1' : '0' };
      if (flat) params.flat = '1';
      else params.collapsed = '1';

      const usePaged = page != null && page !== '' && Number(page) >= 1;
      if (usePaged) {
        params.page = String(Math.max(1, parseInt(String(page), 10) || 1));
        const pp = Math.min(200, Math.max(1, parseInt(String(per_page), 10) || 50));
        params.per_page = String(pp);
        const q = String(search ?? '').trim();
        if (q) params.search = q;
        if (status != null && status !== '' && Number.isFinite(Number(status))) {
          params.status = String(Number(status));
        }
      }

      const { data } = await axios.get(`${base()}`, { params });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
      const res = data.result;
      if (usePaged) {
        return {
          codelists: res?.codelists ?? [],
          total: res?.total ?? 0,
          page: res?.page ?? 1,
          per_page: res?.per_page ?? 50,
        };
      }
      return res?.codelists ?? [];
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /**
   * All version rows for one maintainable (agency + name).
   * @param {string} name
   * @param {string} [agency]
   */
  async function fetchCodelistVersions(name, agency) {
    const n = typeof name === 'string' ? name.trim() : '';
    if (!n) throw new Error('name required');
    const params = {};
    if (agency != null && String(agency).trim() !== '') {
      params.agency = String(agency).trim();
    }
    const { data } = await axios.get(`${base()}/versions/${encodeURIComponent(n)}`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
    return data.result?.codelists ?? [];
  }

  /**
   * One codelist by id. By default includes all items and groups.
   * @param {string|number} id
   * @param {{ includeItems?: boolean, includeGroups?: boolean }} [opts] Set false to omit arrays (item_count / group_count returned).
   */
  async function fetchCodelist(id, { includeItems = true, includeGroups = true } = {}) {
    if (id == null) throw new Error('id required');
    const params = {};
    if (!includeItems) params.include_items = '0';
    if (!includeGroups) params.include_groups = '0';
    const { data } = await axios.get(`${base()}/item/${encodeURIComponent(id)}`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
    return data.result?.codelist ?? null;
  }

  /**
   * Paginated full item rows for a codelist (admin detail table).
   * @returns {Promise<{ items: array, total: number, page: number, per_page: number }>}
   */
  async function fetchItemsPage(codelistId, { page = 1, per_page = 25, search = '' } = {}) {
    if (codelistId == null) throw new Error('codelist id required');
    const params = {
      page: String(Math.max(1, parseInt(String(page), 10) || 1)),
      per_page: String(Math.min(200, Math.max(1, parseInt(String(per_page), 10) || 25))),
    };
    const q = typeof search === 'string' ? search.trim() : '';
    if (q) params.search = q;
    const { data } = await axios.get(`${base()}/item_items/${encodeURIComponent(codelistId)}`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch items');
    const r = data.result ?? {};
    return {
      items: r.items ?? [],
      total: Number(r.total) || 0,
      page: Number(r.page) || 1,
      per_page: Number(r.per_page) || 25,
    };
  }

  /**
   * Paginated groups for a codelist (admin detail table).
   * @returns {Promise<{ groups: array, total: number, page: number, per_page: number }>}
   */
  async function fetchGroupsPage(codelistId, { page = 1, per_page = 25, search = '' } = {}) {
    if (codelistId == null) throw new Error('codelist id required');
    const params = {
      page: String(Math.max(1, parseInt(String(page), 10) || 1)),
      per_page: String(Math.min(200, Math.max(1, parseInt(String(per_page), 10) || 25))),
    };
    const q = typeof search === 'string' ? search.trim() : '';
    if (q) params.search = q;
    const { data } = await axios.get(`${base()}/item_groups/${encodeURIComponent(codelistId)}`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch groups');
    const r = data.result ?? {};
    return {
      groups: r.groups ?? [],
      total: Number(r.total) || 0,
      page: Number(r.page) || 1,
      per_page: Number(r.per_page) || 25,
    };
  }

  /**
   * Fetch a codelist by its single-string idno.
   * @param {string} idno e.g. "NADA_dctypes_1.0"
   */
  async function fetchCodelistByIdno(idno) {
    if (!idno) throw new Error('idno required');
    const { data } = await axios.get(`${base()}/by_idno/${encodeURIComponent(idno)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
    return data.result?.codelist ?? null;
  }

  /**
   * Fetch a codelist by name (+ optional agency / version).
   * Omit version to resolve the latest row for agency + name (server-side).
   */
  async function fetchCodelistByName(name, { agency, version } = {}) {
    if (!name) throw new Error('name required');
    const { data } = await axios.get(`${base()}/by_name/${encodeURIComponent(name)}`, {
      params: { agency, version },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
    return data.result?.codelist ?? null;
  }

  /**
   * Create a codelist.
   * @param {Object} payload
   * @param {string}  payload.name        Required. Short identifier (e.g. 'dctypes').
   * @param {string} [payload.agency]     Defaults to 'NADA' server-side.
   * @param {string} [payload.version]    Defaults to '1.0' server-side.
   * @param {string} [payload.idno]       Optional. Auto-generated as "{agency}_{name}_{version}" if omitted.
   * @param {string} [payload.description]
   */
  async function createCodelist(payload) {
    if (!payload?.name?.trim()) throw new Error('Name is required');
    const { data } = await axios.post(`${base()}`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Create failed');
    return data.result?.codelist;
  }

  /**
   * Update a codelist (draft only). Supported keys: idno, description, status.
   * Passing idno='' (empty string) regenerates it from (agency, name, version).
   */
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
    const { data } = await axios.post(`${base()}/item_delete/${encodeURIComponent(id)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
    return data.result;
  }

  /**
   * Delete many codelists by id. Partial success possible; check result.failed.
   * @param {Array<string|number>} ids
   */
  async function deleteCodelistsBatch(ids) {
    const clean = [
      ...new Set(
        (Array.isArray(ids) ? ids : [])
          .map((id) => Number(id))
          .filter((n) => Number.isInteger(n) && n >= 1)
      ),
    ];
    if (clean.length === 0) {
      throw new Error('At least one codelist id is required');
    }
    const { data } = await axios.post(
      `${base()}/batch_delete`,
      { ids: clean },
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Batch delete failed');
    return data.result ?? {};
  }

  async function restoreCodelist(id) {
    if (id == null) throw new Error('id required');
    const { data } = await axios.post(`${base()}/item/${encodeURIComponent(id)}/restore`, {}, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Restore failed');
    return data.result?.codelist;
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
    const { data } = await axios.post(`${base()}/items_delete/${encodeURIComponent(itemId)}`);
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
    const { data } = await axios.post(
      `${base()}/items_translation_delete/${encodeURIComponent(itemId)}/${encodeURIComponent(lang)}`
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
    const { data } = await axios.post(`${base()}/groups_delete/${encodeURIComponent(groupId)}`);
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
    const { data } = await axios.post(
      `${base()}/groups_items_remove_delete/${encodeURIComponent(groupId)}/${encodeURIComponent(codelistItemId)}`
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
    const { data } = await axios.post(
      `${base()}/groups_translation_delete/${encodeURIComponent(groupId)}/${encodeURIComponent(lang)}`
    );
    if (data.status !== 'success') throw new Error(data.message || 'Delete group translation failed');
    return data.result;
  }

  return {
    loading,
    error,
    fetchCodelists,
    fetchCodelistVersions,
    fetchCodelist,
    fetchItemsPage,
    fetchGroupsPage,
    fetchCodelistByIdno,
    fetchCodelistByName,
    createCodelist,
    updateCodelist,
    deleteCodelist,
    deleteCodelistsBatch,
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
    restoreCodelist,
  };
}
