/**
 * Read-only calls to the public data tables API (/api/tables) for one table.
 */

async function readJson(response) {
  let body;
  try {
    body = await response.json();
  } catch {
    body = null;
  }
  if (!response.ok || (body && body.status === 'failed')) {
    const message = body?.message || body?.error || `HTTP ${response.status}`;
    const error = new Error(message);
    error.status = response.status;
    throw error;
  }
  return body;
}

export function useTableDataApi({ apiBaseUrl, dbId, tableId }) {
  const base = String(apiBaseUrl || '').replace(/\/$/, '');
  const path = `${encodeURIComponent(dbId)}/${encodeURIComponent(tableId)}`;

  const urls = {
    info: `${base}/info/${path}`,
    infoWithDictionary: `${base}/info/${path}?data_dictionary=true`,
    data: `${base}/data/${path}`,
    dictionary: `${base}/data_dictionary/${path}`,
    aggregate: `${base}/aggregate/${path}`,
    download: `${base}/download/${path}`,
  };

  /** Table metadata + data dictionary in one request. */
  async function fetchInfo() {
    const body = await readJson(await fetch(urls.infoWithDictionary, { credentials: 'same-origin' }));
    const result = body?.result || {};
    const metadata = result.metadata || {};
    const dictionary = Array.isArray(metadata.data_dictionary) ? metadata.data_dictionary : [];
    const filterableFields = Array.isArray(result.filterable_fields) ? result.filterable_fields : [];
    const storedCsv = result.stored_csv && result.stored_csv.available ? result.stored_csv : null;
    return { metadata, count: result.count ?? null, dictionary, filterableFields, storedCsv };
  }

  /** Title and row count only (no data dictionary), e.g. for the table selector. */
  async function fetchSummary() {
    const body = await readJson(await fetch(urls.info, { credentials: 'same-origin' }));
    const result = body?.result || {};
    return { title: result.metadata?.title || '', count: result.count ?? null };
  }

  /** @param {URLSearchParams} params  @param {AbortSignal} signal */
  async function fetchData(params, signal) {
    const url = `${urls.data}?${params.toString()}`;
    return readJson(await fetch(url, { credentials: 'same-origin', signal }));
  }

  async function fetchBulkDownloads(siteUrl, idno) {
    if (!idno) return [];
    const url = `${String(siteUrl || '').replace(/\/$/, '')}/api/downloads/${encodeURIComponent(idno)}/files?type=data`;
    try {
      const body = await readJson(await fetch(url, { credentials: 'same-origin' }));
      return Array.isArray(body?.files) ? body.files : [];
    } catch {
      return [];
    }
  }

  return { urls, fetchInfo, fetchSummary, fetchData, fetchBulkDownloads };
}
