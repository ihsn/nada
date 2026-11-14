import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** Admin Solr ops against top-level api/solr. */
export function useSolrApi() {
  const { apiBaseUrl, csrfToken, csrfTokenName } = useAppConfig();

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

  async function get(path, params) {
    try {
      const { data } = await axios.get(`${base()}/${path}`, { params });
      if (data.status === 'failed' || data.status === 'error') {
        throw new Error(data.message || fallbackMessage(path));
      }
      return data;
    } catch (e) {
      throw new Error(apiError(e, fallbackMessage(path)), { cause: e });
    }
  }

  async function post(path, body = null, params) {
    try {
      const { data } = await axios.post(`${base()}/${path}`, body, {
        headers: csrfHeaders(),
        params,
      });
      if (data.status === 'failed' || data.status === 'error') {
        throw new Error(data.message || fallbackMessage(path));
      }
      return data;
    } catch (e) {
      throw new Error(apiError(e, fallbackMessage(path)), { cause: e });
    }
  }

  function fallbackMessage(path) {
    return `Solr request failed (${path})`;
  }

  return {
    ping: () => get('ping'),
    commit: () => get('commit'),
    clearIndex: () => post('clear_index'),
    dbCounts: () => get('db_counts'),
    indexCounts: () => get('index_counts'),
    systemInfo: () => get('system_info'),
    async coreCheckConfigured() {
      // status "error" means no core in config — still a valid payload
      try {
        const { data } = await axios.get(`${base()}/core_check_configured`);
        if (data.status === 'failed') {
          throw new Error(data.message || 'Failed to check configured core');
        }
        return data;
      } catch (e) {
        throw new Error(apiError(e, 'Failed to check configured core'), { cause: e });
      }
    },
    coreCreate: (name, params) => post(`core_create/${encodeURIComponent(name)}`, null, params),
    coreReload: (name) => get(`core_reload/${encodeURIComponent(name)}`),
    coreDelete: (name) => post(`core_delete/${encodeURIComponent(name)}`),
    coreList: () => get('core_list'),
    importSurveysBatch: (startRow, limit) =>
      post(`import_surveys_batch/${startRow ?? ''}/${limit}`),
    importVariablesBatch: (startRow, limit, params) =>
      post(`import_variables_batch/${startRow}/${limit}`, null, params),
    importCitationsBatch: (startRow, limit) =>
      post(`import_citations_batch/${startRow}/${limit}`),
    importVariablesBySurveyBatch: (surveyId, startRow, limit) =>
      post(`import_variables_by_survey_batch/${surveyId}/${startRow}/${limit}`),
    getAllSurveyIds: () => get('get_all_survey_ids'),
    schema: () => get('schema'),
    schemaStats: () => get('schema_stats'),
    schemaFields: () => get('schema_fields'),
    schemaFieldTypes: () => get('schema_field_types'),
    schemaValidate: () => get('schema_validate'),
    schemaSetupVariables: (replace) =>
      get('schema_setup_variables', replace ? { replace: 'true' } : undefined),
    schemaSetupSurveys: (replace) =>
      get('schema_setup_surveys', replace ? { replace: 'true' } : undefined),
    schemaSetupComplete: (replace) =>
      get('schema_setup_complete', replace ? { replace: 'true' } : undefined),
  };
}
