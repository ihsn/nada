import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useDashboardApi() {
  const { siteUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  async function loadStats() {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(`${siteUrl.value}/api/dashboard/stats`);
      if (data && data.data) return data.data;
      throw new Error('Unexpected response format from dashboard API.');
    } catch (e) {
      error.value = e?.response?.statusText || e.message || 'Failed to load dashboard data.';
      return null;
    } finally {
      loading.value = false;
    }
  }

  return { loading, error, loadStats };
}
