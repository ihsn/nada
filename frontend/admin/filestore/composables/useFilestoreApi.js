import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** Admin file manager against api/admin/filestore. */
export function useFilestoreApi() {
  const { apiBaseUrl, config, csrfToken, csrfTokenName } = useAppConfig();

  function base() {
    return String(apiBaseUrl.value || '').replace(/\/$/, '');
  }

  function csrfHeaders() {
    const tok = csrfToken.value || '';
    return tok ? { [csrfTokenName.value || 'ncsrf']: tok } : {};
  }

  function apiError(e, fallback) {
    return e?.response?.data?.message || e?.message || fallback;
  }

  /** Public URL of a stored file: {filesBaseUrl}/{file_path}/{file_name}. */
  function fileUrl(file) {
    const root = String(config.value?.filesBaseUrl || '').replace(/\/$/, '');
    const dir = String(file?.file_path || '').replace(/^\/+|\/+$/g, '');
    const name = encodeURIComponent(file?.file_name || '');
    return dir ? `${root}/${dir}/${name}` : `${root}/${name}`;
  }

  async function search(params) {
    try {
      const { data } = await axios.get(base(), { params });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to load files');
      return { files: data.files || [], total: Number(data.total) || 0 };
    } catch (e) {
      throw new Error(apiError(e, 'Failed to load files'), { cause: e });
    }
  }

  async function getFile(fileName) {
    try {
      const { data } = await axios.get(`${base()}/${encodeURIComponent(fileName)}`);
      if (data.status !== 'success') throw new Error(data.message || 'File not found');
      return data.file;
    } catch (e) {
      throw new Error(apiError(e, 'File not found'), { cause: e });
    }
  }

  async function upload(file, { overwrite = false, onProgress } = {}) {
    const form = new FormData();
    form.append('file', file);
    if (overwrite) form.append('overwrite', 'yes');
    try {
      const { data } = await axios.post(base(), form, {
        headers: csrfHeaders(),
        onUploadProgress: (ev) => {
          if (onProgress && ev.total) onProgress(Math.round((ev.loaded * 100) / ev.total));
        },
      });
      if (data.status !== 'success') throw new Error(data.message || 'Upload failed');
      return data.result;
    } catch (e) {
      throw new Error(cleanUploadError(apiError(e, 'Upload failed')), { cause: e });
    }
  }

  async function remove(fileName) {
    try {
      const { data } = await axios.post(`${base()}/delete/${encodeURIComponent(fileName)}`, null, {
        headers: csrfHeaders(),
      });
      if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
      return true;
    } catch (e) {
      throw new Error(apiError(e, 'Delete failed'), { cause: e });
    }
  }

  return { fileUrl, search, getFile, upload, remove };
}

/** Upload errors look like "UPLOAD_FAILED::<server path> - error:: <p>message</p>"; keep only the message. */
function cleanUploadError(message) {
  let msg = String(message || '').replace(/<[^>]*>/g, '');
  const m = msg.match(/error::\s*(.+)/i);
  if (m) msg = m[1];
  msg = msg.trim();
  if (msg === 'FILE_ALREADY_EXISTS') return 'A file with this name already exists. Tick "Overwrite" to replace it.';
  return msg || 'Upload failed';
}
