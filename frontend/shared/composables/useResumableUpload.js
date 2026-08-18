import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Generic resumable upload client for POST /api/uploads/* (init, chunk, status).
 * Chunks must be sent sequentially; the server appends each chunk to the final file.
 */
export function useResumableUpload() {
  const { config, csrfToken, csrfTokenName } = useAppConfig();

  function uploadsBase() {
    const u = config.value?.uploadsApiUrl;
    if (u) return String(u).replace(/\/+$/, '') + '/';
    const site = String(config.value?.siteUrl || '').replace(/\/+$/, '');
    return `${site}/api/uploads/`;
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

  async function fetchUploadLimits() {
    try {
      const { data } = await axios.get(`${uploadsBase()}limits`, {
        withCredentials: true,
      });
      if (data.status === 'success' && data.limits) return data.limits;
    } catch {
      /* ignore */
    }
    return null;
  }

  /**
   * Poll until the server has marked the upload completed.
   *
   * @param {string} uploadId
   * @param {{ timeoutMs?: number }} [options]
   */
  async function waitUntilCompleted(uploadId, options = {}) {
    const timeoutMs = options.timeoutMs || 15 * 60 * 1000;
    const started = Date.now();
    let delay = 400;

    while (Date.now() - started < timeoutMs) {
      const { data: st } = await axios.get(`${uploadsBase()}status/${uploadId}`, {
        withCredentials: true,
      });
      if (st.status === 'success' && st.upload_status === 'completed') {
        return st;
      }
      await sleep(delay);
      delay = Math.min(Math.round(delay * 1.5), 2000);
    }

    throw new Error('UPLOAD_NOT_COMPLETED');
  }

  /**
   * Upload a file in chunks; resumes from server state on retry.
   * Does not return until GET /status reports upload_status=completed.
   *
   * @param {File} file
   * @param {{ metadata?: object, onProgress?: (ev: { loaded: number, total: number }) => void }} [options]
   * @returns {Promise<{ upload_id: string, filename: string, file_size: number }>}
   */
  async function uploadFile(file, options = {}) {
    if (!file || !file.size) {
      throw new Error('EMPTY_FILE');
    }

    const limits = await fetchUploadLimits();
    const maxFile = limits?.max_file_size ? Number(limits.max_file_size) : 0;
    if (maxFile > 0 && file.size > maxFile) {
      throw new Error('FILE_TOO_LARGE');
    }

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
        metadata: options.metadata || {},
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
      const { data: st } = await axios.get(`${uploadsBase()}status/${uploadId}`, {
        withCredentials: true,
      });
      if (st.status === 'success' && Array.isArray(st.uploaded_chunks)) {
        st.uploaded_chunks.forEach((n) => uploaded.add(Number(n)));
      }
    } catch {
      /* fresh upload */
    }

    let loaded = 0;
    let lastChunkError = null;
    for (let i = 0; i < totalChunks; i++) {
      if (uploaded.has(i)) {
        loaded += i === totalChunks - 1 ? file.size - i * chunkSize : chunkSize;
        if (options.onProgress) {
          options.onProgress({ loaded: Math.min(loaded, file.size), total: file.size });
        }
        continue;
      }
      const start = i * chunkSize;
      const end = Math.min(start + chunkSize, file.size);
      const blob = file.slice(start, end);
      try {
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
      } catch (e) {
        if (i === totalChunks - 1) {
          lastChunkError = e;
          break;
        }
        throw e;
      }
      loaded += blob.size;
      if (options.onProgress) {
        options.onProgress({ loaded: Math.min(loaded, file.size), total: file.size });
      }
    }

    try {
      await waitUntilCompleted(uploadId);
    } catch (e) {
      throw lastChunkError || e;
    }

    return {
      upload_id: uploadId,
      filename: file.name,
      file_size: file.size,
    };
  }

  return {
    uploadsBase,
    fetchUploadLimits,
    waitUntilCompleted,
    uploadFile,
    csrfHeaders,
  };
}
