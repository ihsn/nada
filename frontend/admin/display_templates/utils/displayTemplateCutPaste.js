import {
  findNodeContextWithParent,
  getSectionContainerKey,
  isArrayLike,
  isPropTreeSection,
  isFieldLayoutParent,
  isSectionLike,
  isCustomLayoutField,
  nodeChildrenArray,
  TID,
} from './displayTemplateTree';

/**
 * @param {object[]} rootItems
 * @param {string[]} tids
 */
export function areSiblingSelection(rootItems, tids) {
  if (!Array.isArray(tids) || !tids.length) return false;
  /** @type {object[]|null} */
  let siblings = null;
  for (const tid of tids) {
    const ctx = findNodeContextWithParent(rootItems, tid);
    if (!ctx) return false;
    if (siblings === null) siblings = ctx.siblings;
    else if (siblings !== ctx.siblings) return false;
  }
  return true;
}

/**
 * @param {object|null|undefined} targetNode
 * @returns {'before'|'after'|'into'}
 */
export function defaultPasteZone(targetNode) {
  if (!targetNode) return 'after';
  if (
    targetNode.type === 'section'
    || targetNode.type === 'section_container'
    || isPropTreeSection(targetNode)
    || targetNode.type === 'nested_array'
  ) {
    return 'into';
  }
  return 'after';
}

/**
 * @param {object[]} rootItems
 * @param {string} targetTid
 * @param {'before'|'after'|'into'} zone
 */
function resolveInsertTarget(rootItems, targetTid, zone) {
  const tgt = findNodeContextWithParent(rootItems, targetTid);
  if (!tgt) return null;

  if (zone === 'into') {
    const arr = nodeChildrenArray(tgt.node);
    if (!arr) return null;
    return {
      siblings: arr,
      insertIndex: arr.length,
      parentNode: tgt.node,
      parentKind: inferParentKind(tgt.node, arr),
    };
  }

  return {
    siblings: tgt.siblings,
    insertIndex: tgt.index + (zone === 'after' ? 1 : 0),
    parentNode: tgt.parentNode,
    parentKind: inferParentKind(tgt.parentNode, tgt.siblings),
  };
}

function inferParentKind(parentNode, siblings) {
  if (!parentNode) return 'root';
  if (isPropContext(parentNode, siblings)) return 'props';
  return 'items';
}

function isPropContext(parentNode, siblings) {
  if (!parentNode || !Array.isArray(siblings)) return false;
  if (isArrayLike(parentNode) && Array.isArray(parentNode.props) && parentNode.props === siblings) {
    return true;
  }
  if (isPropTreeSection(parentNode) && Array.isArray(parentNode.props) && parentNode.props === siblings) {
    return true;
  }
  return false;
}

function nodeKind(node, parentNode, siblings) {
  if (!node) return 'unknown';
  if (isPropContext(parentNode, siblings)) {
    if (isPropTreeSection(node)) return 'prop-section';
    return 'prop';
  }
  if (node.prop_key && !isPropTreeSection(node)) return 'prop';
  if (node.type === 'section' || node.type === 'section_container') return 'structural';
  if (isArrayLike(node)) return 'array';
  return 'field';
}

function getInsertionContainerKey(rootItems, drop) {
  if (!drop) return null;
  if (drop.parentKind === 'root' || !drop.parentNode) return '__root__';
  return getSectionContainerKey(rootItems, drop.parentNode[TID]);
}

/**
 * @param {object[]} rootItems
 * @param {object} node
 * @param {ReturnType<typeof resolveInsertTarget>} drop
 * @param {string|null|undefined} sourceContainerKey
 * @param {string|null|undefined} sourceParentTid
 * @param {'before'|'after'|'into'} zone
 */
function canInsertDetachedNode(rootItems, node, drop, sourceContainerKey, sourceParentTid, zone) {
  if (!node || !drop) return false;

  const kind = nodeKind(node, null, null);
  const srcScope = sourceContainerKey ?? '__root__';
  const destScope = getInsertionContainerKey(rootItems, drop);
  if (!destScope) return false;
  if (srcScope !== destScope && !isCustomLayoutField(node)) return false;

  if (drop.parentKind === 'props') {
    if (kind !== 'prop' && kind !== 'prop-section') return false;
    if (!drop.parentNode) return false;

    const targetAcceptsChildren =
      drop.parentNode.type === 'nested_array' || isPropTreeSection(drop.parentNode);

    if (zone === 'into') {
      if (!targetAcceptsChildren) return false;
      return true;
    }

    if (!sourceParentTid || !drop.parentNode[TID] || sourceParentTid !== drop.parentNode[TID]) {
      return false;
    }

    if (kind === 'prop') {
      const parentIsPropSection = isPropTreeSection(drop.parentNode);
      const arrayKey = parentIsPropSection
        ? drop.parentNode.prop_key
        : drop.parentNode.key || drop.parentNode.prop_key;
      const propKey =
        node.prop_key || (arrayKey && node.key ? `${arrayKey}.${node.key}` : '');
      if (arrayKey && propKey && !propKey.startsWith(`${arrayKey}.`)) return false;
    }

    return true;
  }

  if (kind === 'prop' || kind === 'prop-section') return false;

  if (drop.parentKind === 'items' || drop.parentKind === 'root') {
    if (kind === 'field' && !isFieldLayoutParent(drop.parentNode)) {
      return false;
    }
    if (zone === 'into') {
      if (isArrayLike(drop.parentNode)) return false;
      if (drop.parentNode && !isSectionLike(drop.parentNode)) return false;
      if (kind === 'field' && drop.parentNode?.type === 'section_container') {
        return false;
      }
    }
    if (isPropContext(drop.parentNode, drop.siblings)) return false;
    return true;
  }

  return false;
}

/**
 * @param {object[]} rootItems
 * @param {object[]} nodes
 * @param {string|null|undefined} sourceContainerKey
 * @param {string|null|undefined} sourceParentTid
 * @param {string} targetTid
 * @param {'before'|'after'|'into'} zone
 */
export function canInsertNodesAt(rootItems, nodes, sourceContainerKey, sourceParentTid, targetTid, zone) {
  if (!nodes?.length || !targetTid) return false;
  const drop = resolveInsertTarget(rootItems, targetTid, zone);
  if (!drop) return false;
  return nodes.every((node) =>
    canInsertDetachedNode(rootItems, node, drop, sourceContainerKey, sourceParentTid, zone)
  );
}

/**
 * @param {object[]} rootItems
 * @param {{ nodes: object[], containerKey?: string, parentTid?: string|null }} clipboard
 * @param {string} targetTid
 * @returns {'before'|'after'|'into'|null}
 */
export function resolvePasteZone(rootItems, clipboard, targetTid) {
  const tids = clipboard?.tids?.length
    ? clipboard.tids
    : clipboard?.nodes?.map((n) => n[TID]).filter(Boolean);
  if (!tids?.length || !targetTid) return null;
  if (tids.includes(targetTid)) return null;

  const live = prepareCutSelection(rootItems, tids);
  if (!live?.nodes?.length) return null;

  const tgt = findNodeContextWithParent(rootItems, targetTid);
  if (!tgt) return null;
  const preferred = defaultPasteZone(tgt.node);
  const order =
    preferred === 'into' ? ['into', 'after', 'before'] : ['after', 'before', 'into'];
  for (const zone of order) {
    if (
      canInsertNodesAt(
        rootItems,
        live.nodes,
        live.containerKey,
        live.parentTid ?? null,
        targetTid,
        zone
      )
    ) {
      return zone;
    }
  }
  return null;
}

/**
 * @param {object[]} rootItems
 * @param {string[]} tids
 * @returns {{ tids: string[], nodes: object[], containerKey: string, parentKind: string, parentTid: string|null }|null}
 */
export function prepareCutSelection(rootItems, tids) {
  if (!areSiblingSelection(rootItems, tids)) return null;

  /** @type {Array<NonNullable<ReturnType<typeof findNodeContextWithParent>>>} */
  const contexts = tids
    .map((tid) => findNodeContextWithParent(rootItems, tid))
    .filter(Boolean)
    .sort((a, b) => a.index - b.index);

  if (!contexts.length) return null;

  const first = contexts[0];
  const containerKey = getSectionContainerKey(rootItems, first.node[TID]) ?? '__root__';
  const parentKind = inferParentKind(first.parentNode, first.siblings);
  const parentTid = first.parentNode?.[TID] ?? null;
  const nodes = contexts.map((ctx) => ctx.node);
  const orderedTids = nodes.map((n) => n[TID]).filter(Boolean);

  return { tids: orderedTids, nodes, containerKey, parentKind, parentTid };
}

/**
 * @param {object[]} rootItems
 * @param {string[]} tids
 * @returns {object[]}
 */
export function detachNodesFromTree(rootItems, tids) {
  if (!areSiblingSelection(rootItems, tids)) return [];

  /** @type {Array<NonNullable<ReturnType<typeof findNodeContextWithParent>>>} */
  const contexts = tids
    .map((tid) => findNodeContextWithParent(rootItems, tid))
    .filter(Boolean)
    .sort((a, b) => b.index - a.index);

  /** @type {object[]} */
  const nodes = [];
  for (const ctx of contexts) {
    nodes.unshift(ctx.siblings.splice(ctx.index, 1)[0]);
  }
  return nodes;
}

/**
 * @param {object[]} rootItems
 * @param {string[]} tids
 * @returns {{ nodes: object[], containerKey: string, parentKind: string, parentTid: string|null }|null}
 * @deprecated Prefer prepareCutSelection + detachNodesFromTree + pasteNodesAt
 */
export function cutNodesFromTree(rootItems, tids) {
  const selection = prepareCutSelection(rootItems, tids);
  if (!selection) return null;
  const nodes = detachNodesFromTree(rootItems, selection.tids);
  if (!nodes.length) return null;
  return {
    nodes,
    containerKey: selection.containerKey,
    parentKind: selection.parentKind,
    parentTid: selection.parentTid,
  };
}

/**
 * @param {object[]} rootItems
 * @param {{ nodes: object[], containerKey?: string, parentTid?: string|null }} clipboard
 * @param {string} targetTid
 * @param {'before'|'after'|'into'|null} [zone]
 */
export function pasteNodesAt(rootItems, clipboard, targetTid, zone = null) {
  const nodes = clipboard?.nodes;
  if (!nodes?.length || !targetTid) return false;
  const pasteZone = zone || resolvePasteZone(rootItems, clipboard, targetTid);
  if (!pasteZone) return false;
  if (
    !canInsertNodesAt(
      rootItems,
      nodes,
      clipboard.containerKey,
      clipboard.parentTid ?? null,
      targetTid,
      pasteZone
    )
  ) {
    return false;
  }
  const drop = resolveInsertTarget(rootItems, targetTid, pasteZone);
  if (!drop) return false;
  drop.siblings.splice(drop.insertIndex, 0, ...nodes);
  return true;
}

/**
 * Remove flagged nodes from their current location and insert at the paste target.
 * @param {object[]} rootItems
 * @param {{ tids?: string[], nodes?: object[], containerKey?: string, parentTid?: string|null }} clipboard
 * @param {string} targetTid
 * @param {'before'|'after'|'into'|null} [zone]
 */
export function moveCutNodesAt(rootItems, clipboard, targetTid, zone = null) {
  const tids = clipboard?.tids?.length
    ? clipboard.tids
    : clipboard?.nodes?.map((n) => n[TID]).filter(Boolean);
  if (!tids?.length || !targetTid) return false;
  if (tids.includes(targetTid)) return false;

  const live = prepareCutSelection(rootItems, tids);
  if (!live?.nodes?.length) return false;

  const pasteZone = zone || resolvePasteZone(rootItems, live, targetTid);
  if (!pasteZone) return false;

  const nodes = detachNodesFromTree(rootItems, tids);
  if (!nodes.length) return false;

  return pasteNodesAt(
    rootItems,
    {
      nodes,
      containerKey: live.containerKey,
      parentTid: live.parentTid ?? null,
    },
    targetTid,
    pasteZone
  );
}
