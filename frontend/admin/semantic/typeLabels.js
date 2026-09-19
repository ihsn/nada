/** Human labels for catalog / index type keys used across this admin app. */
const TYPE_LABELS = {
  survey: 'Microdata',
  microdata: 'Microdata',
  timeseries: 'Indicator',
  indicator: 'Indicator',
  geospatial: 'Geospatial',
  document: 'Document',
  table: 'Table',
  image: 'Image',
  video: 'Video',
  script: 'Script',
  citation: 'Citation',
};

export function typeLabel(type) {
  if (type == null || type === '') return '—';
  const key = String(type);
  return TYPE_LABELS[key.toLowerCase()] || key;
}

export function formatCount(n) {
  if (n == null || n === '') return '—';
  const num = Number(n);
  if (Number.isNaN(num)) return String(n);
  return num.toLocaleString();
}

export function humanizeKey(key) {
  if (key == null || key === '') return '—';
  return String(key)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

const CHANGE_CLASS_LABELS = {
  upsert_full: 'Full',
  upsert_partial: 'Partial',
  variables: 'Variables',
  delete: 'Delete',
};

export function changeClassLabel(changeClass) {
  if (changeClass == null || changeClass === '') return '—';
  return CHANGE_CLASS_LABELS[changeClass] || humanizeKey(changeClass);
}

/** NADA surveys.type -> nada-ai metadata_type. Null means this type cannot be ingested by idno. */
const DATASET_TO_METADATA_TYPE = {
  timeseries: 'indicator',
  timeseriesdb: 'indicator',
  indicator: 'indicator',
  document: 'document',
  geospatial: 'geospatial',
  survey: 'microdata',
  microdata: 'microdata',
};

export function metadataTypeFromDataType(type) {
  if (type == null || type === '') return null;
  return DATASET_TO_METADATA_TYPE[String(type).toLowerCase()] || null;
}
