import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

/** @type {Promise<object>|null} */
let optionsPromise = null;

/**
 * Client for api/admin/citations. Every POST carries the session CSRF token in X-CSRF-TOKEN.
 */
export function useCitationsApi() {
  const { apiBaseUrl, csrfToken } = useAppConfig();

  function base() {
    return String(apiBaseUrl.value || '').replace(/\/?$/, '/');
  }

  function url(path = '') {
    return `${base()}${path}`;
  }

  function headers(extra = {}) {
    return { 'X-CSRF-TOKEN': csrfToken.value || '', ...extra };
  }

  async function get(path, params) {
    const { data } = await axios.get(url(path), { params });
    return data;
  }

  async function post(path, body = {}) {
    const { data } = await axios.post(url(path), body, { headers: headers({ 'Content-Type': 'application/json' }) });
    return data;
  }

  async function postForm(path, form, onProgress) {
    const { data } = await axios.post(url(path), form, {
      headers: headers(),
      onUploadProgress: onProgress
        ? (e) => onProgress(e.total ? Math.round((e.loaded / e.total) * 100) : 0)
        : undefined,
    });
    return data;
  }

  /** Types and their fields, flags, languages, users and what the caller may do; loaded once per page. */
  function options() {
    if (!optionsPromise) {
      optionsPromise = get('options')
        .then((d) => d.options)
        .catch((e) => {
          optionsPromise = null;
          throw e;
        });
    }
    return optionsPromise;
  }

  /** Download address for an export (the browser follows it, so the session cookie is sent) */
  function exportUrl({ format = 'csv', ids = [], filters = {} } = {}) {
    const q = new URLSearchParams({ format });
    if (ids.length) {
      ids.forEach((id) => q.append('ids[]', String(id)));
    } else {
      Object.entries(filters).forEach(([k, v]) => q.append(k, String(v)));
    }
    return `${url('export')}?${q.toString()}`;
  }

  return {
    options,
    list: (params) => get('', { ...params, with_facets: params?.with_facets ? 1 : undefined }),
    get: (id) => get(String(id)).then((d) => d.citation),
    create: (body) => post('', body).then((d) => d.citation),
    update: (id, body) => post(String(id), body).then((d) => d.citation),
    remove: (id) => post(`${id}/delete`),
    publish: (id, published) => post(`${id}/publish`, { published }),
    batch: (body) => post('batch', body),
    setStudies: (id, sids) => post(`${id}/studies`, { sids }).then((d) => d.studies),
    preview: (metadata) => post('preview', { metadata }).then((d) => d.formatted),
    duplicates: (body) => post('duplicates', body).then((d) => d.rows),
    doi: (doi) => get('doi', { doi }),
    checkUrl: (id) => post(`${id}/check_url`),
    uploadAttachment: (id, file, onProgress) => {
      const form = new FormData();
      form.append('file', file);
      return postForm(`${id}/attachment`, form, onProgress).then((d) => d.attachment);
    },
    deleteAttachment: (id) => post(`${id}/attachment/delete`),
    attachmentUrl: (id) => url(`${id}/attachment`),
    importParse: (form) => postForm('import/parse', form),
    importCommit: (body) => post('import/commit', body),
    exportUrl,
  };
}

const ERROR_MESSAGES = {
  'SESSION-REQUIRED': ['cit_session_expired', 'Your session has expired. Sign in again.'],
  'ACCESS-DENIED': ['cit_access_denied', 'Access denied'],
  ACCESS_DENIED: ['cit_access_denied', 'Access denied'],
  'CSRF-TOKEN-INVALID': ['cit_csrf_invalid', 'The page is out of date. Reload it and try again.'],
  NOT_FOUND: ['cit_not_found', 'Not found.'],
  VALIDATION_ERROR: ['cit_validation_failed', 'Please correct the highlighted fields.'],
  IMPORT_FAILED: ['cit_err_import_failed', 'The file could not be read as citations.'],
  IMPORT_NOT_FOUND: ['cit_err_import_expired', 'The import preview has expired. Upload the file again.'],
  NOTHING_SELECTED: ['cit_err_nothing_selected', 'Select at least one citation.'],
  NO_IDS: ['cit_err_nothing_selected', 'Select at least one citation.'],
  TOO_MANY_IDS: ['cit_err_too_many', 'Too many citations selected (500 at most).'],
  NO_CITATIONS_MATCH: ['cit_err_no_match', 'No citations match.'],
  DOI_INVALID: ['cit_err_doi_invalid', 'This is not a valid DOI.'],
  DOI_NOT_FOUND: ['cit_err_doi_not_found', 'No record was found for this DOI.'],
  CROSSREF_UNAVAILABLE: ['cit_err_crossref', 'The DOI registry could not be reached. Try again later.'],
  CROSSREF_DISABLED: ['cit_err_crossref_disabled', 'DOI lookup is turned off on this site.'],
  FILE_TOO_LARGE: ['cit_err_file_too_large', 'The file is too large.'],
  NO_FILE: ['cit_err_no_file', 'Choose a file first.'],
  FILE_TYPE_NOT_ALLOWED: ['cit_err_file_type', 'This type of file cannot be attached.'],
  FILE_CONTENT_MISMATCH: ['cit_err_file_content', 'The file does not look like its file type.'],
  UPLOAD_FAILED: ['cit_err_upload', 'The upload failed.'],
  STORAGE_NOT_WRITABLE: ['cit_err_storage', 'The server cannot store the file.'],
};

/** Messages for the field errors of a VALIDATION_ERROR response */
const FIELD_ERRORS = {
  TITLE_REQUIRED: ['cit_err_title_required', 'The title is required.'],
  TITLE_TOO_LONG: ['cit_err_title_long', 'The title is too long.'],
  INVALID_FLAG: ['cit_err_flag', 'This flag is not in the list of flags.'],
  INVALID_TYPE: ['cit_err_type', 'This citation type is not valid.'],
  INVALID_DATE: ['cit_err_date', 'This date is not valid.'],
  INVALID_METADATA: ['cit_err_metadata', 'The citation data is not valid.'],
  INVALID_VALUE: ['cit_err_value', 'This value is not valid.'],
  REQUIRED: ['cit_err_required', 'This is required.'],
  STUDY_NOT_FOUND: ['cit_err_study', 'Some of the studies no longer exist.'],
  URL_TOO_LONG: ['cit_err_url_long', 'The URL is too long.'],
};

/**
 * @param {any} e axios error or Error
 * @param {(key: string, fallback?: string) => string} t
 * @returns {string}
 */
export function apiErrorMessage(e, t) {
  const status = e?.response?.status;
  const code = e?.response?.data?.message;
  if (code && ERROR_MESSAGES[code]) {
    const [key, fallback] = ERROR_MESSAGES[code];
    return t(key, fallback);
  }
  if (status === 403) return t('cit_access_denied', 'Access denied');
  if (status === 401) return t('cit_session_expired', 'Your session has expired. Sign in again.');
  if (status === 413) return t('cit_err_file_too_large', 'The file is too large.');
  return t('cit_request_failed', 'Request failed');
}

/**
 * Field errors of a 422 response as {field: message}. A list of values (e.g. the files of a failed import)
 * is joined; codes the page does not know are shown as they are.
 *
 * @param {any} e
 * @param {(key: string, fallback?: string) => string} t
 * @returns {Record<string, string>}
 */
export function apiFieldErrors(e, t) {
  const errors = e?.response?.data?.errors;
  if (!errors || typeof errors !== 'object') return {};
  const out = {};
  Object.entries(errors).forEach(([field, value]) => {
    const codes = Array.isArray(value) ? value : [value];
    out[field] = codes
      .map((c) => {
        const code = String(c ?? '').replace(/<[^>]*>/g, '').trim();
        const known = FIELD_ERRORS[code] || ERROR_MESSAGES[code];
        return known ? t(known[0], known[1]) : code;
      })
      .join(' ');
  });
  return out;
}

/**
 * @param {number|null|undefined} ts unix seconds
 * @param {boolean} [withTime]
 */
export function formatDate(ts, withTime = false) {
  if (!ts) return '';
  const d = new Date(Number(ts) * 1000);
  if (Number.isNaN(d.getTime())) return '';
  return withTime ? d.toLocaleString() : d.toLocaleDateString();
}
