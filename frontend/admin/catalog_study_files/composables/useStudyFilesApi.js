import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/**
 * Admin catalog study folder files — uses numeric surveys.id via id_format=id.
 * Uploads use POST api/uploads/init + binary chunks + POST …/files/commit (resumable).
 */
export function useStudyFilesApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName, config } = useAppConfig();

  function catalogBase() {
    return String(apiBaseUrl.value || '').replace(/\/+$/, '') + '/';
  }

  function uploadsBase() {
    const u = config.value?.uploadsApiUrl;
    if (u) return String(u).replace(/\/+$/, '') + '/';
    const site = String(config.value?.siteUrl || '').replace(/\/+$/, '');
    return `${site}/api/uploads/`;
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
   */
  async function deleteFile(token) {
    const { data } = await axios.delete(
      `${catalogBase()}${sidPart()}/files/${encodeURIComponent(token)}`,
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
    try {
      const { data } = await axios.get(`${uploadsBase()}limits`, { withCredentials: true });
      if (data.status === 'success' && data.limits) return data.limits;
    } catch {
      /* ignore */
    }
    return null;
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
    if (!file || !file.size) {
      throw new Error('EMPTY_FILE');
    }

    const limits = await fetchUploadLimits();
    const maxChunk = limits?.max_chunk_size ? Number(limits.max_chunk_size) : 5 * 1024 * 1024;
    const chunkSize = Math.max(256 * 1024, Math.min(maxChunk || 5 * 1024 * 1024, 8 * 1024 * 1024));
    const totalChunks = Math.ceil(file.size / chunkSize);

    const { data: initData } = await axios.post(
      `${uploadsBase()}init`,
      {
        filename: file.name,
        total_size: file.size,
        total_chunks: totalChunks,
        chunk_size: chunkSize,
        metadata: { source: 'admin_catalog_study_files' },
      },
      {
        withCredentials: true,
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
      }
    );
    if (initData.status !== 'success' || !initData.upload_id) {
      throw new Error(initData.message || 'INIT_UPLOAD_FAILED');
    }
    const uploadId = initData.upload_id;

    const uploaded = new Set();
    try {
      const { data: st } = await axios.get(`${uploadsBase()}status/${uploadId}`, { withCredentials: true });
      if (st.status === 'success' && Array.isArray(st.uploaded_chunks)) {
        st.uploaded_chunks.forEach((n) => uploaded.add(Number(n)));
      }
    } catch {
      /* fresh upload */
    }

    let loaded = 0;
    for (let i = 0; i < totalChunks; i++) {
      if (uploaded.has(i)) {
        loaded += i === totalChunks - 1 ? file.size - i * chunkSize : chunkSize;
        if (onProgress) onProgress({ loaded: Math.min(loaded, file.size), total: file.size });
        continue;
      }
      const start = i * chunkSize;
      const end = Math.min(start + chunkSize, file.size);
      const blob = file.slice(start, end);
      const { data: ch } = await axios.post(`${uploadsBase()}chunk/${uploadId}`, blob, {
        withCredentials: true,
        headers: {
          'Content-Type': 'application/octet-stream',
          'X-Upload-Chunk-Number': String(i),
          'X-Upload-Chunk-Size': String(blob.size),
          ...csrfHeaders(),
        },
      });
      if (ch.status !== 'success') {
        throw new Error(ch.message || `CHUNK_FAILED_${i}`);
      }
      loaded += blob.size;
      if (onProgress) onProgress({ loaded: Math.min(loaded, file.size), total: file.size });
    }

    return commitResumableUpload(uploadId);
  }

  function downloadHref(token) {
    return `${catalogBase()}${sidPart()}/files/download/${encodeURIComponent(token)}?id_format=id`;
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
