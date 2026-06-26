/**
 * Resolve a dataset type code to a translated label (dataset_type_* then tab_* then DB title).
 * @param {(key: string, fallback?: string) => string} t
 * @param {string} code
 * @param {string} [dbTitle]
 */
export function catalogDatasetTypeLabel(t, code, dbTitle = '') {
  const key = String(code || '').toLowerCase();
  if (!key) return dbTitle || '';

  const byDatasetType = t(`dataset_type_${key}`, '');
  if (byDatasetType) return byDatasetType;

  const byTab = t(`tab_${key}`, '');
  if (byTab) return byTab;

  return dbTitle || key;
}
