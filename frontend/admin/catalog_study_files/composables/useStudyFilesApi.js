import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useResumableUpload } from '@/shared/composables/useResumableUpload';

/**
 * Admin catalog study folder files — uses numeric surveys.id via id_format=id.
 * Uploads use POST api/uploads/init + binary chunks + POST …/files/commit (resumable).
 */
export function useStudyFilesApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName, config } = useAppConfig();
  const resumable = useResumableUpload();

  function catalogBase() {
    return String(apiBaseUrl.value || '').replace(/\/+$/, '') + '/';
  }

  function uploadsBase() {
    return resumable.uploadsBase();
  }

  function sidPart() {
    const s = config.value?.studySid;
    if (s == null || s === '') throw new Error('studySid missing');
    return encodeURIComponent(String(s));
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    if (!tok) return {};
    return {
      [name]: tok,
      'X-CSRF-TOKEN': tok,
      'X-Requested-With': 'XMLHttpRequest',
    };
  }

  async function fetchFiles() {
    const { data } = await axios.get(`${catalogBase()}${sidPart()}/files`, {
      params: { id_format: 'id' },
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_FILES_FAILED');
    return data;
  }

  /**
   * @param {string} token — row.base64 from list API
   * POST …/files/{token}/delete: same as DELETE …/files/{token} when DELETE is blocked.
   */
  async function deleteFile(token) {
    const { data } = await axios.post(
      `${catalogBase()}${sidPart()}/files/${encodeURIComponent(token)}/delete`,
      {},
      {
        params: { id_format: 'id' },
        withCredentials: true,
        headers: csrfHeaders(),
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'DELETE_FAILED');
    return data;
  }

  /**
   * Optional: server max chunk / max file (api/uploads/limits).
   */
  async function fetchUploadLimits() {
    return resumable.fetchUploadLimits();
  }

  /**
   * Commit a completed resumable upload into the study folder.
   * @param {string} uploadId
   */
  async function commitResumableUpload(uploadId) {
    const { data } = await axios.post(
      `${catalogBase()}${sidPart()}/files/commit`,
      { upload_id: uploadId },
      {
        params: { id_format: 'id' },
        withCredentials: true,
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      }
    );
    if (data.status !== 'success') throw new Error(data.message || 'COMMIT_FAILED');
    return data;
  }

  /**
   * Upload one file via api/uploads (chunked, resumable) then catalog commit.
   * @param {File} file
   * @param {(ev: { loaded: number, total: number }) => void} [onProgress]
   */
  async function uploadFile(file, onProgress) {
    const completed = await resumable.uploadFile(file, {
      metadata: { source: 'admin_catalog_study_files' },
      onProgress,
    });
    return commitResumableUpload(completed.upload_id);
  }

  function downloadHref(token) {
    const params = new URLSearchParams({ id_format: 'id', t: token });
    return `${catalogBase()}${sidPart()}/files/download?${params.toString()}`;
  }

  return {
    fetchFiles,
    deleteFile,
    uploadFile,
    commitResumableUpload,
    fetchUploadLimits,
    downloadHref,
    sidPart,
    catalogBase,
    uploadsBase,
  };
}
