import { inject, provide, ref, shallowRef } from 'vue';
import { normalizeNodeId, nodeKey } from '../utils/nodeIds';

export const METADATA_NAV_KEY = Symbol('METADATA_NAV');

/**
 * Find a template node by normalized id (depth-first).
 */
export function findNodeByNormalizedId(items, targetNormId) {
  if (!targetNormId || !Array.isArray(items)) return null;
  for (let i = 0; i < items.length; i++) {
    const node = items[i];
    if (!node || typeof node !== 'object') continue;
    const k = nodeKey(node, '');
    if (k && normalizeNodeId(k) === targetNormId) {
      return node;
    }
    if (Array.isArray(node.items)) {
      const found = findNodeByNormalizedId(node.items, targetNormId);
      if (found) return found;
    }
  }
  return null;
}

/**
 * Prefer first section_container (ME overview), else first section, else first root.
 */
export function pickInitialNode(items) {
  const roots = (items || []).filter((x) => x && typeof x === 'object');
  if (!roots.length) return null;
  const container = roots.find((n) => n.type === 'section_container');
  if (container) return container;
  const section = roots.find((n) => n.type === 'section');
  if (section) return section;
  return roots[0];
}

/**
 * Shared tree ↔ form navigation: only the active node is shown in the main pane.
 */
export function createMetadataNav(rootItemsRef) {
  const activeNodeId = ref('');
  const activeNode = shallowRef(null);

  function setActiveNodeFromObject(node, { scrollMain = true } = {}) {
    if (!node || typeof node !== 'object') return;
    const k = nodeKey(node, '');
    activeNode.value = node;
    activeNodeId.value = k ? normalizeNodeId(k) : '';
    if (scrollMain) {
      requestAnimationFrame(() => {
        const main = document.getElementById('mf-main-content');
        if (main) main.scrollTop = 0;
      });
    }
  }

  function setActiveNode(nodeIdOrNode, options = {}) {
    if (nodeIdOrNode && typeof nodeIdOrNode === 'object') {
      setActiveNodeFromObject(nodeIdOrNode, options);
      return;
    }
    const id = normalizeNodeId(nodeIdOrNode);
    const items = typeof rootItemsRef === 'function' ? rootItemsRef() : rootItemsRef?.value;
    const node = findNodeByNormalizedId(items || [], id);
    if (node) {
      setActiveNodeFromObject(node, options);
    } else {
      activeNodeId.value = id;
    }
  }

  function selectInitial() {
    const items = typeof rootItemsRef === 'function' ? rootItemsRef() : rootItemsRef?.value;
    const node = pickInitialNode(items || []);
    if (node) {
      setActiveNodeFromObject(node, { scrollMain: false });
    }
  }

  return {
    activeNodeId,
    activeNode,
    setActiveNode,
    setActiveNodeFromObject,
    selectInitial,
  };
}

export function provideMetadataNav(nav) {
  provide(METADATA_NAV_KEY, nav);
}

export function useMetadataNav() {
  return inject(METADATA_NAV_KEY, null);
}
