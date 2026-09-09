/** @typedef {{ type?: string, key?: string, title?: string, items?: object[], props?: object[], prop_key?: string, display_type?: string, display_options?: object, _tid?: string }} TemplateNode */

export const TID = '_tid';

export const VIRTUAL_DESCRIPTION_TID = '__template_description__';
export const VIRTUAL_TEMPLATE_ROOT_TID = '__template__';

/** Node types that hold layout children in `items[]`. */
const SECTION_TYPES = new Set(['section_container', 'section']);
/** Array-like types that hold column defs in `props[]`. */
const ARRAY_TYPES = new Set(['array', 'nested_array', 'simple_array']);

export const FIELD_TYPES = [
  'string',
  'number',
  'integer',
  'boolean',
  'text',
  'object',
  'array',
  'nested_array',
  'simple_array',
  'widget',
];

export const DISPLAY_TYPES = ['text', 'textarea', 'date', 'dropdown', 'dropdown-custom'];

/** Layout types offered when creating a custom display field. */
export const CUSTOM_FIELD_TYPES = [
  'string',
  'text',
  'number',
  'integer',
  'boolean',
  'date',
  'object',
  'array',
  'nested_array',
  'simple_array',
];

const ARRAY_CUSTOM_TYPES = new Set(['array', 'nested_array', 'simple_array']);

function customDisplayOptionsForType(layoutType) {
  /** @type {Record<string, unknown>} */
  const display_options = { custom: true };
  if (layoutType === 'date') {
    display_options.date_format = 'Y-m-d';
  } else if (layoutType === 'text') {
    display_options.format = 'richtext';
    display_options.linkify = false;
  } else if (layoutType !== 'object' && !ARRAY_CUSTOM_TYPES.has(layoutType)) {
    display_options.format = 'plain';
    display_options.linkify = false;
  }
  return display_options;
}

export function cloneJson(obj) {
  try {
    return JSON.parse(JSON.stringify(obj ?? {}));
  } catch {
    return {};
  }
}

export function newTid() {
  return typeof crypto !== 'undefined' && crypto.randomUUID
    ? `tid-${crypto.randomUUID()}`
    : `tid-${Math.random().toString(36).slice(2)}`;
}

/**
 * Normalize stored template_json to metadata-editor / legacy display shape.
 * Migrates deprecated { sections, node_type } format when present.
 * @param {object|string} raw
 * @returns {object}
 */
export function normalizeTemplateRoot(raw) {
  const o = typeof raw === 'string' ? JSON.parse(raw || '{}') : cloneJson(raw);
  if (Array.isArray(o.sections) && !Array.isArray(o.items)) {
    return migrateLegacySectionsRoot(o);
  }
  if (!Array.isArray(o.items)) {
    o.items = [];
  }
  if (!o.type) {
    o.type = 'template';
  }
  if (o.title == null) {
    o.title = '';
  }
  delete o.sections;
  delete o.data_type;
  delete o.description;
  return o;
}

function migrateLegacySectionsRoot(root) {
  return {
    type: 'template',
    title: root.title != null ? String(root.title) : '',
    items: migrateLegacySectionNodes(root.sections || []),
  };
}

function migrateLegacySectionNodes(nodes) {
  const out = [];
  for (const n of nodes || []) {
    if (!n || typeof n !== 'object') continue;
    if (n.node_type === 'section_group') {
      out.push({
        type: 'section_container',
        key: n.key || `section_container_${out.length}`,
        title: n.title || 'Section container',
        items: migrateLegacySectionNodes(n.sections || []),
      });
      continue;
    }
    if (n.node_type === 'section') {
      out.push({
        type: 'section',
        key: n.key || `section_${out.length}`,
        title: n.title || 'Section',
        items: migrateLegacyFieldNodes(n.fields || []),
      });
      continue;
    }
    if (n.node_type === 'field') {
      out.push(migrateLegacyFieldNode(n));
    }
  }
  return out;
}

function migrateLegacyFieldNodes(fields) {
  return (fields || []).map((f) => migrateLegacyFieldNode(f));
}

function migrateLegacyFieldNode(n) {
  const type =
    n.source_type === 'array'
      ? 'array'
      : n.source_type === 'string' || !n.source_type
        ? 'string'
        : String(n.source_type);
  const row = {
    type,
    key: n.key || n.path || `field_${Date.now()}`,
    title: n.title || n.label_override || n.key || 'Field',
    display_type: 'text',
  };
  if (Array.isArray(n.fields) && n.fields.length) {
    row.props = n.fields.map((p) => ({
      key: String(p.key || '').split('.').pop() || 'column',
      title: p.title || p.key || 'Column',
      type: p.source_type === 'array' ? 'array' : 'string',
      prop_key: p.path || p.key,
      display_type: 'text',
    }));
  }
  return row;
}

/** @param {TemplateNode|null|undefined} n */
export function isPropNode(n) {
  return !!(n && typeof n.prop_key === 'string' && n.prop_key.length > 0);
}

/** Section node living inside a nested_array props[] tree (ME props-tree). */
export function isPropTreeSection(n) {
  return isSectionLike(n) && isPropNode(n);
}

/**
 * Scope key for array / nested_array add-fields (layout field key or prop_key).
 * @param {TemplateNode|null|undefined} node
 * @returns {string}
 */
export function resolveArrayPropScope(node) {
  if (!node) return '';
  if (node.type === 'nested_array' && !isPropTreeSection(node) && !node.prop_key) {
    return typeof node.key === 'string' ? node.key : '';
  }
  if (typeof node.prop_key === 'string' && node.prop_key.length) {
    return node.prop_key;
  }
  return typeof node.key === 'string' ? node.key : '';
}

/**
 * @param {TemplateNode|null|undefined} n
 * @returns {TemplateNode[]|null}
 */
export function nodeChildrenArray(n) {
  if (!n || typeof n !== 'object') {
    return null;
  }
  const t = n.type;
  if (SECTION_TYPES.has(t)) {
    // Layout sections use items[]; sections inside nested_array props use props[].
    if (isPropNode(n)) {
      if (!Array.isArray(n.props)) {
        n.props = [];
      }
      return n.props;
    }
    if (!Array.isArray(n.items)) {
      n.items = [];
    }
    return n.items;
  }
  if (ARRAY_TYPES.has(t)) {
    if (!Array.isArray(n.props)) {
      n.props = [];
    }
    return n.props;
  }
  if (Array.isArray(n.items) && n.items.length > 0 && (SECTION_TYPES.has(t) || !t)) {
    return n.items;
  }
  return null;
}

/** @param {TemplateNode} n */
export function isContainerNode(n) {
  return nodeChildrenArray(n) !== null;
}

/** @param {TemplateNode} n */
export function nodeHasChildren(n) {
  const ch = nodeChildrenArray(n);
  return Array.isArray(ch) && ch.length > 0;
}

/** @param {TemplateNode} n */
export function nodeLabel(n) {
  if (!n) return '';
  const bit = n.title || n.key || n.type || 'Node';
  return String(bit);
}

/** @param {TemplateNode} n */
export function nodeIcon(n) {
  if (isPropTreeSection(n)) {
    return 'mdi-folder-outline';
  }
  switch (n?.type) {
    case 'section_container':
      return 'mdi-dresser-outline';
    case 'section':
      return 'mdi-folder-outline';
    case 'nested_array':
      return 'mdi-file-tree-outline';
    case 'array':
      return 'mdi-table-large';
    case 'simple_array':
      return 'mdi-format-list-bulleted';
    case 'object':
      return 'mdi-code-json';
    case 'widget':
      return 'mdi-puzzle-outline';
    case 'string':
    case 'text':
    case 'number':
    case 'integer':
    case 'boolean':
      return 'mdi-file-document-outline';
    default:
      break;
  }
  if (isPropNode(n)) {
    return 'mdi-file-document-outline';
  }
  return 'mdi-shape-outline';
}

/** @param {TemplateNode} n */
export function isSectionLike(n) {
  return SECTION_TYPES.has(n?.type);
}

/**
 * Layout fields may only live under a section (not section_container or template root).
 * @param {TemplateNode|null|undefined} parentNode
 */
export function isFieldLayoutParent(parentNode) {
  return parentNode?.type === 'section';
}

/**
 * Preview tree: hide section_container nodes and promote their children (sections stay nested).
 * @param {TemplateNode[]|null|undefined} items
 * @returns {TemplateNode[]}
 */
export function flattenPreviewTreeItems(items) {
  if (!Array.isArray(items)) return [];
  /** @type {TemplateNode[]} */
  const out = [];
  for (const item of items) {
    if (!item || typeof item !== 'object') continue;
    if (item.type === 'section_container') {
      out.push(...flattenPreviewTreeItems(item.items));
      continue;
    }
    out.push(item);
  }
  return out;
}

/** @param {TemplateNode} n */
export function isArrayLike(n) {
  return ARRAY_TYPES.has(n?.type);
}

/** @param {TemplateNode} n */
export function isLeafField(n) {
  if (!n || typeof n !== 'object') return false;
  return !isSectionLike(n) && !isArrayLike(n);
}

/** @param {TemplateNode} n */
export function nodeSearchText(n) {
  if (!n) return '';
  return [n.title, n.key, n.prop_key, n.type].filter(Boolean).join(' ').toLowerCase();
}

/** @param {TemplateNode} n @param {string} query normalized lowercase */
export function nodeMatchesTreeSearch(n, query) {
  if (!query) return true;
  return nodeSearchText(n).includes(query);
}

/** @param {TemplateNode} n @param {string} query normalized lowercase */
export function subtreeMatchesTreeSearch(n, query) {
  if (!query) return true;
  if (nodeMatchesTreeSearch(n, query)) return true;
  const kids = nodeChildrenArray(n);
  if (!kids?.length) return false;
  return kids.some((c) => subtreeMatchesTreeSearch(c, query));
}

export function ensureTreeIds(nodes) {
  if (!Array.isArray(nodes)) return;
  for (const n of nodes) {
    if (!n || typeof n !== 'object') continue;
    if (!n[TID]) n[TID] = newTid();
    const kids = nodeChildrenArray(n);
    if (kids && kids.length) ensureTreeIds(kids);
  }
}

export function stripTreeIds(value) {
  if (Array.isArray(value)) {
    return value.map(stripTreeIds);
  }
  if (value && typeof value === 'object') {
    /** @type {Record<string, unknown>} */
    const out = {};
    for (const [k, v] of Object.entries(value)) {
      if (k === TID) continue;
      out[k] = stripTreeIds(v);
    }
    return out;
  }
  return value;
}

export function findNodeContextWithParent(nodes, tid, parentNode = null) {
  if (!Array.isArray(nodes) || !tid) return null;
  for (let i = 0; i < nodes.length; i++) {
    const node = nodes[i];
    if (node && node[TID] === tid) {
      return { node, siblings: nodes, index: i, parentNode };
    }
    const nested = nodeChildrenArray(node);
    if (nested && nested.length) {
      const hit = findNodeContextWithParent(nested, tid, node);
      if (hit) return hit;
    }
  }
  return null;
}

export function findNodeContext(nodes, tid) {
  const x = findNodeContextWithParent(nodes, tid);
  if (!x) return null;
  return { node: x.node, siblings: x.siblings, index: x.index };
}

export function isDescendantTid(nodes, ancestorTid, candidateTid) {
  const ctx = findNodeContextWithParent(nodes, ancestorTid);
  if (!ctx) return false;
  const ch = nodeChildrenArray(ctx.node);
  if (!ch || !ch.length) return false;
  return walkContainsTid(ch, candidateTid);
}

function walkContainsTid(nodes, tid) {
  for (const n of nodes) {
    if (n && n[TID] === tid) return true;
    const c = nodeChildrenArray(n);
    if (c && walkContainsTid(c, tid)) return true;
  }
  return false;
}

export function moveNode(items, dragTid, targetTid, zone) {
  const src = findNodeContextWithParent(items, dragTid);
  const tgt = findNodeContextWithParent(items, targetTid);
  if (!src || !tgt || dragTid === targetTid) return false;
  if (isDescendantTid(items, dragTid, targetTid)) return false;

  const removed = src.siblings[src.index];

  if (zone === 'into') {
    const arr = nodeChildrenArray(tgt.node);
    if (!arr) return false;
    src.siblings.splice(src.index, 1);
    arr.push(removed);
    return true;
  }

  const sameParent = src.siblings === tgt.siblings;
  const srcIdx = src.index;
  const tgtIdx = tgt.index;
  src.siblings.splice(srcIdx, 1);

  const tgt2 = findNodeContextWithParent(items, targetTid);
  if (!tgt2) return false;

  let insertIndex = tgt2.index + (zone === 'after' ? 1 : 0);
  if (sameParent && srcIdx < tgtIdx) insertIndex -= 1;
  tgt2.siblings.splice(insertIndex, 0, removed);
  return true;
}

export function cloneSubtree(node) {
  const copy = cloneJson(node);
  const suffix = `_copy_${Math.random().toString(36).slice(2, 9)}`;
  function walk(n, parentKey) {
    if (!n || typeof n !== 'object') return;
    n[TID] = newTid();
    if (typeof n.key === 'string' && n.key.length && !isPropNode(n)) {
      n.key = `${n.key}${suffix}`;
    }
    if (isPropNode(n) && parentKey) {
      const col = n.key || 'column';
      n.prop_key = `${parentKey}.${col}${suffix}`;
    }
    const ch = nodeChildrenArray(n);
    const pk = typeof n.key === 'string' ? n.key : parentKey;
    if (ch) ch.forEach((c) => walk(c, ARRAY_TYPES.has(n.type) ? pk : parentKey));
  }
  walk(copy, null);
  return copy;
}

export function newSectionContainer(key, title) {
  return {
    type: 'section_container',
    key: key || `section_container_${Date.now()}`,
    title: title || 'Section container',
    items: [],
    [TID]: newTid(),
  };
}

export function newSection(key, title) {
  return {
    type: 'section',
    key: key || `section_${Date.now()}`,
    title: title || 'Section',
    items: [],
    [TID]: newTid(),
  };
}

export function newWidgetNode(title = 'Widget') {
  return {
    type: 'widget',
    key: `widget_${Date.now()}`,
    title: title || 'Widget',
    display_options: {},
    [TID]: newTid(),
  };
}

/**
 * @param {object|null|undefined} node
 */
export function isCustomLayoutField(node) {
  if (!node || typeof node !== 'object') return false;
  return node.display_options?.custom === true;
}

/**
 * Custom layout field (not a core DDI key). Placeable in any section.
 * @param {string} key
 * @param {string} title
 * @param {string} type
 */
export function newCustomField(key, title, type = 'string') {
  const layoutType = CUSTOM_FIELD_TYPES.includes(type) ? type : 'string';
  /** @type {Record<string, unknown>} */
  const node = {
    type: layoutType === 'date' ? 'string' : layoutType,
    key: key || `field_${Date.now()}`,
    title: title || 'New field',
    display_options: customDisplayOptionsForType(layoutType),
    [TID]: newTid(),
  };
  if (layoutType === 'array' || layoutType === 'nested_array') {
    node.props = [];
  }
  return node;
}

/**
 * Custom column on an array / nested_array (or prop-tree section).
 * @param {string} parentKey array field key or parent prop_key
 * @param {string} columnKey simple identifier
 * @param {string} title
 * @param {string} type
 */
export function newCustomArrayProp(parentKey, columnKey, title, type = 'string') {
  const layoutType = CUSTOM_FIELD_TYPES.includes(type) ? type : 'string';
  const pkey = columnKey || `column_${Date.now()}`;
  /** @type {Record<string, unknown>} */
  const node = {
    key: pkey,
    title: title || 'Column',
    type: layoutType === 'date' ? 'string' : layoutType,
    prop_key: `${parentKey}.${pkey}`,
    display_options: customDisplayOptionsForType(layoutType),
    [TID]: newTid(),
  };
  if (layoutType === 'array' || layoutType === 'nested_array') {
    node.props = [];
  }
  return node;
}

function shortKeySuffix() {
  return Math.random().toString(36).slice(2, 8);
}

/**
 * Unused additional.* key for an untitled custom field.
 * @param {{ fieldKeys?: Set<string>, layoutKeys?: Set<string> }|null|undefined} used
 * @param {Record<string, unknown>|Set<string>|null|undefined} coreFields
 */
export function uniqueCustomFieldKey(used, coreFields) {
  const fieldKeys = used?.fieldKeys || new Set();
  const layoutKeys = used?.layoutKeys || new Set();
  const core =
    coreFields instanceof Set
      ? coreFields
      : new Set(Object.keys(coreFields || {}));
  for (let i = 0; i < 40; i++) {
    const key = `additional.untitled_${shortKeySuffix()}`;
    if (!fieldKeys.has(key) && !layoutKeys.has(key) && !core.has(key)) return key;
  }
  return `additional.untitled_${Date.now()}`;
}

/**
 * Unused simple column key under an array scope.
 * @param {string} scope
 * @param {{ propKeys?: Set<string> }|null|undefined} used
 * @param {Record<string, unknown>|Set<string>|null|undefined} coreProps
 */
export function uniqueCustomColumnKey(scope, used, coreProps) {
  const propKeys = used?.propKeys || new Set();
  const core = coreProps instanceof Set ? coreProps : new Set(Object.keys(coreProps || {}));
  const prefix = `${scope}.`;
  for (let i = 0; i < 40; i++) {
    const col = `untitled_${shortKeySuffix()}`;
    const propKey = prefix + col;
    if (!propKeys.has(propKey) && !core.has(propKey)) return col;
  }
  return `untitled_${Date.now()}`;
}

export function findNearestSection(items, tid) {
  const ctx = findNodeContextWithParent(items, tid);
  if (!ctx) return null;
  if (ctx.node.type === 'section' && !isPropTreeSection(ctx.node)) return ctx.node;
  let p = ctx.parentNode;
  while (p) {
    if (p.type === 'section' && !isPropTreeSection(p)) return p;
    const pc = findNodeContextWithParent(items, p[TID]);
    p = pc ? pc.parentNode : null;
  }
  return null;
}

export function newLeafField(key, title, type = 'string') {
  const format = type === 'text' ? 'richtext' : 'plain';
  /** @type {Record<string, unknown>} */
  const display_options = { format, linkify: false };
  if (type === 'date') {
    display_options.date_format = 'Y-m-d';
    delete display_options.format;
    delete display_options.linkify;
  }
  return {
    type: type === 'date' ? 'string' : type,
    key: key || `field_${Date.now()}`,
    title: title || 'New field',
    display_options,
    [TID]: newTid(),
  };
}

export function newArrayProp(parentKey, columnKey, title) {
  const pkey = columnKey || `column_${Date.now()}`;
  return {
    key: pkey,
    title: title || 'Column',
    type: 'string',
    prop_key: `${parentKey}.${pkey}`,
    display_options: {
      format: 'plain',
      linkify: false,
    },
    [TID]: newTid(),
  };
}

/** New section node for nested_array props[] (ME props-tree). */
export function newPropTreeSection(scopeKey, title) {
  const key = `section-${Date.now()}`;
  const scope = String(scopeKey || '').replace(/\.$/, '');
  return {
    key,
    prop_key: scope ? `${scope}.${key}` : key,
    title: title || 'Untitled',
    type: 'section',
    props: [],
    help_text: '',
    [TID]: newTid(),
  };
}

/**
 * Scope key for a new prop-tree section (parent nested_array or prop section).
 * @param {TemplateNode[]} rootItems
 * @param {string} tid
 */
export function getPropTreeSectionScope(rootItems, tid) {
  const ctx = findNodeContextWithParent(rootItems, tid);
  if (!ctx) return '';

  if (isPropTreeSection(ctx.node) && Array.isArray(ctx.node.props)) {
    return ctx.node.prop_key || '';
  }

  if (ctx.parentNode) {
    if (isPropTreeSection(ctx.parentNode)) {
      return ctx.parentNode.prop_key || '';
    }
    if (ctx.parentNode.type === 'nested_array') {
      return ctx.parentNode.key || '';
    }
  }

  if (ctx.node.type === 'nested_array') {
    return ctx.node.key || '';
  }

  let walk = ctx;
  while (walk?.parentNode) {
    if (walk.parentNode.type === 'nested_array') {
      return walk.parentNode.key || '';
    }
    const parentCtx = findNodeContextWithParent(rootItems, walk.parentNode[TID]);
    walk = parentCtx;
  }

  return '';
}

export function findNearestSectionContainer(items, tid) {
  const ctx = findNodeContextWithParent(items, tid);
  if (!ctx) return null;
  if (ctx.node.type === 'section_container') return ctx.node;
  let p = ctx.parentNode;
  while (p) {
    if (p.type === 'section_container') return p;
    const pc = findNodeContextWithParent(items, p[TID]);
    p = pc ? pc.parentNode : null;
  }
  return null;
}

/**
 * section_container key enclosing a layout-tree node, or "__root__" when none.
 * @param {TemplateNode[]} rootItems
 * @param {string} tid
 * @returns {string|null}
 */
export function getSectionContainerKey(rootItems, tid) {
  if (!tid || !Array.isArray(rootItems)) return null;

  let ctx = findNodeContextWithParent(rootItems, tid);
  while (ctx) {
    if (ctx.node?.type === 'section_container' && typeof ctx.node.key === 'string') {
      return ctx.node.key;
    }
    if (!ctx.parentNode) {
      return '__root__';
    }
    ctx = findNodeContextWithParent(rootItems, ctx.parentNode[TID]);
  }

  return '__root__';
}

export function expandAllContainers(nodes, expandedObj) {
  if (!Array.isArray(nodes) || !expandedObj) return;
  for (const n of nodes) {
    if (!n) continue;
    if (isContainerNode(n) && n[TID]) expandedObj[n[TID]] = true;
    const ch = nodeChildrenArray(n);
    if (ch && ch.length) expandAllContainers(ch, expandedObj);
  }
}

export function swapSiblingOrder(items, tid, direction) {
  const ctx = findNodeContextWithParent(items, tid);
  if (!ctx) return false;
  const { siblings, index } = ctx;
  const j = direction === 'up' ? index - 1 : index + 1;
  if (j < 0 || j >= siblings.length) return false;
  const tmp = siblings[index];
  siblings[index] = siblings[j];
  siblings[j] = tmp;
  return true;
}

export function dropZoneFromPointer(e, targetNode) {
  const el = /** @type {HTMLElement} */ (e.currentTarget);
  const rect = el.getBoundingClientRect();
  const y = e.clientY - rect.top;
  const h = Math.max(rect.height, 1);
  const ratio = y / h;
  if (ratio < 0.28) return 'before';
  if (ratio > 0.72) return 'after';
  if (isContainerNode(targetNode)) return 'into';
  return 'after';
}

/**
 * Overlay identity: prop_key when set, else key.
 * @param {TemplateNode|null|undefined} n
 * @returns {string}
 */
export function translationKeyForNode(n) {
  if (!n || typeof n !== 'object') return '';
  const propKey = String(n.prop_key || '').trim();
  if (propKey) return propKey;
  return String(n.key || '').trim();
}

/**
 * Rows for the Translations tab: key | source title.
 * @param {object|null|undefined} root
 * @returns {{ key: string, title: string, type: string, kind: string }[]}
 */
export function collectTranslationRows(root) {
  /** @type {{ key: string, title: string, type: string, kind: string }[]} */
  const rows = [];
  const seen = new Set();

  /** @param {TemplateNode} node */
  function add(node) {
    const key = translationKeyForNode(node);
    if (!key || seen.has(key)) return;
    seen.add(key);
    const t = String(node.type || '');
    let kind = 'field';
    if (t === 'section') kind = 'section';
    else if (t === 'section_container') kind = 'container';
    else if (t === 'widget') kind = 'widget';
    else if (String(node.prop_key || '').trim()) kind = 'prop';
    rows.push({
      key,
      title: node.title != null ? String(node.title) : '',
      type: t,
      kind,
    });
  }

  /** @param {TemplateNode[]|null|undefined} nodes */
  function walk(nodes) {
    for (const n of nodes || []) {
      if (!n || typeof n !== 'object') continue;
      add(n);
      if (Array.isArray(n.items)) walk(n.items);
      if (Array.isArray(n.props)) walk(n.props);
    }
  }

  walk(root?.items);
  return rows;
}

/** @deprecated use normalizeTemplateRoot */
export const normalizeDisplayRoot = normalizeTemplateRoot;

/** @deprecated use newSectionContainer */
export const newSectionGroup = newSectionContainer;

/** @deprecated use findNearestSectionContainer */
export const findNearestSectionGroup = findNearestSectionContainer;
