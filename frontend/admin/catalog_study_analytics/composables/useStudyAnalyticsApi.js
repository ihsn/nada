import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useStudyAnalyticsApi() {
  const { config } = useAppConfig();

  function apiPrefix() {
    const u = config.value?.analyticsApiBase;
    if (u) return String(u).replace(/\/+$/, '') + '/';
    const site = String(config.value?.siteUrl || '').replace(/\/+$/, '');
    return `${site}/api/analytics/`;
  }

  function studyIdParam() {
    const sid = config.value?.studySid;
    if (sid == null) throw new Error('studySid missing');
    return { study_id: sid, limit: 500 };
  }

  async function fetchMonthlyStudies() {
    const { data } = await axios.get(`${apiPrefix()}monthly/studies`, {
      params: studyIdParam(),
      withCredentials: true,
    });
    if (data?.status !== 'success') throw new Error(data?.message || data?.errors || 'LOAD_MONTHLY_FAILED');
    return Array.isArray(data.data) ? data.data : [];
  }

  async function fetchMonthlyFiles() {
    const { data } = await axios.get(`${apiPrefix()}monthly/files`, {
      params: studyIdParam(),
      withCredentials: true,
    });
    if (data?.status !== 'success') throw new Error(data?.message || data?.errors || 'LOAD_FILES_FAILED');
    return Array.isArray(data.data) ? data.data : [];
  }

  return { fetchMonthlyStudies, fetchMonthlyFiles };
}
