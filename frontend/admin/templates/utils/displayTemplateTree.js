/** @typedef {{ node_type?: string, key?: string, title?: string, sections?: object[], fields?: object[], source_type?: string, is_prop?: boolean, _tid?: string }} DisplayNode */

export const TID = '_tid';

/** Virtual tree row — catalogue record + layout summary */
export const VIRTUAL_DESCRIPTION_TID = '__template_description__';

/** Virtual root — wraps Description + all layout sections */
export const VIRTUAL_TEMPLATE_ROOT_TID = '__template__';

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
 * @param {object|string} raw
 * @returns {object}
 */
export function normalizeDisplayRoot(raw) {
  const o = typeof raw === 'string' ? JSON.parse(raw || '{}') : cloneJson(raw);
  if (!Array.isArray(o.sections)) {
    o.sections = [];
  }
  if (!o.type) {
    o.type = 'display_template';
  }
  return o;
}

/**
 * Children array for tree + mutations (sections, fields, or nested fields on array containers).
 * @param {DisplayNode|null|undefined} n
 * @returns {DisplayNode[]|null}
 */
export function nodeChildrenArray(n) {
  if (!n || typeof n !== 'object') {
    return null;
  }
  const t = n.node_type;
  if (t === 'section_group') {
    return Array.isArray(n.sections) ? n.sections : [];
  }
  if (t === 'section') {
    return Array.isArray(n.fields) ? n.fields : [];
  }
  if (t === 'field' && n.source_type === 'array') {
    if (!Array.isArray(n.fields)) {
      n.fields = [];
    }
    return n.fields;
  }
  return null;
}

/** @param {DisplayNode} n */
export function isContainerNode(n) {
  return nodeChildrenArray(n) !== null;
}

/** @param {DisplayNode} n */
export function nodeHasChildren(n) {
  const ch = nodeChildrenArray(n);
  return Array.isArray(ch) && ch.length > 0;
}

/** @param {DisplayNode} n */
export function nodeLabel(n) {
  if (!n) {
    return '';
  }
  const bit = n.title || n.key || n.node_type || 'Node';
  return String(bit);
}

/** @param {DisplayNode} n */
export function nodeIcon(n) {
  if (n?.is_prop) {
    return 'mdi-file-document-outline';
  }
  switch (n?.node_type) {
    case 'section_group':
      return 'mdi-dresser-outline';
    case 'section':
      return 'mdi-folder-outline';
    case 'field':
      if (n?.source_type === 'array') {
        return 'mdi-table-large';
      }
      return 'mdi-form-textbox';
    default:
      return 'mdi-shape-outline';
  }
}

/**
 * @param {DisplayNode[]} nodes
 */
export function ensureTreeIds(nodes) {
  if (!Array.isArray(nodes)) {
    return;
  }
  for (const n of nodes) {
    if (!n || typeof n !== 'object') {
      continue;
    }
    if (!n[TID]) {
      n[TID] = newTid();
    }
    const kids = nodeChildrenArray(n);
    if (kids && kids.length) {
      ensureTreeIds(kids);
    }
  }
}

/**
 * @param {unknown} value
 * @returns {unknown}
 */
export function stripTreeIds(value) {
  if (Array.isArray(value)) {
    return value.map(stripTreeIds);
  }
  if (value && typeof value === 'object') {
    /** @type {Record<string, unknown>} */
    const out = {};
    for (const [k, v] of Object.entries(value)) {
      if (k === TID) {
        continue;
      }
      out[k] = stripTreeIds(v);
    }
    return out;
  }
  return value;
}

/**
 * @param {DisplayNode[]} nodes
 * @param {string} tid
 * @param {DisplayNode|null} parentNode
 * @returns {{ node: DisplayNode, siblings: DisplayNode[], index: number, parentNode: DisplayNode|null }|null}
 */
export function findNodeContextWithParent(nodes, tid, parentNode = null) {
  if (!Array.isArray(nodes) || !tid) {
    return null;
  }
  for (let i = 0; i < nodes.length; i++) {
    const node = nodes[i];
    if (node && node[TID] === tid) {
      return { node, siblings: nodes, index: i, parentNode };
    }
    const nested = nodeChildrenArray(node);
    if (nested && nested.length) {
      const hit = findNodeContextWithParent(nested, tid, node);
      if (hit) {
        return hit;
      }
    }
  }
  return null;
}

/**
 * @param {DisplayNode[]} sections
 * @param {string} tid
 */
export function findNodeContext(sections, tid) {
  const x = findNodeContextWithParent(sections, tid);
  if (!x) return null;
  return { node: x.node, siblings: x.siblings, index: x.index };
}

/**
 * @param {DisplayNode[]} nodes
 * @param {string} ancestorTid
 * @param {string} candidateTid
 */
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

/**
 * Move a node by drag-drop. zone: 'before' | 'after' | 'into'
 * @param {DisplayNode[]} sections
 * @param {string} dragTid
 * @param {string} targetTid
 * @param {'before'|'after'|'into'} zone
 */
export function moveNode(sections, dragTid, targetTid, zone) {
  const src = findNodeContextWithParent(sections, dragTid);
  const tgt = findNodeContextWithParent(sections, targetTid);
  if (!src || !tgt || dragTid === targetTid) return false;
  if (isDescendantTid(sections, dragTid, targetTid)) return false;

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

  const tgt2 = findNodeContextWithParent(sections, targetTid);
  if (!tgt2) return false;

  let insertIndex = tgt2.index + (zone === 'after' ? 1 : 0);
  if (sameParent && srcIdx < tgtIdx) {
    insertIndex -= 1;
  }
  tgt2.siblings.splice(insertIndex, 0, removed);
  return true;
}

/**
 * @param {DisplayNode} node
 */
export function cloneSubtree(node) {
  const copy = cloneJson(node);
  const suffix = `_copy_${Math.random().toString(36).slice(2, 9)}`;
  function walk(n) {
    if (!n || typeof n !== 'object') return;
    n[TID] = newTid();
    if (typeof n.key === 'string' && n.key.length) {
      n.key = `${n.key}${suffix}`;
    }
    if (typeof n.path === 'string' && n.path.length) {
      n.path = n.key;
    }
    const ch = nodeChildrenArray(n);
    if (ch) {
      ch.forEach(walk);
    }
  }
  walk(copy);
  return copy;
}

export function newSectionGroup(key, title) {
  return {
    node_type: 'section_group',
    key: key || `section_group_${Date.now()}`,
    title: title || 'Section group',
    sections: [],
    [TID]: newTid(),
  };
}

export function newSection(key, title) {
  return {
    node_type: 'section',
    key: key || `section_${Date.now()}`,
    title: title || 'Section',
    fields: [],
    [TID]: newTid(),
  };
}

export function newLeafField(key, title) {
  return {
    node_type: 'field',
    key: key || `field_${Date.now()}`,
    path: key || `field_${Date.now()}`,
    title: title || 'New field',
    source_type: 'string',
    renderer: null,
    renderer_options: {},
    visibility_rules: [],
    [TID]: newTid(),
  };
}

/**
 * Drop zone from pointer position on row.
 * @param {DragEvent} e
 * @param {DisplayNode} targetNode
 */
/**
 * Nearest section_group ancestor for the selected node (or the node itself).
 * @param {DisplayNode[]} sections
 * @param {string} tid
 */
export function findNearestSectionGroup(sections, tid) {
  const ctx = findNodeContextWithParent(sections, tid);
  if (!ctx) return null;
  if (ctx.node.node_type === 'section_group') {
    return ctx.node;
  }
  let p = ctx.parentNode;
  while (p) {
    if (p.node_type === 'section_group') {
      return p;
    }
    const pc = findNodeContextWithParent(sections, p[TID]);
    p = pc ? pc.parentNode : null;
  }
  return null;
}

/**
 * Open all container rows in the tree UI.
 * @param {DisplayNode[]} nodes
 * @param {Record<string, boolean>} expandedObj
 */
export function expandAllContainers(nodes, expandedObj) {
  if (!Array.isArray(nodes) || !expandedObj) {
    return;
  }
  for (const n of nodes) {
    if (!n) continue;
    if (isContainerNode(n) && n[TID]) {
      expandedObj[n[TID]] = true;
    }
    const ch = nodeChildrenArray(n);
    if (ch && ch.length) {
      expandAllContainers(ch, expandedObj);
    }
  }
}

/**
 * Swap node with sibling above or below within the same parent list.
 * @param {DisplayNode[]} sections
 * @param {string} tid
 * @param {'up'|'down'} direction
 */
export function swapSiblingOrder(sections, tid, direction) {
  const ctx = findNodeContextWithParent(sections, tid);
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
