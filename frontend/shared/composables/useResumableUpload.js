import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

const CHUNK_TIMEOUT_MS = 120000;
const MAX_CHUNK_ATTEMPTS = 5;
const MAX_INIT_ATTEMPTS = 3;

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function backoffMs(attempt) {
  return Math.min(500 * 2 ** (attempt - 1), 8000);
}

function apiErrorCode(error) {
  return String(error?.response?.data?.error_code || '');
}

function apiErrorMessage(error) {
  return String(error?.response?.data?.message || error?.message || '');
}

function isOutOfOrderError(error) {
  const code = apiErrorCode(error);
  if (code === 'CHUNK_OUT_OF_ORDER') return true;
  return apiErrorMessage(error).includes('CHUNK_OUT_OF_ORDER');
}

function isFatalChunkError(error) {
  const code = apiErrorCode(error);
  const fatal = new Set([
    'FILE_TOO_LARGE',
    'FILE_TYPE_NOT_ALLOWED',
    'UPLOAD_NOT_FOUND',
    'UPLOAD_CANCELLED',
    'CHUNK_OUT_OF_RANGE',
    'INVALID_INPUT',
  ]);
  if (fatal.has(code)) return true;
  const status = error?.response?.status;
  return status === 401 || status === 403;
}

function isRetryableChunkError(error) {
  if (isFatalChunkError(error) || isOutOfOrderError(error)) return false;
  if (error?.code === 'ECONNABORTED' || error?.code === 'ERR_NETWORK') return true;
  if (!error?.response) return true;
  const status = error.response.status;
  if (status === 408 || status === 429 || status >= 500) return true;
  return apiErrorCode(error) === 'CHUNK_SIZE_MISMATCH';
}

function isRetryableInitError(error) {
  if (error?.code === 'ECONNABORTED' || error?.code === 'ERR_NETWORK') return true;
  if (!error?.response) return true;
  const status = error.response.status;
  return status === 408 || status === 429 || status >= 500;
}

function markUploaded(uploaded, chunks) {
  if (!Array.isArray(chunks)) return;
  chunks.forEach((n) => uploaded.add(Number(n)));
}

function loadedBytes(uploaded, totalChunks, chunkSize, fileSize) {
  let loaded = 0;
  uploaded.forEach((n) => {
    loaded += n === totalChunks - 1 ? fileSize - n * chunkSize : chunkSize;
  });
  return Math.min(loaded, fileSize);
}

function nextExpectedChunk(uploaded) {
  let expected = 0;
  while (uploaded.has(expected)) expected += 1;
  return expected;
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

  async function fetchStatus(uploadId) {
    const { data } = await axios.get(`${uploadsBase()}status/${uploadId}`, {
      withCredentials: true,
    });
    return data;
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
      const st = await fetchStatus(uploadId);
      if (st.status === 'success' && st.upload_status === 'completed') {
        return st;
      }
      await sleep(delay);
      delay = Math.min(Math.round(delay * 1.5), 2000);
    }

    throw new Error('UPLOAD_NOT_COMPLETED');
  }

  async function initUpload(file, chunkSize, totalChunks, metadata) {
    let lastErr = null;
    for (let attempt = 1; attempt <= MAX_INIT_ATTEMPTS; attempt++) {
      try {
        const { data } = await axios.post(
          `${uploadsBase()}init`,
          {
            filename: file.name,
            total_size: file.size,
            total_chunks: totalChunks,
            chunk_size: chunkSize,
            metadata: metadata || {},
          },
          {
            withCredentials: true,
            headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
          }
        );
        if (data.status !== 'success' || !data.upload_id) {
          throw new Error(data.message || 'INIT_UPLOAD_FAILED');
        }
        return data.upload_id;
      } catch (e) {
        lastErr = e;
        if (!isRetryableInitError(e) || attempt === MAX_INIT_ATTEMPTS) {
          throw new Error(apiErrorMessage(e) || 'INIT_UPLOAD_FAILED');
        }
        await sleep(backoffMs(attempt));
      }
    }
    throw lastErr || new Error('INIT_UPLOAD_FAILED');
  }

  async function postChunk(uploadId, chunkNumber, blob) {
    const { data } = await axios.post(`${uploadsBase()}chunk/${uploadId}`, blob, {
      withCredentials: true,
      timeout: CHUNK_TIMEOUT_MS,
      headers: {
        'Content-Type': 'application/octet-stream',
        'X-Upload-Chunk-Number': String(chunkNumber),
        'X-Upload-Chunk-Size': String(blob.size),
        ...csrfHeaders(),
      },
    });
    if (data.status !== 'success') {
      const err = new Error(data.message || `CHUNK_FAILED_${chunkNumber}`);
      err.payload = data;
      throw err;
    }
    return data;
  }

  async function syncUploadedFromStatus(uploadId, uploaded) {
    try {
      const st = await fetchStatus(uploadId);
      if (st.status === 'success') {
        markUploaded(uploaded, st.uploaded_chunks);
        return st;
      }
    } catch {
      /* status unavailable */
    }
    return null;
  }

  /**
   * Upload a file in chunks; retries transient chunk failures and can resume
   * an existing upload_id. Does not return until GET /status reports completed.
   *
   * @param {File} file
   * @param {{
   *   metadata?: object,
   *   uploadId?: string,
   *   onSession?: (ev: { upload_id: string }) => void,
   *   onProgress?: (ev: {
   *     loaded: number,
   *     total: number,
   *     chunkIndex?: number,
   *     totalChunks?: number,
   *     attempt?: number,
   *     maxAttempts?: number
   *   }) => void
   * }} [options]
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

    const uploaded = new Set();
    let uploadId = options.uploadId || null;

    if (uploadId) {
      const st = await syncUploadedFromStatus(uploadId, uploaded);
      if (!st || st.upload_status === 'cancelled' || Number(st.total_size) !== file.size || Number(st.total_chunks) !== totalChunks) {
        uploaded.clear();
        uploadId = null;
      } else if (st.upload_status === 'completed') {
        if (options.onSession) options.onSession({ upload_id: uploadId });
        return {
          upload_id: uploadId,
          filename: file.name,
          file_size: file.size,
        };
      }
    }

    if (!uploadId) {
      uploadId = await initUpload(file, chunkSize, totalChunks, options.metadata);
      uploaded.clear();
    }

    if (options.onSession) {
      options.onSession({ upload_id: uploadId });
    }

    const emitProgress = (extra = {}) => {
      if (!options.onProgress) return;
      options.onProgress({
        loaded: loadedBytes(uploaded, totalChunks, chunkSize, file.size),
        total: file.size,
        totalChunks,
        maxAttempts: MAX_CHUNK_ATTEMPTS,
        ...extra,
      });
    };

    emitProgress();

    let lastChunkError = null;
    for (let i = 0; i < totalChunks; i++) {
      if (uploaded.has(i)) {
        emitProgress({ chunkIndex: i, attempt: 1 });
        continue;
      }

      const start = i * chunkSize;
      const end = Math.min(start + chunkSize, file.size);
      const blob = file.slice(start, end);
      let chunkOk = false;

      for (let attempt = 1; attempt <= MAX_CHUNK_ATTEMPTS; attempt++) {
        emitProgress({ chunkIndex: i, attempt });
        try {
          const ch = await postChunk(uploadId, i, blob);
          markUploaded(uploaded, ch.uploaded_chunks);
          uploaded.add(i);
          chunkOk = true;
          break;
        } catch (e) {
          const st = await syncUploadedFromStatus(uploadId, uploaded);
          if (st?.upload_status === 'completed' || uploaded.has(i) || nextExpectedChunk(uploaded) > i) {
            chunkOk = true;
            break;
          }
          if (isOutOfOrderError(e) || isFatalChunkError(e) || !isRetryableChunkError(e)) {
            e.uploadId = uploadId;
            throw e;
          }
          lastChunkError = e;
          if (attempt === MAX_CHUNK_ATTEMPTS) {
            if (i === totalChunks - 1) {
              break;
            }
            e.uploadId = uploadId;
            throw e;
          }
          await sleep(backoffMs(attempt));
        }
      }

      if (!chunkOk) {
        if (i === totalChunks - 1) {
          break;
        }
        const err = lastChunkError || new Error(`CHUNK_FAILED_${i}`);
        err.uploadId = uploadId;
        throw err;
      }

      emitProgress({ chunkIndex: i, attempt: 1 });
    }

    try {
      await waitUntilCompleted(uploadId);
    } catch (e) {
      const err = lastChunkError || e;
      err.uploadId = uploadId;
      throw err;
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
