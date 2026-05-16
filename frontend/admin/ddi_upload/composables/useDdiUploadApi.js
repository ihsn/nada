import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * GET api/admin/catalog/ddi_upload_collections
 */
export function useDdiUploadApi() {
  const { apiBaseUrl } = useAppConfig();

  async function fetchCollectionsForUpload() {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.get(`${base}ddi_upload_collections`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to load collections');
    return data.collections || [];
  }

  async function postImport(formData) {
    const base = apiBaseUrl.value || '';
    const { data } = await axios.post(`${base}import`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    if (data.status !== 'success') {
      const msg = data.message || (data.errors ? JSON.stringify(data.errors) : 'Import failed');
      throw new Error(msg);
    }
    return data;
  }

  return { fetchCollectionsForUpload, postImport };
}
