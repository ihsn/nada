import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useResumableUpload } from '@/shared/composables/useResumableUpload';

/**
 * Admin catalog study upload / import.
 * GET api/admin/catalog/ddi_upload_collections
 * POST api/admin/catalog/import — one-shot (XML, JSON, JSONL, small ZIP)
 * Package ZIP: api/uploads (chunked) + api/admin/catalog/import_package/*
 */
export function useDdiUploadApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName } = useAppConfig();
  const resumable = useResumableUpload();

  function catalogBase() {
    return String(apiBaseUrl.value || '').replace(/\/+$/, '') + '/';
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

  async function fetchCollectionsForUpload() {
    const { data } = await axios.get(`${catalogBase()}ddi_upload_collections`, { withCredentials: true });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to load collections');
    return data.collections || [];
  }

  async function postImport(formData) {
    const { data } = await axios.post(`${catalogBase()}import`, formData, {
      headers: { 'Content-Type': 'multipart/form-data', ...csrfHeaders() },
      withCredentials: true,
    });
    if (data.status !== 'success') {
      const error = new Error(data.message || 'Import failed');
      error.importResponse = data;
      throw error;
    }
    return data;
  }

  /**
   * Resumable upload for package ZIP; returns upload_id when complete.
   */
  async function uploadPackageZip(file, onProgress) {
    const completed = await resumable.uploadFile(file, {
      metadata: {
        purpose: 'package_import',
        allowed_types: 'zip',
        source: 'admin_ddi_upload',
      },
      onProgress: onProgress
        ? (ev) => onProgress({ phase: 'upload', loaded: ev.loaded, total: ev.total })
        : undefined,
    });
    return completed.upload_id;
  }

  async function packageImportRequest(path, body) {
    const { data } = await axios.post(`${catalogBase()}import_package/${path}`, body, {
      withCredentials: true,
      headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
    });
    if (data.status !== 'success') {
      const error = new Error(data.message || 'Package import failed');
      error.importResponse = data;
      throw error;
    }
    return data;
  }

  async function fetchPackageImportStatus(uploadId) {
    const { data } = await axios.get(`${catalogBase()}import_package/status`, {
      params: { upload_id: uploadId },
      withCredentials: true,
    });
    if (data.status !== 'success') {
      throw new Error(data.message || 'STATUS_FAILED');
    }
    return data;
  }

  /**
   * Run staged package import tasks until complete.
   */
  async function runPackageImport(uploadId, importOptions, onProgress) {
    let status = await fetchPackageImportStatus(uploadId).catch(() => ({
      next_task: 'unzip',
      done: false,
      data_files_pending: [],
      data_files_total: 0,
      data_files_done: 0,
    }));

    const runTask = async (task) => {
      if (task === 'unzip') {
        status = await packageImportRequest('unzip', { upload_id: uploadId });
      } else if (task === 'create') {
        status = await packageImportRequest('create', {
          upload_id: uploadId,
          repositoryid: importOptions.repositoryid,
          overwrite: importOptions.overwrite ? 'yes' : 'no',
        });
      } else if (task === 'datafile') {
        status = await packageImportRequest('datafile', { upload_id: uploadId });
      } else if (task === 'finalize') {
        status = await packageImportRequest('finalize', { upload_id: uploadId });
      }
      if (onProgress) {
        onProgress({
          phase: status.phase || task,
          next_task: status.next_task,
          data_files_done: status.data_files_done,
          data_files_total: status.data_files_total,
          data_files_pending: status.data_files_pending,
          done: status.done,
        });
      }
    };

    while (!status.done) {
      const task = status.next_task;
      if (!task) break;
      await runTask(task);
      if (status.done) break;
    }

    if (!status.done || !status.sid) {
      throw new Error('PACKAGE_IMPORT_INCOMPLETE');
    }
    return status;
  }

  return {
    fetchCollectionsForUpload,
    postImport,
    uploadPackageZip,
    runPackageImport,
    fetchPackageImportStatus,
  };
}
