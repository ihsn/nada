import { ref } from 'vue';
import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** Matches `Data_structure_component_model::$allowed_column_types`. */
export const COLUMN_TYPES = [
  'dimension',
  'time_period',
  'measure',
  'attribute',
  'indicator_id',
  'indicator_name',
  'annotation',
  'geography',
  'observation_value',
  'periodicity',
];

/** Matches `Data_structure_component_model::$allowed_data_types`. */
export const DATA_TYPES = ['string', 'integer', 'float', 'double', 'date', 'boolean'];

/**
 * Admin data structures API. Mutations use POST aliases where available (PUT/DELETE blocked on some networks).
 * DSD admin UI uses numeric `data_structures.id` only for structure-scoped URLs (not idno).
 */
export function useDataStructuresApi() {
  const { apiBaseUrl, codelistsApiBaseUrl } = useAppConfig();
  const loading = ref(false);
  const error = ref(null);

  const base = () => (apiBaseUrl.value || '').replace(/\/$/, '');
  const codelistsBase = () => (codelistsApiBaseUrl.value || '').replace(/\/$/, '');

  /** @returns {number} */
  function requireStructureId(value, label = 'structure id') {
    const n = Number(value);
    if (!Number.isInteger(n) || n < 1) {
      throw new Error(`${label} must be a positive integer`);
    }
    return n;
  }

  /**
   * @param {object} [opts]
   * @param {boolean} [opts.flat] one row per version
   * @param {number} [opts.page] when set (≥1), response is paginated and return value is an object
   * @param {number} [opts.per_page]
   * @param {string} [opts.search]
   * @param {number|null} [opts.status] exact status code
   * @returns {Promise<Array|{ structures: array, total: number, page: number, per_page: number }>}
   */
  async function fetchDataStructures({ flat = false, page, per_page = 50, search = '', status = null } = {}) {
    loading.value = true;
    error.value = null;
    try {
      const params = {};
      if (flat) params.flat = '1';
      const usePaged = page != null && page !== '' && Number(page) >= 1;
      if (usePaged) {
        params.page = String(Math.max(1, parseInt(String(page), 10) || 1));
        const pp = Math.min(200, Math.max(1, parseInt(String(per_page), 10) || 50));
        params.per_page = String(pp);
        const q = typeof search === 'string' ? search.trim() : '';
        if (q) params.search = q;
        if (status != null && status !== '' && Number.isFinite(Number(status))) {
          params.status = String(Number(status));
        }
      }
      const { data } = await axios.get(`${base()}`, { params });
      if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
      const res = data.result;
      if (usePaged) {
        return {
          structures: res?.data_structures ?? [],
          total: res?.total ?? 0,
          page: res?.page ?? 1,
          per_page: res?.per_page ?? 50,
        };
      }
      return res?.data_structures ?? [];
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function fetchDataStructure(id, withComponents = true) {
    const sid = requireStructureId(id, 'structure id');
    const { data } = await axios.get(`${base()}/${encodeURIComponent(String(sid))}`, {
      params: { with_components: withComponents ? '1' : '0' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch');
    return data.result?.data_structure ?? null;
  }

  /**
   * All version rows for the structure family containing this `data_structures.id`.
   * @param {string|number} structureId — numeric primary key only
   */
  async function fetchStructureVersions(structureId) {
    const sid = requireStructureId(structureId, 'structure id');
    const { data } = await axios.get(`${base()}/versions/${encodeURIComponent(String(sid))}`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch versions');
    return data.result?.data_structures ?? [];
  }

  async function fetchDataStructureProjects(structureId, { page = 1, per_page = 25 } = {}) {
    const sid = requireStructureId(structureId, 'structure id');
    const params = {
      page: String(Math.max(1, parseInt(String(page), 10) || 1)),
      per_page: String(Math.min(200, Math.max(1, parseInt(String(per_page), 10) || 25))),
    };
    const { data } = await axios.get(`${base()}/projects/${encodeURIComponent(String(sid))}`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch projects');
    const res = data.result ?? {};
    return {
      projects: res.projects ?? [],
      total: Number(res.total) || 0,
      page: Number(res.page) || 1,
      per_page: Number(res.per_page) || 25,
      data_structure_id: Number(res.data_structure_id) || sid,
    };
  }

  async function createDataStructure(payload) {
    const { data } = await axios.post(`${base()}/create`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Create failed');
    return data.result?.data_structure;
  }

  async function updateDataStructure(structureId, payload) {
    const sid = requireStructureId(structureId, 'structure id');
    const seg = encodeURIComponent(String(sid));
    const { data } = await axios.post(`${base()}/update/${seg}`, payload, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Update failed');
    return data.result?.data_structure;
  }

  async function updateDataStructureStatus(structureId, status) {
    const sid = requireStructureId(structureId, 'structure id');
    if (!Number.isFinite(Number(status))) {
      throw new Error('status must be numeric');
    }
    const seg = encodeURIComponent(String(sid));
    const { data } = await axios.post(
      `${base()}/status/${seg}`,
      { status: Number(status) },
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Status update failed');
    return data.result?.data_structure;
  }

  async function deleteDataStructure(structureId) {
    const sid = requireStructureId(structureId, 'structure id');
    const seg = encodeURIComponent(String(sid));
    // POST …/delete/{id_or_idno}: same resolution as DELETE …/{segment} when DELETE is blocked.
    const { data } = await axios.post(`${base()}/delete/${seg}`);
    if (data.status !== 'success') throw new Error(data.message || 'Delete failed');
    return data.result;
  }

  /**
   * Delete many structures by primary key id. Partial success is possible; check result.failed.
   * @param {Array<string|number>} ids
   */
  async function deleteDataStructuresBatch(ids) {
    const clean = [
      ...new Set(
        (Array.isArray(ids) ? ids : [])
          .map((id) => Number(id))
          .filter((n) => Number.isInteger(n) && n >= 1)
      ),
    ];
    if (clean.length === 0) {
      throw new Error('At least one structure id is required');
    }
    const { data } = await axios.post(
      `${base()}/batch_delete`,
      { ids: clean },
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Batch delete failed');
    return data.result ?? {};
  }

  async function exportDataStructure(structureId) {
    const sid = requireStructureId(structureId, 'structure id');
    const { data } = await axios.get(`${base()}/export/${encodeURIComponent(String(sid))}`);
    if (data.status !== 'success') throw new Error(data.message || 'Export failed');
    return data.result?.export ?? null;
  }

  async function validatePayload(body) {
    const { data } = await axios.post(`${base()}/validate`, body, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.message && data.status === 'error' && !data.result) {
      throw new Error(data.message);
    }
    return data.result ?? { valid: false, errors: [], warnings: [] };
  }

  async function createComponent(structureId, payload) {
    const sid = requireStructureId(structureId, 'structure id');
    const { data } = await axios.post(
      `${base()}/components/${encodeURIComponent(String(sid))}`,
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Create component failed');
    return data.result?.component;
  }

  async function updateComponent(componentId, payload) {
    if (componentId == null) throw new Error('component id required');
    const { data } = await axios.post(
      `${base()}/components_update/${encodeURIComponent(componentId)}`,
      payload,
      { headers: { 'Content-Type': 'application/json' } }
    );
    if (data.status !== 'success') throw new Error(data.message || 'Update component failed');
    return data.result?.component;
  }

  async function deleteComponent(componentId) {
    if (componentId == null) throw new Error('component id required');
    const { data } = await axios.post(`${base()}/components_delete/${encodeURIComponent(componentId)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Delete component failed');
    return data.result;
  }

  /**
   * Import DSD + codelists from SDMX-ML structure XML (multipart: file, overwrite_codelists, optional dsd_id).
   * @param {FormData} formData
   */
  async function importFromSdmxXml(formData) {
    const { data } = await axios.post(`${base()}/import`, formData);
    if (data.status !== 'success') throw new Error(data.message || 'Import failed');
    return data.result ?? null;
  }

  /**
   * Import DSD + codelists from JSON (`data_structure` envelope). POST …/import_json
   * @param {Record<string, unknown>} body Parsed JSON (overwrite merged by caller if needed)
   */
  async function importFromJson(body) {
    const { data } = await axios.post(`${base()}/import_json`, body, {
      headers: { 'Content-Type': 'application/json' },
    });
    if (data.status !== 'success') throw new Error(data.message || 'Import failed');
    return data.result ?? null;
  }

  /** Codelist rows for pickers (admin codelists index, full list — legacy). */
  async function fetchCodelistsForPicker() {
    const b = codelistsBase();
    if (!b) throw new Error('codelists API base URL not configured');
    const { data } = await axios.get(b, { params: { with_counts: '0' } });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch codelists');
    return data.result?.codelists ?? [];
  }

  /**
   * Paginated codelists (admin index with `page`).
   * @param {{ page?: number, perPage?: number, search?: string, withCounts?: boolean }} opts
   */
  async function fetchCodelistsPage({ page = 1, perPage = 25, search = '', withCounts = false } = {}) {
    const b = codelistsBase();
    if (!b) throw new Error('codelists API base URL not configured');
    const params = {
      page,
      per_page: perPage,
      with_counts: withCounts ? '1' : '0',
    };
    const q = typeof search === 'string' ? search.trim() : '';
    if (q) params.search = q;
    const { data } = await axios.get(b, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch codelists');
    const r = data.result ?? {};
    return {
      codelists: r.codelists ?? [],
      total: Number(r.total) || 0,
      page: Number(r.page) || page,
      per_page: Number(r.per_page) || perPage,
    };
  }

  /** One codelist row by id (for picker label when editing). */
  async function fetchCodelistMetaForPicker(id) {
    if (id == null || id === '') return null;
    const b = codelistsBase();
    if (!b) throw new Error('codelists API base URL not configured');
    const { data } = await axios.get(`${b}/item/${encodeURIComponent(id)}`);
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch codelist');
    return data.result?.codelist ?? null;
  }

  /**
   * Flat codelist entries { value, label } for one codelist (code + default title only, no translations).
   * @param {string|number} codelistId
   * @param {{ page?: number, perPage?: number, search?: string }} opts
   */
  async function fetchCodelistItemsFlatPage(codelistId, { page = 1, perPage = 50, search = '' } = {}) {
    const b = codelistsBase();
    if (!b) throw new Error('codelists API base URL not configured');
    if (codelistId == null || codelistId === '') throw new Error('codelist id required');
    const params = { view: 'flat', page, per_page: perPage };
    const q = typeof search === 'string' ? search.trim() : '';
    if (q) params.search = q;
    const { data } = await axios.get(`${b}/item_items/${encodeURIComponent(codelistId)}`, { params });
    if (data.status !== 'success') throw new Error(data.message || 'Failed to fetch codelist items');
    const r = data.result ?? {};
    return {
      items: r.items ?? [],
      total: Number(r.total) || 0,
      page: Number(r.page) || page,
      per_page: Number(r.per_page) || perPage,
    };
  }

  return {
    loading,
    error,
    fetchDataStructures,
    fetchDataStructure,
    fetchStructureVersions,
    fetchDataStructureProjects,
    createDataStructure,
    updateDataStructure,
    updateDataStructureStatus,
    deleteDataStructure,
    deleteDataStructuresBatch,
    exportDataStructure,
    validatePayload,
    createComponent,
    updateComponent,
    deleteComponent,
    fetchCodelistsForPicker,
    fetchCodelistsPage,
    fetchCodelistMetaForPicker,
    fetchCodelistItemsFlatPage,
    importFromSdmxXml,
    importFromJson,
  };
}
