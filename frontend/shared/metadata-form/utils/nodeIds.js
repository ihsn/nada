/**
 * Match Metadata Editor node id normalization (dots → dashes).
 */
export function normalizeNodeId(key) {
  if (key == null || key === '') return '';
  return String(key).replace(/\./g, '-');
}

/**
 * DOM id used on form fields/sections and targeted by the tree.
 */
export function fieldDomId(key) {
  const n = normalizeNodeId(key);
  return n ? `field-${n}` : '';
}

/**
 * Prefer key, then id, for tree/form anchoring.
 */
export function nodeKey(node, fallback = '') {
  if (!node || typeof node !== 'object') return fallback;
  if (node.key) return String(node.key);
  if (node.id) return String(node.id);
  return fallback;
}

export function scrollToFormNode(nodeId, { focus = true } = {}) {
  const id = fieldDomId(nodeId) || (nodeId.startsWith('field-') ? nodeId : `field-${normalizeNodeId(nodeId)}`);
  const el = document.getElementById(id);
  if (!el) return false;
  el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  if (focus) {
    const focusable = el.querySelector('input, textarea, select, button, [tabindex]') || el;
    if (typeof focusable.focus === 'function') {
      try {
        focusable.focus({ preventScroll: true });
      } catch {
        focusable.focus();
      }
    }
  }
  return true;
}
