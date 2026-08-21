import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { formatApiError } from '../utils/apiError';

export function useDisplayTemplatesApi() {
  const { apiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  const base = () => {
    let b = (apiBaseUrl.value || '').trim();
    b = b.replace(/\/+$/, '');
    return b;
  };

  const jsonHeaders = { 'Content-Type': 'application/json' };

  async function fetchTemplates({ data_type, status } = {}) {
    loading.value = true;
    error.value = null;
    try {
      const params = {};
      if (data_type) params.data_type = data_type;
      if (status) params.status = status;
      const { data } = await axios.get(`${base()}`, { params });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
      return data.result?.templates ?? [];
    } catch (e) {
      error.value = /** @type {Error} */ (e);
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTemplate(uid) {
    const { data } = await axios.get(`${base()}/${encodeURIComponent(uid)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
    return {
      template: data.result?.template ?? null,
      coreTemplate: data.result?.core_template ?? null,
      coreTemplateParts: data.result?.core_template_parts ?? null,
      coreTemplateUid: data.result?.core_template_uid ?? null,
    };
  }

  async function fetchCoreTemplate(identifier) {
    const { data } = await axios.get(`${base()}/core/${encodeURIComponent(identifier)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch core template');
    return {
      core: data.result?.core ?? null,
      coreTemplate: data.result?.core_template ?? null,
      coreTemplateParts: data.result?.core_template_parts ?? null,
    };
  }

  async function fetchCoreTemplates({ data_type } = {}) {
    const params = {};
    if (data_type) params.data_type = data_type;
    const { data } = await axios.get(`${base()}/cores`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch core templates');
    return data.result?.cores ?? [];
  }

  async function createTemplate(payload) {
    const { data } = await axios.post(`${base()}`, payload, { headers: jsonHeaders });
    if (data.status !== 'success') throw new Error(data.message || 'Create failed');
    return data.result?.template;
  }

  async function updateTemplate(uid, payload) {
    const { data } = await axios.post(`${base()}/${encodeURIComponent(uid)}`, payload, {
      headers: jsonHeaders,
    });
    if (data.status !== 'success') throw new Error(data.message || 'Update failed');
    return data.result?.template;
  }

  async function deleteTemplate(uid) {
    const { data } = await axios.post(
      `${base()}/${encodeURIComponent(uid)}/delete`,
      {},
      { headers: jsonHeaders }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
    return data.result;
  }

  async function duplicateTemplate(uid) {
    const { data } = await axios.post(
      `${base()}/${encodeURIComponent(uid)}/duplicate`,
      {},
      { headers: jsonHeaders }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Duplicate failed');
    return data.result?.template;
  }

  async function setDefaultTemplate(data_type, uid) {
    const { data } = await axios.post(
      `${base()}/default/${encodeURIComponent(data_type)}/${encodeURIComponent(uid)}`,
      {},
      { headers: jsonHeaders }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Failed to set default');
    return data.result?.default_template;
  }

  async function importTemplate(payload) {
    try {
      const { data } = await axios.post(`${base()}/import`, payload, {
        headers: jsonHeaders,
      });
      if (data.status !== 'success') throw new Error(data.message || 'Import failed');
      return data.result;
    } catch (e) {
      throw new Error(formatApiError(e, 'Import failed'));
    }
  }

  async function validatePayload(payload) {
    const { data } = await axios.post(`${base()}/validate`, payload, {
      headers: jsonHeaders,
    });
    if (data.status !== 'success') throw new Error(data.message || 'Validation failed');
    return data.result;
  }

  async function fetchRenderers() {
    const { data } = await axios.get(`${base()}/renderers`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch renderers');
    return data.result?.renderers ?? [];
  }

  async function fetchRenderersBySourceType(source_type, data_type) {
    const params = {};
    if (data_type) params.data_type = data_type;
    const { data } = await axios.get(`${base()}/renderers/${encodeURIComponent(source_type)}`, {
      params,
    });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch renderers');
    return data.result?.renderers ?? [];
  }

  async function fetchExport(uid) {
    const { data } = await axios.get(`${base()}/${encodeURIComponent(uid)}/export`);
    if (data.status !== 'success') throw new Error(data.message || 'Export failed');
    return data.result ?? data;
  }

  return {
    loading,
    error,
    fetchTemplates,
    fetchTemplate,
    fetchCoreTemplate,
    fetchCoreTemplates,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    duplicateTemplate,
    setDefaultTemplate,
    importTemplate,
    validatePayload,
    fetchRenderers,
    fetchRenderersBySourceType,
    fetchExport,
  };
}
