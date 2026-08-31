export function isRequiredField(node) {
  return !!(node && (node.is_required || node.required || node.isRequired));
}

export function isRecommendedField(node) {
  if (!node) return false;
  if (node.is_recommended || node.isRecommended) return true;
  const cls = typeof node.class === 'string' ? node.class : '';
  return cls.split(/\s+/).includes('recommended');
}

export function isSectionNode(node) {
  return !!node && (node.type === 'section' || node.type === 'section_container');
}

export function nodeTextMatches(node, query) {
  const q = String(query || '').trim().toLowerCase();
  if (!q) return true;
  return `${node?.title || ''} ${node?.key || ''}`.toLowerCase().includes(q);
}

export function fieldPassesMode(node, mode) {
  if (mode === 'required') return isRequiredField(node);
  if (mode === 'recommended') return isRequiredField(node) || isRecommendedField(node);
  return true;
}

/**
 * Tree visibility: keep ancestors of matching fields; sections match if a child does
 * or if the section title matches the search (then children still obey the mode).
 */
export function nodeVisibleInTree(node, { mode = 'all', query = '' } = {}) {
  if (!node || typeof node !== 'object') return false;
  const kids = Array.isArray(node.items) ? node.items.filter((x) => x && typeof x === 'object') : [];
  const childVisible = kids.some((c) => nodeVisibleInTree(c, { mode, query }));

  if (isSectionNode(node)) {
    if (childVisible) return true;
    if (String(query || '').trim() && nodeTextMatches(node, query)) {
      return kids.some((c) => nodeVisibleInTree(c, { mode, query: '' }));
    }
    return false;
  }

  return fieldPassesMode(node, mode) && nodeTextMatches(node, query);
}

export function nodeVisibleInForm(node, { mode = 'all', query = '' } = {}) {
  if (!node || typeof node !== 'object') return false;
  if (isSectionNode(node)) return nodeVisibleInTree(node, { mode, query });
  return fieldPassesMode(node, mode) && nodeTextMatches(node, query);
}
