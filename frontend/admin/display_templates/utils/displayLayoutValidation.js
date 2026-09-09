import { isArrayLike, isPropTreeSection } from './displayTemplateTree';

/**
 * Display-specific layout checks for public study-page rendering.
 *
 * @typedef {{ key: string, message: string }} ValidationIssue
 */

const STRUCTURAL_TYPES = new Set([
  'section',
  'section_container',
  'template',
  'template_root',
  'template_description',
]);

function isStructuralType(type) {
  return STRUCTURAL_TYPES.has(type || '');
}

/**
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
function isLayoutSection(node) {
  if (!node || typeof node !== 'object') return false;
  if (node.type === 'section_container' || isArrayLike(node)) return false;
  if (node.type === 'section' && !isPropTreeSection(node)) return true;
  // Display templates may omit type: "section" on grouped nodes that still have items[].
  return !!(Array.isArray(node.items) && node.items.length && typeof node.key === 'string' && node.key.length);
}

/**
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
function isLayoutFieldNode(node) {
  if (!node || typeof node !== 'object') return false;
  if (isLayoutSection(node) || node.type === 'section_container') return false;
  if (isStructuralType(node.type)) return false;
  if (Array.isArray(node.items) && node.items.length && !isArrayLike(node)) return false;
  return true;
}

/**
 * @param {object[]} items
 * @returns {boolean}
 */
function hasSectionDescendant(items) {
  for (const item of items || []) {
    if (!item || typeof item !== 'object') continue;
    if (isLayoutSection(item)) return true;
    if (Array.isArray(item.items) && hasSectionDescendant(item.items)) return true;
  }
  return false;
}

/**
 * @param {object[]} items
 * @returns {boolean}
 */
function hasFieldDescendant(items) {
  for (const item of items || []) {
    if (!item || typeof item !== 'object') continue;
    if (isLayoutFieldNode(item)) return true;
    if (Array.isArray(item.items) && hasFieldDescendant(item.items)) return true;
  }
  return false;
}

/**
 * @param {object[]} items
 * @param {'section_container'|'section'|'root'|null} parentKind
 * @param {ValidationIssue[]} errors
 * @param {ValidationIssue[]} warnings
 * @param {Record<string, boolean>} seenSectionKeys
 */
function walkDisplayLayout(items, parentKind, errors, warnings, seenSectionKeys) {
  for (const item of items || []) {
    if (!item || typeof item !== 'object') continue;

    const itemKey = typeof item.key === 'string' && item.key.length ? item.key : 'template';
    const type = item.type || '';

    if (isLayoutSection(item)) {
      if (seenSectionKeys[item.key]) {
        errors.push({
          key: item.key,
          message: `Duplicate section key "${item.key}". Sidebar anchors require unique section keys.`,
        });
      } else {
        seenSectionKeys[item.key] = true;
      }

      const children = Array.isArray(item.items) ? item.items : [];
      if (!hasFieldDescendant(children)) {
        warnings.push({
          key: item.key,
          message: `Section "${item.title || item.key}" has no fields and will not appear on the study page.`,
        });
      }
      walkDisplayLayout(children, 'section', errors, warnings, seenSectionKeys);
      continue;
    }

    if (type === 'section_container') {
      const children = Array.isArray(item.items) ? item.items : [];
      if (!hasSectionDescendant(children)) {
        warnings.push({
          key: itemKey,
          message: `Section container "${item.title || item.key || itemKey}" has no sections.`,
        });
      }
      walkDisplayLayout(children, 'section_container', errors, warnings, seenSectionKeys);
      continue;
    }

    if (isLayoutFieldNode(item)) {
      if (parentKind === 'section_container' || parentKind === 'root') {
        errors.push({
          key: item.key || itemKey,
          message:
            'Field must be placed under a section, not directly under a section container or template root, for sidebar navigation.',
        });
      }
      continue;
    }

    if (isArrayLike(item)) {
      const props = Array.isArray(item.props) ? item.props : [];
      if (!props.length) {
        warnings.push({
          key: item.key || itemKey,
          message: `Array field "${item.title || item.key || itemKey}" has no columns (props) defined.`,
        });
      }
      continue;
    }

    if (Array.isArray(item.items) && item.items.length) {
      walkDisplayLayout(item.items, parentKind, errors, warnings, seenSectionKeys);
    }
  }
}

/**
 * Validate layout tree for public display rendering semantics.
 *
 * @param {object[]|null|undefined} items
 * @returns {{ errors: ValidationIssue[], warnings: ValidationIssue[] }}
 */
export function validateDisplayLayout(items) {
  /** @type {ValidationIssue[]} */
  const errors = [];
  /** @type {ValidationIssue[]} */
  const warnings = [];
  /** @type {Record<string, boolean>} */
  const seenSectionKeys = {};

  walkDisplayLayout(items, 'root', errors, warnings, seenSectionKeys);

  return { errors, warnings };
}
