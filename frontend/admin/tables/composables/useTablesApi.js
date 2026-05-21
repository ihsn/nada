import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useTablesApi() {
  const { apiBaseUrl, catalogApiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  const base = () => (apiBaseUrl.value || '').replace(/\/$/, '');
  const catalogBase = () =>
    (catalogApiBaseUrl.value || apiBaseUrl.value?.replace(/\/api\/tables\/?$/, '/api/catalog') || '')
      .replace(/\/$/, '');

  async function fetchTables({ limit = 15, offset = 0 } = {}) {
    loading.value = true;
    error.value = null;
    try {
      const { data } = await axios.get(base(), { params: { limit, offset } });
      if (data.status !== 'success') {
        throw new Error(data.message || 'Failed to load tables');
      }
      const tablesObj = data.tables || {};
      const tables = Object.values(tablesObj).map((table) => ({
        ...table,
        _id: table._id || table.table_id,
        table_key: table._id || table.table_id || `table_${Math.random()}`,
      }));
      return { tables, total: data.total || 0 };
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteTable(dbId, tableId, deleteDefinition = true) {
    const { data } = await axios.post(`${base()}/delete/${dbId}/${tableId}`, {
      delete_definition: deleteDefinition,
    });
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to delete table');
    }
    return data;
  }

  async function createTable(dbId, tableId, payload) {
    const { data } = await axios.post(`${base()}/create_table/${dbId}/${tableId}`, payload);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to create table');
    }
    return data;
  }

  async function fetchTableInfo(dbId, tableId) {
    const { data } = await axios.get(`${base()}/info/${dbId}/${tableId}`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to load table info');
    }
    return data.result;
  }

  async function updateTableInfo(dbId, tableId, payload) {
    const { data } = await axios.put(`${base()}/update_table/${dbId}/${tableId}`, payload);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to update table');
    }
    return data;
  }

  async function fetchFields(dbId, tableId) {
    const { data } = await axios.get(`${base()}/fields/${dbId}/${tableId}`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to load fields');
    }
    return data.fields || [];
  }

  async function fetchField(dbId, tableId, fieldName) {
    const { data } = await axios.get(`${base()}/field/${dbId}/${tableId}/${fieldName}`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to load field');
    }
    return data.field;
  }

  async function upsertField(dbId, tableId, fieldData) {
    const { data } = await axios.post(`${base()}/fields/${dbId}/${tableId}`, fieldData);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to save field');
    }
    return data;
  }

  async function deleteField(dbId, tableId, fieldName) {
    const { data } = await axios.post(`${base()}/fields/${dbId}/${tableId}/${fieldName}/delete`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to delete field');
    }
    return data;
  }

  async function populateFields(dbId, tableId) {
    const { data } = await axios.post(`${base()}/fields/${dbId}/${tableId}/populate`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to populate schema');
    }
    return data;
  }

  async function syncFields(dbId, tableId) {
    const { data } = await axios.post(`${base()}/fields/${dbId}/${tableId}/sync`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to sync fields');
    }
    return data;
  }

  async function convertFieldTypes(dbId, tableId, payload) {
    const { data } = await axios.post(`${base()}/convert_field_types/${dbId}/${tableId}`, payload);
    if (data.status !== 'success' && data.status !== 'partial') {
      throw new Error(data.message || 'Failed to convert data types');
    }
    return data;
  }

  async function fetchIndexes(dbId, tableId) {
    const { data } = await axios.get(`${base()}/indexes/${dbId}/${tableId}`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to load indexes');
    }
    const result = data.result || {};
    return Object.keys(result).map((name) => ({ name, key: result[name] }));
  }

  async function createIndex(dbId, tableId, indexFields) {
    const { data } = await axios.post(`${base()}/indexes/${dbId}/${tableId}`, {
      index_fields: indexFields,
    });
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to create index');
    }
    return data;
  }

  async function createTextIndex(dbId, tableId, indexFields) {
    const { data } = await axios.post(`${base()}/text_index/${dbId}/${tableId}`, {
      index_fields: indexFields,
    });
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to create text index');
    }
    return data;
  }

  async function deleteIndex(dbId, tableId, indexName) {
    const { data } = await axios.delete(`${base()}/indexes/${dbId}/${tableId}/${indexName}`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to delete index');
    }
    return data;
  }

  async function deleteAllIndexes(dbId, tableId) {
    const { data } = await axios.post(`${base()}/indexes/${dbId}/${tableId}/all`);
    if (data.status !== 'success') {
      throw new Error(data.message || 'Failed to delete all indexes');
    }
    return data;
  }

  async function fetchStudyLinks(dbId, tableId) {
    const { data } = await axios.get(`${base()}/${dbId}/${tableId}/studies`);
    if (data.status !== 'success') {
      throw new Error(data.error || 'Failed to load study links');
    }
    return data.studies || [];
  }

  async function searchCatalogStudies(query) {
    const { data } = await axios.get(`${catalogBase()}/search`, {
      params: { sk: query, ps: 20, page: 1 },
    });
    return data.result?.rows || [];
  }

  async function attachStudy(dbId, tableId, idno) {
    const { data } = await axios.post(`${base()}/attach_to_study`, {
      db_id: dbId,
      table_id: tableId,
      idno,
    });
    if (data.status !== 'success') {
      throw new Error(data.error || 'Failed to attach study');
    }
    return data;
  }

  async function detachStudy(dbId, tableId, sid) {
    const { data } = await axios.post(`${base()}/detach_from_study`, {
      db_id: dbId,
      table_id: tableId,
      sid,
    });
    if (data.status !== 'success') {
      throw new Error(data.error || 'Failed to detach study');
    }
    return data;
  }

  function apiUrl(table, endpoint) {
    const dbId = table.db_id || table.metadata?.db_id;
    const tableId =
      table.table_id ||
      table.metadata?.table_id ||
      (table._id ? String(table._id).replace(`table_${dbId}_`, '') : '');
    const urls = {
      info: `${base()}/info/${dbId}/${tableId}`,
      fields: `${base()}/fields/${dbId}/${tableId}`,
      data: `${base()}/data/${dbId}/${tableId}`,
    };
    return urls[endpoint] || '';
  }

  function exportDefinitionUrl(dbId, tableId) {
    return `${base()}/export_definition/${dbId}/${tableId}`;
  }

  return {
    loading,
    error,
    base,
    fetchTables,
    deleteTable,
    createTable,
    fetchTableInfo,
    updateTableInfo,
    fetchFields,
    fetchField,
    upsertField,
    deleteField,
    populateFields,
    syncFields,
    convertFieldTypes,
    fetchIndexes,
    createIndex,
    createTextIndex,
    deleteIndex,
    deleteAllIndexes,
    fetchStudyLinks,
    searchCatalogStudies,
    attachStudy,
    detachStudy,
    apiUrl,
    exportDefinitionUrl,
  };
}
