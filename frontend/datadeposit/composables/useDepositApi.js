import axios from 'axios';
import { useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { extractApiError } from './apiErrors';

export function useDepositApi() {
  const { config } = useAppConfig();
  const route = useRoute();

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

  function apiRoot() {
    return String(config.value?.projectsApiUrl || '').replace(/\/+$/, '');
  }

  function projectId(explicit) {
    if (explicit !== undefined && explicit !== null && String(explicit) !== '') {
      return String(explicit);
    }
    if (route?.params?.id) return String(route.params.id);
    if (config.value?.projectId) return String(config.value.projectId);
    return '';
  }

  function projectRoot(id) {
    const pid = projectId(id);
    const root = apiRoot();
    return pid && root ? `${root}/${encodeURIComponent(pid)}` : '';
  }

  function metadataUrl() {
    const root = projectRoot();
    return root ? `${root}/metadata` : String(config.value?.metadataApiUrl || '').replace(/\/+$/, '');
  }

  function submissionUrl() {
    const root = projectRoot();
    return root ? `${root}/submission` : String(config.value?.submissionApiUrl || '').replace(/\/+$/, '');
  }

  function submitUrl() {
    const root = projectRoot();
    return root ? `${root}/submit` : String(config.value?.submitApiUrl || '').replace(/\/+$/, '');
  }

  function projectUrl(id) {
    const root = projectRoot(id);
    return root || String(config.value?.projectApiUrl || '').replace(/\/+$/, '');
  }

  function projectsUrl() {
    return apiRoot();
  }

  function deleteProjectUrl(id) {
    const base = apiRoot() || String(config.value?.deleteApiUrlBase || '').replace(/\/+$/, '');
    return base && id ? `${base}/${id}/delete` : '';
  }

  function reopenProjectUrl(id) {
    const base = apiRoot() || String(config.value?.reopenApiUrlBase || '').replace(/\/+$/, '');
    return base && id ? `${base}/${id}/reopen` : '';
  }

  function emailProjectUrl(id) {
    const base = apiRoot() || String(config.value?.projectsApiUrl || '').replace(/\/+$/, '');
    const pid = projectId(id);
    return base && pid ? `${base}/${pid}/email` : '';
  }

  function importUrl(id) {
    const root = projectRoot(id);
    return root ? `${root}/import` : '';
  }

  function validateUrl(id) {
    const root = projectRoot(id);
    return root ? `${root}/validate` : '';
  }

  function exportUrl(id, format) {
    const base = apiRoot() || String(config.value?.projectsApiUrl || '').replace(/\/+$/, '');
    const pid = projectId(id);
    const fmt = String(format || '').replace(/[^a-z0-9_]/gi, '');
    return base && pid && fmt ? `${base}/${pid}/export/${fmt}` : '';
  }

  async function fetchMetadata() {
    const data = await getJson(metadataUrl());
    return data.metadata && typeof data.metadata === 'object' ? data.metadata : {};
  }

  async function saveMetadata(payload) {
    return postJson(metadataUrl(), payload);
  }

  async function fetchSubmission() {
    const data = await getJson(submissionUrl());
    return data.submission && typeof data.submission === 'object' ? data.submission : {};
  }

  async function saveSubmission(payload) {
    return postJson(submissionUrl(), payload);
  }

  async function submitProject(payload) {
    return postJson(submitUrl(), payload);
  }

  async function fetchProject(id) {
    const data = await getJson(projectUrl(id));
    return data.project && typeof data.project === 'object' ? data.project : {};
  }

  async function saveProject(payload) {
    return postJson(projectUrl(), payload);
  }

  async function fetchProjects({
    page = 1,
    pageSize = 100,
    q = '',
    status = '',
    sortBy = 'created_on',
    sortOrder = 'desc',
  } = {}) {
    const base = projectsUrl();
    if (!base) throw new Error('API URL missing');
    const url = new URL(base, window.location.origin);
    url.searchParams.set('page', String(page));
    url.searchParams.set('page_size', String(Math.min(100, Math.max(1, pageSize))));
    if (q) url.searchParams.set('q', q);
    if (status) url.searchParams.set('status', status);
    if (sortBy) url.searchParams.set('sort_by', sortBy);
    if (sortOrder) url.searchParams.set('sort_order', sortOrder);
    const data = await getJson(url.toString());
    return {
      projects: Array.isArray(data.projects) ? data.projects : [],
      total: Number(data.total) || 0,
      page: Number(data.page) || page,
      pageSize: Number(data.page_size) || pageSize,
      totalPages: Number(data.total_pages) || 1,
    };
  }

  async function createProject(payload) {
    return postJson(projectsUrl(), payload);
  }

  async function deleteProject(id) {
    return postJson(deleteProjectUrl(id), {});
  }

  async function requestReopen(id, reason) {
    return postJson(reopenProjectUrl(id), { reason });
  }

  async function emailSummary(id, emails) {
    return postJson(emailProjectUrl(id), { emails });
  }

  async function fetchImportSources(q, id) {
    const root = importUrl(id);
    if (!root) throw new Error('API URL missing');
    const url = new URL(root, window.location.origin);
    const query = String(q || '').trim();
    if (query) url.searchParams.set('q', query);
    const data = await getJson(url.toString());
    return {
      dataType: data.data_type || '',
      projects: Array.isArray(data.projects) ? data.projects : [],
    };
  }

  async function importMetadata(sourceProjectId, id) {
    return postJson(importUrl(id), { source_project_id: Number(sourceProjectId) });
  }

  async function importMetadataJson(json, id) {
    return postJson(importUrl(id), { json });
  }

  async function validateProject({ metadata, submission } = {}, id) {
    const url = validateUrl(id);
    if (!url) throw new Error('API URL missing');
    const payload = {};
    if (metadata && typeof metadata === 'object') payload.metadata = metadata;
    if (submission && typeof submission === 'object') payload.submission = submission;
    const data = await postJson(url, payload);
    return {
      valid: data.valid !== false && !(Array.isArray(data.issues) && data.issues.length),
      issues: Array.isArray(data.issues) ? data.issues : [],
    };
  }

  return {
    projectId,
    fetchMetadata,
    saveMetadata,
    fetchSubmission,
    saveSubmission,
    submitProject,
    fetchProject,
    saveProject,
    fetchProjects,
    createProject,
    deleteProject,
    requestReopen,
    emailSummary,
    fetchImportSources,
    importMetadata,
    importMetadataJson,
    validateProject,
    exportUrl,
  };
}
