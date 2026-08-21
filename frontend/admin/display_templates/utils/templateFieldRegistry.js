import {
  cloneJson,
  findNodeContextWithParent,
  getSectionContainerKey,
  isArrayLike,
  isFieldLayoutParent,
  isPropNode,
  isPropTreeSection,
  isSectionLike,
  isCustomLayoutField,
  moveNode,
  nodeChildrenArray,
  newTid,
  resolveArrayPropScope,
  TID,
} from './displayTemplateTree';
import { validateDisplayLayout } from './displayLayoutValidation';
import { sanitizeDisplayLayoutFieldNode } from './displayFieldOptions';

export { getSectionContainerKey };

/** @typedef {{ key: string, title: string, type: string, node: object, parentKey?: string|null, kind: 'field'|'prop'|'container'|'section' }} RegistryPart */
/** @typedef {{ key: string, message: string }} ValidationIssue */
/** @typedef {{ valid: boolean, errors: ValidationIssue[], warnings: ValidationIssue[] }} ValidationResult */

const STRUCTURAL_TYPES = new Set([
  'section',
  'section_container',
  'template',
  'template_root',
  'template_description',
]);

const KEY_PATTERN = /^[a-zA-Z0-9:_-]+$/;

/**
 * @param {object|null|undefined} coreTemplate
 * @returns {{ fields: Record<string, object>, props: Record<string, object>, hasCore: boolean }}
 */
export function buildTemplateFieldRegistry(coreTemplate) {
  /** @type {Record<string, object>} */
  const fields = {};
  /** @type {Record<string, object>} */
  const props = {};

  const root = normalizeCoreRoot(coreTemplate);
  if (!root || !Array.isArray(root.items)) {
    return { fields, props, hasCore: false };
  }

  walkCoreNodes(root.items, fields, props, '__root__');
  return { fields, props, hasCore: Object.keys(fields).length > 0 || Object.keys(props).length > 0 };
}

function normalizeCoreRoot(coreTemplate) {
  if (!coreTemplate || typeof coreTemplate !== 'object') return null;
  if (Array.isArray(coreTemplate.items)) return coreTemplate;
  if (coreTemplate.template && Array.isArray(coreTemplate.template.items)) {
    return coreTemplate.template;
  }
  return null;
}

/**
 * @param {object[]} items
 * @param {Record<string, object>} fields
 * @param {Record<string, object>} props
 * @param {string} containerKey
 */
function walkCoreNodes(items, fields, props, containerKey) {
  for (const item of items || []) {
    if (!item || typeof item !== 'object') continue;

    let activeContainer = containerKey;
    if (item.type === 'section_container' && typeof item.key === 'string' && item.key.length) {
      activeContainer = item.key;
    }

    if (typeof item.key === 'string' && item.key.length) {
      if (!isStructuralType(item.type)) {
        const row = stripRuntimeIds(cloneJson(item));
        row._containerKey = activeContainer;
        fields[item.key] = row;
      }
    }

    if (isArrayLike(item) && Array.isArray(item.props)) {
      walkCorePropNodes(item.props, item.key, fields, props);
    }

    if (Array.isArray(item.items) && item.items.length) {
      walkCoreNodes(item.items, fields, props, activeContainer);
    }
  }
}

/**
 * Walk array props[] trees, including sections and nested arrays inside nested_array.
 * @param {object[]} propItems
 * @param {string|null|undefined} parentArrayKey
 * @param {Record<string, object>} fields
 * @param {Record<string, object>} props
 */
function walkCorePropNodes(propItems, parentArrayKey, fields, props) {
  for (const prop of propItems || []) {
    if (!prop || typeof prop !== 'object') continue;

    const propKey =
      prop.prop_key ||
      (parentArrayKey && prop.key ? `${parentArrayKey}.${prop.key}` : '');
    if (propKey) {
      props[propKey] = stripRuntimeIds(cloneJson(prop));
    }

    if (isPropTreeSection(prop) && Array.isArray(prop.props)) {
      walkCorePropNodes(prop.props, parentArrayKey, fields, props);
      continue;
    }

    if (isArrayLike(prop) && Array.isArray(prop.props)) {
      const arrayKey = prop.prop_key || propKey || parentArrayKey;
      walkCorePropNodes(prop.props, arrayKey, fields, props);
    }
  }
}

function stripRuntimeIds(node) {
  if (Array.isArray(node)) {
    return node.map(stripRuntimeIds);
  }
  if (!node || typeof node !== 'object') return node;
  /** @type {Record<string, unknown>} */
  const out = {};
  for (const [k, v] of Object.entries(node)) {
    if (k === TID || k === '_containerKey' || k === '_scopeKey') continue;
    out[k] = stripRuntimeIds(v);
  }
  return out;
}

/** @param {string|undefined} type */
export function isStructuralType(type) {
  return STRUCTURAL_TYPES.has(type || '');
}

/** @param {object|null|undefined} node */
export function isRegistryFieldNode(node) {
  return !!(node && typeof node.key === 'string' && node.key.length && !isStructuralType(node.type));
}

/**
 * Collect field keys, prop keys, and layout (section/container) keys used in the user tree.
 * @param {object[]} items
 * @returns {{ fieldKeys: Set<string>, propKeys: Set<string>, layoutKeys: Set<string> }}
 */
export function collectUsedKeys(items) {
  /** @type {Set<string>} */
  const fieldKeys = new Set();
  /** @type {Set<string>} */
  const propKeys = new Set();
  /** @type {Set<string>} */
  const layoutKeys = new Set();
  walkUsedKeys(items, fieldKeys, propKeys, layoutKeys);
  return { fieldKeys, propKeys, layoutKeys };
}

function walkUsedKeys(items, fieldKeys, propKeys, layoutKeys) {
  for (const item of items || []) {
    if (!item || typeof item !== 'object') continue;

    if (typeof item.key === 'string' && item.key.length) {
      layoutKeys.add(item.key);
      if (!isStructuralType(item.type)) {
        fieldKeys.add(item.key);
      }
    }

    if (isArrayLike(item) && Array.isArray(item.props)) {
      walkUsedPropNodes(item.props, item.key, fieldKeys, propKeys);
    }

    if (Array.isArray(item.items) && item.items.length) {
      walkUsedKeys(item.items, fieldKeys, propKeys, layoutKeys);
    }
  }
}

function walkUsedPropNodes(propItems, parentArrayKey, fieldKeys, propKeys) {
  for (const prop of propItems || []) {
    if (!prop || typeof prop !== 'object') continue;

    const pk =
      prop.prop_key ||
      (parentArrayKey && prop.key ? `${parentArrayKey}.${prop.key}` : '');
    if (pk) propKeys.add(pk);

    if (isPropTreeSection(prop) && Array.isArray(prop.props)) {
      walkUsedPropNodes(prop.props, parentArrayKey, fieldKeys, propKeys);
      continue;
    }

    if (isArrayLike(prop) && Array.isArray(prop.props)) {
      const arrayKey = prop.prop_key || pk || parentArrayKey;
      walkUsedPropNodes(prop.props, arrayKey, fieldKeys, propKeys);
    }
  }
}

/**
 * @param {{ fields: Record<string, object>, props: Record<string, object> }} registry
 * @param {{ fieldKeys: Set<string>, propKeys: Set<string> }} used
 * @returns {RegistryPart[]}
 */
export function getAvailableParts(registry, used) {
  /** @type {RegistryPart[]} */
  const out = [];

  for (const [key, node] of Object.entries(registry.fields || {})) {
    if (used.fieldKeys.has(key)) continue;
    out.push({
      kind: 'field',
      key,
      title: String(node.title || node.label || key),
      type: String(node.type || 'string'),
      node,
      parentKey: null,
    });
  }

  for (const [propKey, node] of Object.entries(registry.props || {})) {
    if (used.propKeys.has(propKey)) continue;
    const parentKey = propKey.includes('.') ? propKey.slice(0, propKey.lastIndexOf('.')) : null;
    if (parentKey && !used.fieldKeys.has(parentKey)) {
      // Parent array must be in layout before individual columns are listed.
      continue;
    }
    out.push({
      kind: 'prop',
      key: propKey,
      title: String(node.title || node.label || propKey),
      type: String(node.type || 'string'),
      node,
      parentKey,
    });
  }

  out.sort((a, b) => a.title.localeCompare(b.title, undefined, { sensitivity: 'base' }));
  return out;
}

/**
 * @param {object[]} items
 * @param {{ fields: Record<string, object>, props: Record<string, object>, hasCore?: boolean }} registry
 * @param {{ requireCore?: boolean }} [options]
 * @returns {ValidationResult}
 */
export function validateTree(items, registry, options = {}) {
  /** @type {ValidationIssue[]} */
  const errors = [];
  /** @type {ValidationIssue[]} */
  const warnings = [];
  /** @type {Record<string, boolean>} */
  const seenFieldKeys = {};
  /** @type {Record<string, boolean>} */
  const seenPropKeys = {};

  const requireCore = options.requireCore !== false && registry?.hasCore;

  walkValidate(items, registry, requireCore, seenFieldKeys, seenPropKeys, errors, warnings, null, 'items', '__root__', null);

  const display = validateDisplayLayout(items);
  errors.push(...display.errors);
  warnings.push(...display.warnings);

  return { valid: errors.length === 0, errors, warnings };
}

function walkValidate(
  items,
  registry,
  requireCore,
  seenFieldKeys,
  seenPropKeys,
  errors,
  warnings,
  parentArrayKey,
  childKind,
  containerKey = '__root__'
) {
  if (!Array.isArray(items)) return;

  for (const item of items) {
    if (!item || typeof item !== 'object') continue;

    if (childKind === 'props') {
      validatePropNode(item, parentArrayKey, registry, requireCore, seenPropKeys, errors, warnings);

      if (isPropTreeSection(item) && Array.isArray(item.props) && item.props.length) {
        walkValidate(
          item.props,
          registry,
          requireCore,
          seenFieldKeys,
          seenPropKeys,
          errors,
          warnings,
          parentArrayKey,
          'props'
        );
      } else if (isArrayLike(item) && Array.isArray(item.props) && item.props.length) {
        const arrayKey = resolveArrayPropKey(item, parentArrayKey);
        walkValidate(
          item.props,
          registry,
          requireCore,
          seenFieldKeys,
          seenPropKeys,
          errors,
          warnings,
          arrayKey,
          'props'
        );
      }
      continue;
    }

    let activeContainer = containerKey;
    if (item.type === 'section_container' && typeof item.key === 'string' && item.key.length) {
      activeContainer = item.key;
    }

    if (typeof item.key === 'string' && item.key.length) {
      if (!isLayoutGroupNode(item)) {
        validateFieldNode(item, registry, requireCore, seenFieldKeys, errors, warnings, activeContainer);
      }
    }

    if (Array.isArray(item.items) && item.items.length) {
      walkValidate(
        item.items,
        registry,
        requireCore,
        seenFieldKeys,
        seenPropKeys,
        errors,
        warnings,
        null,
        'items',
        activeContainer
      );
    }

    if (isArrayLike(item) && Array.isArray(item.props) && item.props.length) {
      const arrayKey = typeof item.key === 'string' ? item.key : parentArrayKey;
      walkValidate(item.props, registry, requireCore, seenFieldKeys, seenPropKeys, errors, warnings, arrayKey, 'props');
    }
  }
}

function resolveArrayPropKey(item, parentArrayKey) {
  if (typeof item.prop_key === 'string' && item.prop_key.length) {
    return item.prop_key;
  }
  if (parentArrayKey && item.key) {
    return `${parentArrayKey}.${item.key}`;
  }
  return typeof item.key === 'string' ? item.key : parentArrayKey;
}

/**
 * Layout groups (sections/containers or implicit groups with items[]) are not field nodes.
 * @param {object|null|undefined} item
 */
function isLayoutGroupNode(item) {
  if (!item || typeof item !== 'object') return false;
  if (isStructuralType(item.type)) return true;
  if (!Array.isArray(item.items) || !item.items.length) return false;
  if (isArrayLike(item)) return false;
  return typeof item.key === 'string' && item.key.length > 0;
}

function validateFieldNode(item, registry, requireCore, seenFieldKeys, errors, warnings, containerKey) {
  const key = item.key;
  if (!key) return;

  const keyErrors = validateKeyFormat(key);
  if (keyErrors.length) {
    errors.push({ key, message: keyErrors[0] });
  }

  if (seenFieldKeys[key]) {
    errors.push({ key, message: `Duplicate field key "${key}".` });
  }
  seenFieldKeys[key] = true;

  if (requireCore && !registry.fields[key] && item.type !== 'widget' && !isCustomLayoutField(item)) {
    warnings.push({ key, message: `Unknown field key "${key}" (not in core template).` });
  }

  if (requireCore && registry.fields[key]) {
    const expectedContainer = registry.fields[key]._containerKey;
    if (expectedContainer && containerKey && expectedContainer !== containerKey) {
      errors.push({
        key,
        message: `Field "${key}" must stay within section container "${expectedContainer}".`,
      });
    }
  }

  if (registry.fields[key] && item.type && registry.fields[key].type && item.type !== registry.fields[key].type) {
    errors.push({
      key,
      message: `Field type mismatch for "${key}": expected "${registry.fields[key].type}", got "${item.type}".`,
    });
  }
}

function validatePropNode(prop, parentArrayKey, registry, requireCore, seenPropKeys, errors, warnings) {
  const propKey = prop.prop_key || (parentArrayKey && prop.key ? `${parentArrayKey}.${prop.key}` : '');
  const displayKey = propKey || prop.key || 'column';

  if (isPropTreeSection(prop)) {
    if (propKey && seenPropKeys[propKey]) {
      errors.push({ key: propKey, message: `Duplicate array section "${propKey}".` });
    }
    if (propKey) seenPropKeys[propKey] = true;
    if (requireCore && propKey && !registry.props[propKey] && !skipUnknownPropWarning(prop, parentArrayKey, registry)) {
      warnings.push({ key: propKey, message: `Unknown array section "${propKey}" (not in core template).` });
    }
    return;
  }

  if (!prop.key || String(prop.key).includes('.') || !KEY_PATTERN.test(String(prop.key))) {
    errors.push({ key: displayKey, message: 'Array column key must be a simple identifier.' });
  }

  if (propKey && seenPropKeys[propKey]) {
    errors.push({ key: propKey, message: `Duplicate array column "${propKey}".` });
  }
  if (propKey) seenPropKeys[propKey] = true;

  if (parentArrayKey && propKey && !propKey.startsWith(`${parentArrayKey}.`)) {
    errors.push({
      key: propKey,
      message: `Column "${propKey}" must belong to array "${parentArrayKey}".`,
    });
  }

  if (requireCore && propKey && !registry.props[propKey] && !skipUnknownPropWarning(prop, parentArrayKey, registry)) {
    warnings.push({ key: propKey, message: `Unknown array column "${propKey}" (not in core template).` });
  }
}

function skipUnknownPropWarning(prop, parentArrayKey, registry) {
  if (isCustomLayoutField(prop)) return true;
  if (!parentArrayKey || !registry) return false;
  return !registry.fields?.[parentArrayKey] && !registry.props?.[parentArrayKey];
}

export function validateColumnKeyFormat(key) {
  const k = String(key || '');
  if (!k || k.includes('.') || !KEY_PATTERN.test(k)) {
    return ['Array column key must be a simple identifier.'];
  }
  return [];
}

export function validateKeyFormat(key) {
  /** @type {string[]} */
  const errors = [];
  const parts = String(key).split('.');
  if (parts.some((p) => !p.length)) {
    errors.push('Key must not contain empty path segments.');
  }
  for (const part of parts) {
    if (!KEY_PATTERN.test(part)) {
      errors.push('Key segments may only contain letters, numbers, colons, underscores, and hyphens.');
      break;
    }
  }
  return errors;
}

/**
 * @param {object|null|undefined} node
 * @param {{ fields: Record<string, object> }} registry
 */
export function isCloneAllowed(node, registry) {
  if (!node) return false;
  if (isStructuralType(node.type)) return true;
  if (node.type === 'widget') return true;
  if (isRegistryFieldNode(node) && registry?.fields?.[node.key]) return false;
  if (isPropNode(node)) return false;
  return true;
}

/**
 * Prepare a core registry node for insertion into the user tree.
 * @param {object} coreNode
 * @returns {object}
 */
export function prepareCoreNodeForInsert(coreNode) {
  const copy = sanitizeDisplayLayoutFieldNode(stripRuntimeIds(cloneJson(coreNode)));
  assignTreeIds(copy);
  return copy;
}

function assignTreeIds(node) {
  if (Array.isArray(node)) {
    node.forEach(assignTreeIds);
    return;
  }
  if (!node || typeof node !== 'object') return;
  node[TID] = newTid();
  if (Array.isArray(node.items)) node.items.forEach(assignTreeIds);
  if (Array.isArray(node.props)) node.props.forEach(assignTreeIds);
}

/**
 * section_container key enclosing a layout-tree node (alias for displayTemplateTree helper).
 * @param {object[]} rootItems
 * @param {string} tid
 */
export function getTopLevelContainerKey(rootItems, tid) {
  return getSectionContainerKey(rootItems, tid);
}

/**
 * @param {object[]} rootItems
 * @param {ReturnType<typeof resolveDropTarget>} drop
 * @returns {string|null}
 */
function getInsertionContainerKey(rootItems, drop) {
  if (!drop) return null;
  if (drop.parentKind === 'root' || !drop.parentNode) {
    return '__root__';
  }
  return getSectionContainerKey(rootItems, drop.parentNode[TID]);
}

/**
 * Layout fields/sections may not move or be added across section_container boundaries.
 * @param {object[]} rootItems
 * @param {string} dragTid
 * @param {ReturnType<typeof resolveDropTarget>} drop
 */
function scopesMatch(rootItems, dragTid, drop) {
  const src = findNodeContextWithParent(rootItems, dragTid);
  const srcScope = getSectionContainerKey(rootItems, dragTid);
  const destScope = getInsertionContainerKey(rootItems, drop);

  if (!src || srcScope === null || destScope === null) return false;

  // Top-level section containers may only reorder among template root items.
  if (!src.parentNode && src.node?.type === 'section_container') {
    return drop.parentKind === 'root';
  }

  // Layout nodes cannot leave their section_container, except custom fields.
  if (srcScope !== destScope) {
    return isCustomLayoutField(src.node);
  }

  return true;
}

/**
 * @param {object|null|undefined} node
 * @param {object|null|undefined} parentNode
 * @param {object[]|null|undefined} siblings
 */
function nodeKind(node, parentNode = null, siblings = null) {
  if (!node) return 'unknown';
  if (isPropContext(parentNode, siblings)) {
    if (isPropTreeSection(node)) return 'prop-section';
    return 'prop';
  }
  if (isPropNode(node) && !isPropTreeSection(node)) return 'prop';
  if (isStructuralType(node.type)) return 'structural';
  if (isArrayLike(node)) return 'array';
  return 'field';
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

function inferChildListKind(parentNode) {
  if (!parentNode) return 'root';
  if (isArrayLike(parentNode) || isPropTreeSection(parentNode)) return 'props';
  return 'items';
}

/**
 * Resolve where a node would be inserted for a drag/drop operation.
 * @param {object[]} rootItems
 * @param {string} dragTid
 * @param {string} targetTid
 * @param {'before'|'after'|'into'} zone
 */
export function resolveDropTarget(rootItems, dragTid, targetTid, zone) {
  const src = findNodeContextWithParent(rootItems, dragTid);
  const tgt = findNodeContextWithParent(rootItems, targetTid);
  if (!src || !tgt) return null;

  if (zone === 'into') {
    const arr = nodeChildrenArray(tgt.node);
    if (!arr) return null;
    return {
      src,
      tgt,
      siblings: arr,
      insertIndex: arr.length,
      parentNode: tgt.node,
      parentKind: inferChildListKind(tgt.node),
    };
  }

  return {
    src,
    tgt,
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

/**
 * @param {object[]} rootItems
 * @param {string} dragTid
 * @param {string} targetTid
 * @param {'before'|'after'|'into'} zone
 */
export function canMoveNode(rootItems, dragTid, targetTid, zone) {
  if (!dragTid || !targetTid || dragTid === targetTid) return false;

  const src = findNodeContextWithParent(rootItems, dragTid);
  if (!src) return false;

  if (isDescendantByTid(rootItems, dragTid, targetTid)) return false;

  const drop = resolveDropTarget(rootItems, dragTid, targetTid, zone);
  if (!drop) return false;

  const kind = nodeKind(src.node, src.parentNode, src.siblings);

  // --- Array column / prop-tree section rules ---
  if (drop.parentKind === 'props') {
    if (kind !== 'prop' && kind !== 'prop-section') return false;
    if (!drop.parentNode) return false;

    const targetAcceptsChildren =
      drop.parentNode.type === 'nested_array' || isPropTreeSection(drop.parentNode);

    if (zone === 'into') {
      if (!targetAcceptsChildren) return false;
      return scopesMatch(rootItems, dragTid, drop);
    }

    if (
      !src.parentNode
      || !drop.parentNode
      || src.parentNode[TID] !== drop.parentNode[TID]
    ) {
      return false;
    }

    if (kind === 'prop') {
      const parentIsPropSection = isPropTreeSection(drop.parentNode);
      const arrayKey = parentIsPropSection
        ? drop.parentNode.prop_key
        : drop.parentNode.key || drop.parentNode.prop_key;
      const propKey =
        src.node.prop_key || (arrayKey && src.node.key ? `${arrayKey}.${src.node.key}` : '');
      if (arrayKey && propKey && !propKey.startsWith(`${arrayKey}.`)) return false;
    }

    return scopesMatch(rootItems, dragTid, drop);
  }

  // Prop-tree nodes cannot leave their props hierarchy via layout moves.
  if (kind === 'prop' || kind === 'prop-section') return false;

  // --- Field / section / container rules ---
  if (drop.parentKind === 'items' || drop.parentKind === 'root') {
    if (kind === 'field' && !isFieldLayoutParent(drop.parentNode)) {
      return false;
    }

    if (zone === 'into') {
      if (isArrayLike(drop.parentNode)) {
        return false;
      }
      if (drop.parentNode && !isSectionLike(drop.parentNode)) {
        return false;
      }
      if (kind === 'field' && drop.parentNode?.type === 'section_container') {
        return false;
      }
    }

    if (isPropContext(drop.parentNode, drop.siblings)) {
      return false;
    }

    return scopesMatch(rootItems, dragTid, drop);
  }

  return false;
}

/**
 * Pick the first valid drop zone for hover feedback.
 * @param {object[]} rootItems
 * @param {string} dragTid
 * @param {string} targetTid
 * @param {'before'|'after'|'into'} preferredZone
 * @returns {'before'|'after'|'into'|null}
 */
export function resolveAllowedDropZone(rootItems, dragTid, targetTid, preferredZone) {
  if (!dragTid || !targetTid) return null;
  const order =
    preferredZone === 'into'
      ? ['into', 'after', 'before']
      : [preferredZone, preferredZone === 'before' ? 'after' : 'before', 'into'];
  for (const zone of order) {
    if (canMoveNode(rootItems, dragTid, targetTid, zone)) {
      return zone;
    }
  }
  return null;
}

/**
 * @param {object[]} rootItems
 * @param {string} dragTid
 * @param {string} targetTid
 * @param {'before'|'after'|'into'} zone
 * @returns {boolean}
 */
export function moveNodeIfAllowed(rootItems, dragTid, targetTid, zone) {
  const allowed = resolveAllowedDropZone(rootItems, dragTid, targetTid, zone);
  if (!allowed) return false;
  return moveNode(rootItems, dragTid, targetTid, allowed);
}

function isDescendantByTid(items, ancestorTid, candidateTid) {
  const ctx = findNodeContextWithParent(items, ancestorTid);
  if (!ctx) return false;
  const ch = nodeChildrenArray(ctx.node);
  if (!ch?.length) return false;
  return containsTid(ch, candidateTid);
}

function containsTid(nodes, tid) {
  for (const n of nodes) {
    if (n && n[TID] === tid) return true;
    const c = nodeChildrenArray(n);
    if (c && containsTid(c, tid)) return true;
  }
  return false;
}

/**
 * @param {object[]} rootItems
 * @param {object} targetNode
 * @param {RegistryPart} part
 */
export function canAddPartAtTarget(rootItems, targetNode, part, registry) {
  if (!targetNode || !part) return false;

  if (part.kind === 'container') {
    if (!isTemplateRootTarget(targetNode) || !part.key) return false;
    return !collectUsedKeys(rootItems).layoutKeys.has(part.key);
  }

  if (part.kind === 'section') {
    if (!part.key) return false;
    if (collectUsedKeys(rootItems).layoutKeys.has(part.key)) return false;
    const parentKey = part.parentKey ?? '__root__';
    if (parentKey === '__root__') {
      return isTemplateRootTarget(targetNode);
    }
    return targetNode.type === 'section_container' && targetNode.key === parentKey;
  }

  if (part.kind === 'field') {
    if (targetNode.type !== 'section') return false;
    const targetContainer = getSectionContainerKey(rootItems, targetNode[TID]);
    const fieldMeta = registry?.fields?.[part.key];
    const fieldContainer = fieldMeta?._containerKey;
    if (!targetContainer || !fieldContainer || targetContainer !== fieldContainer) return false;
    return true;
  }

  if (part.kind === 'prop') {
    const targetScope = resolveArrayPropScope(targetNode);

    if (targetNode.type === 'nested_array' && !isPropTreeSection(targetNode)) {
      if (part.parentKey !== targetScope) return false;
      if (part.node?.type === 'section' || part.node?.type === 'section_container') return false;
      return true;
    }

    if (isArrayLike(targetNode) && targetNode.type === 'array') {
      if (part.parentKey !== targetScope) return false;
      if (part.node?.type === 'section' || part.node?.type === 'section_container') return false;
      const rest = part.key.slice(targetScope.length + 1);
      return rest.length > 0 && !rest.includes('.');
    }

    if (isPropTreeSection(targetNode)) {
      const scope = targetNode.prop_key || '';
      if (!scope || !part.key.startsWith(`${scope}.`)) return false;
      const rest = part.key.slice(scope.length + 1);
      return rest.length > 0 && !rest.includes('.');
    }

    return false;
  }

  return false;
}

function isTemplateRootTarget(targetNode) {
  return targetNode?.type === 'template';
}

function pruneUsedFieldNodes(node, usedFieldKeys) {
  if (!node || typeof node !== 'object' || !Array.isArray(node.items)) return;
  node.items = node.items.filter((child) => {
    if (!child || typeof child !== 'object') return false;
    if (isStructuralType(child.type)) {
      pruneUsedFieldNodes(child, usedFieldKeys);
      return true;
    }
    if (child.key && usedFieldKeys.has(child.key)) return false;
    pruneUsedFieldNodes(child, usedFieldKeys);
    return true;
  });
}

/**
 * Insert a core part into the user tree at the selected target node.
 * @param {object[]} rootItems
 * @param {object} targetNode
 * @param {RegistryPart} part
 * @returns {object|null} inserted node
 */
export function insertCorePart(rootItems, targetNode, part, registry) {
  if (!canAddPartAtTarget(rootItems, targetNode, part, registry)) return null;

  const node = prepareCoreNodeForInsert(part.node);

  if (part.kind === 'container' || part.kind === 'section') {
    if (!Array.isArray(targetNode.items)) targetNode.items = [];
    pruneUsedFieldNodes(node, collectUsedKeys(rootItems).fieldKeys);
    targetNode.items.push(node);
    return node;
  }

  if (part.kind === 'field') {
    if (!Array.isArray(targetNode.items)) targetNode.items = [];
    targetNode.items.push(node);
    return node;
  }

  if (part.kind === 'prop') {
    if (isArrayLike(targetNode)) {
      if (!Array.isArray(targetNode.props)) targetNode.props = [];
      targetNode.props.push(node);
      return node;
    }
    if (isPropTreeSection(targetNode)) {
      if (!Array.isArray(targetNode.props)) targetNode.props = [];
      targetNode.props.push(node);
      return node;
    }
  }

  return null;
}

/**
 * @param {ValidationResult} result
 * @returns {string}
 */
export function formatValidationErrors(result) {
  const errorLines = [];
  for (const e of result?.errors || []) {
    errorLines.push(`${e.key}: ${e.message}`);
  }
  const warningLines = [];
  for (const w of result?.warnings || []) {
    warningLines.push(`${w.key}: ${w.message}`);
  }
  if (!errorLines.length && !warningLines.length) return 'Template is valid.';
  if (!errorLines.length) {
    return ['Template is valid with warnings:', ...warningLines].join('\n');
  }
  if (!warningLines.length) {
    return errorLines.join('\n');
  }
  return [...errorLines, 'Warnings:', ...warningLines].join('\n');
}
