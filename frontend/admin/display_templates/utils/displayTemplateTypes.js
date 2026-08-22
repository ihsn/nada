/** Map catalog / legacy data_type values to display group ids (ME template manager). */
export const DISPLAY_DATA_TYPE_ALIASES = {
  survey: 'microdata',
  microdata: 'microdata',
  timeseries: 'indicator',
  indicator: 'indicator',
  'timeseries-db': 'indicator-db',
  timeseriesdb: 'indicator-db',
  'indicator-db': 'indicator-db',
};

/** @type {Record<string, string>} */
const DATA_TYPE_ICON_NAMES = {
  microdata: 'mdi-database',
  survey: 'mdi-database',
  indicator: 'mdi-chart-timeline-variant',
  timeseries: 'mdi-chart-timeline-variant',
  'indicator-db': 'mdi-database',
  'timeseries-db': 'mdi-database',
  timeseriesdb: 'mdi-database',
  script: 'mdi-file-code-outline',
  geospatial: 'mdi-earth',
  document: 'mdi-file-document-outline',
  table: 'mdi-table',
  image: 'mdi-image',
  video: 'mdi-video',
  resource: 'mdi-link-variant',
};

/**
 * Canonical display order (matches metadata-editor template manager / editor_templates.php).
 * @type {Array<{ uid: string, label: string, matchKeys: string[], order: number }>}
 */
export const DISPLAY_TEMPLATE_TYPE_GROUPS = [
  { uid: 'microdata', label: 'Microdata', matchKeys: ['survey', 'microdata'], order: 10 },
  { uid: 'indicator', label: 'Indicator', matchKeys: ['timeseries', 'indicator'], order: 20 },
  {
    uid: 'indicator-db',
    label: 'Indicator database',
    matchKeys: ['timeseries-db', 'timeseriesdb', 'indicator-db'],
    order: 30,
  },
  { uid: 'script', label: 'Script', matchKeys: ['script'], order: 40 },
  { uid: 'geospatial', label: 'Geospatial', matchKeys: ['geospatial'], order: 50 },
  { uid: 'document', label: 'Document', matchKeys: ['document'], order: 60 },
  { uid: 'table', label: 'Table', matchKeys: ['table'], order: 70 },
  { uid: 'image', label: 'Image', matchKeys: ['image'], order: 80 },
  { uid: 'video', label: 'Video', matchKeys: ['video'], order: 90 },
  { uid: 'resource', label: 'External resource', matchKeys: ['resource'], order: 100 },
];

const KNOWN_GROUP_ORDER = Object.fromEntries(
  DISPLAY_TEMPLATE_TYPE_GROUPS.map((group) => [group.uid, group.order]),
);

/**
 * @param {string} type
 * @returns {string}
 */
export function normalizeDisplayDataType(type) {
  const key = String(type || '').toLowerCase();
  return DISPLAY_DATA_TYPE_ALIASES[key] || key;
}

/**
 * @param {string} type
 * @returns {string}
 */
export function dataTypeIcon(type) {
  const canonical = normalizeDisplayDataType(type);
  return DATA_TYPE_ICON_NAMES[canonical] || DATA_TYPE_ICON_NAMES[type] || 'mdi-form-select';
}

/**
 * @param {string} type
 * @returns {{ uid: string, label: string, matchKeys: string[], order?: number }|null}
 */
export function groupForDataType(type) {
  const key = String(type || '').toLowerCase();
  if (!key) return null;

  const canonical = normalizeDisplayDataType(key);
  return (
    DISPLAY_TEMPLATE_TYPE_GROUPS.find(
      (group) => group.uid === canonical || group.uid === key || group.matchKeys.includes(key),
    ) || null
  );
}

/**
 * @param {Array<{ uid?: string, label?: string, order?: number, isFallback?: boolean }>} groups
 * @returns {Array<object>}
 */
export function sortDisplayTemplateTypeGroups(groups) {
  return [...groups].sort((a, b) => {
    const ao = a.order ?? KNOWN_GROUP_ORDER[a.uid] ?? (a.isFallback ? 9000 : 9500);
    const bo = b.order ?? KNOWN_GROUP_ORDER[b.uid] ?? (b.isFallback ? 9000 : 9500);
    if (ao !== bo) return ao - bo;
    return String(a.label || a.uid || '').localeCompare(String(b.label || b.uid || ''), undefined, {
      sensitivity: 'base',
    });
  });
}

/**
 * @param {object[]} templates
 * @returns {object[]}
 */
export function buildDisplayTemplateTypeGroups(templates) {
  const groups = DISPLAY_TEMPLATE_TYPE_GROUPS.map((group) => ({ ...group }));
  const covered = new Set();
  groups.forEach((group) => {
    group.matchKeys.forEach((key) => covered.add(key));
    covered.add(group.uid);
  });

  (templates || []).forEach((row) => {
    const type = row?.data_type;
    if (!type || covered.has(type)) return;
    const known = groupForDataType(type);
    if (known) {
      known.matchKeys.forEach((key) => covered.add(key));
      covered.add(known.uid);
      return;
    }

    covered.add(type);
    groups.push({
      uid: type,
      label: formatDataTypeLabel(type),
      matchKeys: [type],
      isFallback: true,
      order: 9100,
    });
  });

  return sortDisplayTemplateTypeGroups(groups);
}

/**
 * @param {string} type
 * @returns {string}
 */
export function formatDataTypeLabel(type) {
  const group = groupForDataType(type);
  if (group) return group.label;
  return String(type || '')
    .replace(/-/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

/**
 * @param {object[]} rows
 * @returns {object[]}
 */
export function dedupeTemplatesByUid(rows) {
  const seen = new Set();
  return (rows || []).filter((row) => {
    const uid = row?.uid;
    if (!uid || seen.has(uid)) return false;
    seen.add(uid);
    return true;
  });
}

/**
 * @param {object} group
 * @param {object[]} templates
 * @returns {object[]}
 */
export function templatesForTypeGroup(group, templates) {
  if (!group) return [];
  const keys = group.matchKeys?.length ? group.matchKeys : [group.uid];
  const rows = (templates || []).filter((row) => row && keys.includes(row.data_type));
  return sortTemplatesForDisplay(rows);
}

/**
 * Stable row order within a type group: system cores first, then custom/imported
 * by last modified (newest first).
 * @param {object[]} rows
 * @returns {object[]}
 */
export function sortTemplatesForDisplay(rows) {
  return [...rows].sort((a, b) => {
    const aSystem = isSystemTemplateRow(a) ? 0 : 1;
    const bSystem = isSystemTemplateRow(b) ? 0 : 1;
    if (aSystem !== bSystem) return aSystem - bSystem;
    if (aSystem === 0) {
      return compareTemplateName(a, b);
    }
    const byUpdated = templateTimestamp(b.updated_at) - templateTimestamp(a.updated_at);
    if (byUpdated !== 0) return byUpdated;
    return compareTemplateName(a, b);
  });
}

function isSystemTemplateRow(row) {
  return row?.template_type === 'system' || !!row?.is_core;
}

function compareTemplateName(a, b) {
  return String(a?.name || '').localeCompare(String(b?.name || ''), undefined, { sensitivity: 'base' });
}

function templateTimestamp(value) {
  if (!value) return 0;
  const raw = String(value).trim();
  if (!raw) return 0;
  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const ms = Date.parse(normalized);
  return Number.isNaN(ms) ? 0 : ms;
}
