/** Required fields for timeseries-schema data_structure_reference. */
const REQUIRED_DSD_REF_KEYS = ['idno', 'agency', 'name', 'version'];

/**
 * True when ref is a schema-valid data_structure_reference object.
 */
export function isCompleteDsdReference(ref) {
  if (!ref || typeof ref !== 'object' || Array.isArray(ref)) return false;
  return REQUIRED_DSD_REF_KEYS.every((k) => String(ref[k] ?? '').trim().length > 0);
}

/**
 * Reference safe to re-inject on metadata save (avoids client-side schema failures).
 * Partial/legacy refs are omitted; backend may still enrich from surveys.data_structure_id.
 */
export function dsdReferenceForMetadataSave(ref) {
  if (ref === undefined || ref === null || ref === '') return null;
  if (!isCompleteDsdReference(ref)) return null;
  return JSON.parse(JSON.stringify(ref));
}
