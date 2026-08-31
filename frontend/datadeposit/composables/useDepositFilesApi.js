import axios from 'axios';
import { useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useResumableUpload } from '@/shared/composables/useResumableUpload';
import { extractApiError } from './apiErrors';

export function useDepositFilesApi() {
  const { config } = useAppConfig();
  const route = useRoute();
  const resumable = useResumableUpload();

  function throwApiError(errOrData, fallbackMessage) {
    const extracted = extractApiError(
      errOrData?.isAxiosError || errOrData?.response
        ? errOrData
        : { responseData: errOrData, message: errOrData?.message || fallbackMessage }
    );
    const err = new Error(extracted.message || fallbackMessage);
    err.errors = extracted.errors;
    err.responseData = extracted.raw;
    throw err;
  }

  function filesUrl() {
    const apiRoot = String(config.value?.projectsApiUrl || '').replace(/\/+$/, '');
    const id = route?.params?.id || config.value?.projectId;
    if (apiRoot && id) {
      return `${apiRoot}/${encodeURIComponent(String(id))}/files`;
    }
    return String(config.value?.filesApiUrl || '').replace(/\/+$/, '');
  }

  function filesSaveUrl(fileId) {
    return `${filesUrl()}/${encodeURIComponent(String(fileId))}`;
  }

  function filesDeleteUrl() {
    const base = filesUrl();
    return base ? `${base}/delete` : String(config.value?.filesDeleteApiUrl || '').replace(/\/+$/, '');
  }

  async function getJson(url) {
    if (!url) throw new Error('API URL missing');
    try {
      const { data } = await axios.get(url, { withCredentials: true });
      if (data.status !== 'success') {
        throwApiError(data, data.message || 'LOAD_FAILED');
      }
      return data;
    } catch (e) {
      if (e?.errors) throw e;
      throwApiError(e, 'Failed to load');
    }
  }

  async function postJson(url, payload) {
    if (!url) throw new Error('API URL missing');
    try {
      const { data } = await axios.post(url, payload || {}, {
        headers: { 'Content-Type': 'application/json' },
        withCredentials: true,
      });
      if (data.status === 'failed' || data.status === 'error') {
        throwApiError(data, data.message || 'SAVE_FAILED');
      }
      return data;
    } catch (e) {
      if (e?.errors) throw e;
      throwApiError(e, 'Save failed');
    }
  }

  async function fetchFiles() {
    return getJson(filesUrl());
  }

  async function commitUpload(uploadId) {
    return postJson(filesUrl(), { upload_id: uploadId });
  }

  async function saveFile(fileId, payload) {
    return postJson(filesSaveUrl(fileId), payload);
  }

  async function deleteFiles(ids) {
    return postJson(filesDeleteUrl(), { ids });
  }

  async function uploadFile(file, onProgress) {
    const completed = await resumable.uploadFile(file, {
      metadata: { source: 'datadeposit' },
      onProgress,
    });
    return commitUpload(completed.upload_id);
  }

  function allowedExtensions() {
    const raw = String(config.value?.allowedResourceTypes || '');
    return raw
      .split(',')
      .map((x) => x.trim().toLowerCase())
      .filter(Boolean);
  }

  return {
    fetchFiles,
    commitUpload,
    saveFile,
    deleteFiles,
    uploadFile,
    allowedExtensions,
  };
}
