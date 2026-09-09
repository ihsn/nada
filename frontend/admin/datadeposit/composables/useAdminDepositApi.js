import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useAdminDepositApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    return tok ? { [name]: tok } : {};
  }

  /**
   * @param {Record<string, string|number|undefined>} params
   */
  async function searchProjects(params) {
    loading.value = true;
    error.value = null;
    try {
      const base = apiBaseUrl.value || '';
      const { data } = await axios.get(base, { params });
      if (data.status !== 'success') {
        throw new Error(data.message || 'Search failed');
      }
      return data.result;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /**
   * @param {number|string} id
   */
  async function fetchProject(id) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.get(`${base}projects/${encodeURIComponent(String(id))}`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Not found');
    }
    return data.result;
  }

  /**
   * @param {number|string} id
   * @param {string} format
   */
  function exportUrl(id, format) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const pid = encodeURIComponent(String(id || ''));
    const fmt = String(format || '').replace(/[^a-z0-9_]/gi, '');
    return base && pid && fmt ? `${base}projects/${pid}/export/${fmt}` : '';
  }

  /**
   * @param {number|string} id
   * @param {number|string} fileId
   */
  function fileDownloadUrl(id, fileId) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const pid = encodeURIComponent(String(id || ''));
    const fid = encodeURIComponent(String(fileId || ''));
    return base && pid && fid ? `${base}projects/${pid}/files/${fid}/download` : '';
  }

  /**
   * @param {number|string} id
   * @param {{ status: string, catalog_study_id?: string, admin_comments?: string, notify?: boolean }} payload
   */
  async function processProject(id, payload) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.post(
      `${base}projects/${encodeURIComponent(String(id))}/process`,
      payload || {},
      { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
    );
    if (data.status !== 'success') {
      throw new Error(data.message || 'Update failed');
    }
    return data;
  }

  /**
   * @param {number|string} id
   * @param {{ to: string, cc?: string, subject: string, body: string }} payload
   */
  /**
   * @param {number|string} id
   */
  async function fetchProjectHistory(id) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.get(`${base}projects/${encodeURIComponent(String(id))}/history`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'History failed');
    }
    return data.result;
  }

  async function sendProjectEmail(id, payload) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.post(
      `${base}projects/${encodeURIComponent(String(id))}/communicate`,
      payload || {},
      { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
    );
    if (data.status !== 'success') {
      throw new Error(data.message || 'Send failed');
    }
    return data;
  }

  /**
   * @param {Array<number|string>} ids
   */
  async function deleteProjects(ids) {
    const list = (Array.isArray(ids) ? ids : [ids]).map((id) => Number(id)).filter((id) => id > 0);
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.post(
      `${base}delete`,
      { project: list },
      { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
    );
    if (data.status !== 'success') {
      throw new Error(data.message || 'Delete failed');
    }
    return data;
  }

  /**
   * @param {number|string} id
   */
  async function fetchAssign(id) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.get(`${base}projects/${encodeURIComponent(String(id))}/assign`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Not found');
    }
    return data.result;
  }

  /**
   * @param {number|string} id
   * @param {number|string} userId
   */
  async function assignTask(id, userId) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.post(
      `${base}projects/${encodeURIComponent(String(id))}/assign`,
      { user_id: userId },
      { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
    );
    if (data.status !== 'success') {
      throw new Error(data.message || 'Assign failed');
    }
    return data;
  }

  async function fetchTasks() {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.get(`${base}tasks`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Tasks failed');
    }
    return data.result;
  }

  async function fetchMyTasks() {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.get(`${base}tasks/my`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Tasks failed');
    }
    return data.result;
  }

  /**
   * @param {number|string} id
   */
  async function fetchTask(id) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.get(`${base}tasks/${encodeURIComponent(String(id))}`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Not found');
    }
    return data.result;
  }

  /**
   * @param {number|string} id
   * @param {0|1} status
   */
  async function updateTaskStatus(id, status) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.post(
      `${base}tasks/${encodeURIComponent(String(id))}/update`,
      { status },
      { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
    );
    if (data.status !== 'success') {
      throw new Error(data.message || 'Update failed');
    }
    return data.result;
  }

  /**
   * @param {number|string} id
   */
  async function deleteTask(id) {
    const base = String(apiBaseUrl.value || '').replace(/\/?$/, '/');
    const { data } = await axios.post(
      `${base}tasks/${encodeURIComponent(String(id))}/delete`,
      {},
      { headers: { 'Content-Type': 'application/json', ...csrfHeaders() } }
    );
    if (data.status !== 'success') {
      throw new Error(data.message || 'Delete failed');
    }
    return data;
  }

  return {
    loading,
    error,
    searchProjects,
    fetchProject,
    exportUrl,
    fileDownloadUrl,
    processProject,
    sendProjectEmail,
    fetchProjectHistory,
    deleteProjects,
    fetchAssign,
    assignTask,
    fetchTasks,
    fetchMyTasks,
    fetchTask,
    updateTaskStatus,
    deleteTask,
  };
}
