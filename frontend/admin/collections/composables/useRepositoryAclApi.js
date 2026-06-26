import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Per-collection repositories_acl (study_* / licensed_request_*) for admin collections Vue.
 */
export function useRepositoryAclApi() {
  const { apiBaseUrl } = useAppConfig();

  function base() {
    return apiBaseUrl.value || '';
  }

  /**
   * @param {number|string} repositoryPk repositories.id (0 = central)
   * @param {string} [userQ] optional search (min 2 chars server-side)
   */
  async function fetchRepositoryAcl(repositoryPk, userQ = '') {
    const params = {};
    const q = (userQ || '').trim();
    if (q.length >= 2) {
      params.user_q = q;
    }
    const { data } = await axios.get(`${base()}repository_acl/${encodeURIComponent(repositoryPk)}`, { params });
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to load permissions');
    }
    return data;
  }

  /**
   * @param {number|string} repositoryPk
   * @param {number} userId
   * @param {string[]} permissions
   */
  async function saveUserRepositoryAcl(repositoryPk, userId, permissions) {
    const { data } = await axios.post(
      `${base()}repository_acl/${encodeURIComponent(repositoryPk)}`,
      { user_id: userId, permissions },
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to save');
    }
    return data;
  }

  return { fetchRepositoryAcl, saveUserRepositoryAcl };
}
