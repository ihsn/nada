import { isStructuralType } from './templateFieldRegistry';

/**
 * @typedef {{ kind: 'field'|'container'|'section', key: string, title: string, type: string, node: object, parentKey?: string|null }} GroupField
 * @typedef {{ key?: string, title: string, type?: string }} GroupCrumb
 * @typedef {{ id: string, breadcrumb: GroupCrumb[], fields: GroupField[] }} AvailableFieldGroup
 */

function normalizeCoreRoot(coreTemplate) {
  if (!coreTemplate || typeof coreTemplate !== 'object') return null;
  if (Array.isArray(coreTemplate.items)) return coreTemplate;
  if (coreTemplate.template && Array.isArray(coreTemplate.template.items)) {
    return coreTemplate.template;
  }
  return null;
}

/**
 * Group unused core fields under their section / section_container in the core template tree.
 *
 * @param {object|null|undefined} coreTemplate
 * @param {{ fields: Record<string, object>, hasCore?: boolean }} registry
 * @param {{ fieldKeys: Set<string>, propKeys: Set<string> }} used
 * @param {{ containerKey?: string|null }} [options]
 * @returns {AvailableFieldGroup[]}
 */
export function buildAvailableFieldGroups(coreTemplate, registry, used, options = {}) {
  const containerKey = options?.containerKey ?? null;
  /** @type {AvailableFieldGroup[]} */
  const groups = [];
  const root = normalizeCoreRoot(coreTemplate);
  if (!root?.items?.length || !registry?.fields) return groups;

  /**
   * @param {object[]} items
   * @param {GroupCrumb[]} breadcrumb
   */
  function walk(items, breadcrumb) {
    for (const item of items || []) {
      if (!item || typeof item !== 'object') continue;

      const type = item.type;
      const crumb = {
        key: item.key,
        title: String(item.title || item.key || type || 'Group'),
        type,
      };
      const nextBreadcrumb = type === 'section_container' || type === 'section'
        ? [...breadcrumb, crumb]
        : breadcrumb;

      if ((type === 'section_container' || type === 'section') && Array.isArray(item.items)) {
        /** @type {GroupField[]} */
        const fields = [];
        for (const child of item.items) {
          if (!child || typeof child !== 'object' || !child.key) continue;
          if (isStructuralType(child.type)) continue;
          if (used.fieldKeys.has(child.key)) continue;
          if (!registry.fields[child.key]) continue;
          fields.push({
            kind: 'field',
            key: child.key,
            title: String(child.title || child.label || child.key),
            type: String(child.type || 'string'),
            node: registry.fields[child.key],
          });
        }

        if (fields.length) {
          groups.push({
            id: item.key || nextBreadcrumb.map((c) => c.key).filter(Boolean).join('/') || `group-${groups.length}`,
            breadcrumb: nextBreadcrumb,
            fields,
          });
        }

        walk(item.items, nextBreadcrumb);
      } else if (Array.isArray(item.items) && item.items.length) {
        walk(item.items, breadcrumb);
      }
    }
  }

  walk(root.items, []);

  let filtered = groups;

  if (containerKey) {
    filtered = filtered.filter((group) => {
      const container = group.breadcrumb.find((c) => c.type === 'section_container');
      return (container?.key ?? '__root__') === containerKey;
    });
  }

  return filtered;
}

function usedLayoutKeys(used) {
  return used?.layoutKeys instanceof Set ? used.layoutKeys : new Set();
}

/**
 * Unused core section_containers (Template selected). Layout sections are local grouping only.
 *
 * @param {object|null|undefined} coreTemplate
 * @param {{ layoutKeys?: Set<string> }} used
 * @returns {AvailableFieldGroup[]}
 */
export function buildAvailableRootLayoutGroups(coreTemplate, used) {
  const root = normalizeCoreRoot(coreTemplate);
  if (!root?.items?.length) return [];

  const usedKeys = usedLayoutKeys(used);
  /** @type {GroupField[]} */
  const fields = [];

  for (const item of root.items) {
    if (!item || typeof item !== 'object' || !item.key) continue;
    if (item.type !== 'section_container') continue;
    if (usedKeys.has(item.key)) continue;
    fields.push({
      kind: 'container',
      key: item.key,
      title: String(item.title || item.label || item.key),
      type: 'section_container',
      node: item,
      parentKey: '__root__',
    });
  }

  if (!fields.length) return [];
  return [
    {
      id: '__root_layout__',
      breadcrumb: [{ title: 'Template', type: 'template' }],
      fields,
    },
  ];
}

/**
 * @param {AvailableFieldGroup[]} groups
 * @param {string} query
 * @returns {AvailableFieldGroup[]}
 */
export function filterAvailableFieldGroups(groups, query) {
  const q = query.trim().toLowerCase();
  if (!q) return groups;

  return groups
    .map((group) => {
      const fields = group.fields.filter((f) => {
        const title = String(f.title || '').toLowerCase();
        const key = String(f.key || '').toLowerCase();
        return title.includes(q) || key.includes(q);
      });
      return fields.length ? { ...group, fields } : null;
    })
    .filter(Boolean);
}

/**
 * @param {AvailableFieldGroup[]} groups
 * @returns {number}
 */
export function countGroupedFields(groups) {
  return groups.reduce((sum, g) => sum + g.fields.length, 0);
}

/**
 * @param {GroupCrumb[]} breadcrumb
 * @returns {string}
 */
export function formatGroupBreadcrumb(breadcrumb) {
  return (breadcrumb || []).map((c) => c.title).filter(Boolean).join(' › ');
}

/**
 * Core props[] for a nested_array (layout field or prop-tree node).
 * @param {{ fields: Record<string, object>, props: Record<string, object> }} registry
 * @param {string} scopeKey
 * @returns {object[]|null}
 */
function coreNestedArrayProps(registry, scopeKey) {
  if (!scopeKey || !registry) return null;
  if (registry.fields?.[scopeKey]?.props) {
    return registry.fields[scopeKey].props;
  }
  if (registry.props?.[scopeKey]?.props) {
    return registry.props[scopeKey].props;
  }
  return null;
}

/**
 * Flatten core/user props for nested_array availability (sections are transparent wrappers).
 * @param {object[]} propItems
 * @param {string} scopeKey
 * @param {Array<{ propKey: string, node: object, parentKey: string }>} out
 */
function flattenNestedArrayPropParts(propItems, scopeKey, out) {
  for (const prop of propItems || []) {
    if (!prop || typeof prop !== 'object') continue;
    if (prop.type === 'section' || prop.type === 'section_container') {
      if (Array.isArray(prop.props)) {
        flattenNestedArrayPropParts(prop.props, scopeKey, out);
      }
      continue;
    }
    const propKey =
      prop.prop_key || (scopeKey && prop.key ? `${scopeKey}.${prop.key}` : '');
    if (!propKey) continue;
    out.push({ propKey, node: prop, parentKey: scopeKey });
  }
}

/**
 * Used prop keys at nested_array level (sections expanded, arrays atomic).
 * @param {object[]} propItems
 * @param {string} scopeKey
 * @returns {Set<string>}
 */
export function collectNestedArrayUsedPropKeys(propItems, scopeKey) {
  /** @type {Set<string>} */
  const used = new Set();
  for (const prop of propItems || []) {
    if (!prop || typeof prop !== 'object') continue;
    if (prop.type === 'section' || prop.type === 'section_container') {
      for (const pk of collectNestedArrayUsedPropKeys(prop.props, scopeKey)) {
        used.add(pk);
      }
      continue;
    }
    const pk =
      prop.prop_key || (scopeKey && prop.key ? `${scopeKey}.${prop.key}` : '');
    if (pk) used.add(pk);
  }
  return used;
}

/**
 * Unused props for a nested_array field (sections ignored; arrays stay atomic).
 *
 * @param {{ fields: Record<string, object>, props: Record<string, object> }} registry
 * @param {{ fieldKeys: Set<string>, propKeys: Set<string> }} used
 * @param {string} nestedArrayKey layout field key or prop_key for nested nested_array
 * @param {object[]|null|undefined} userProps current props[] from the user tree node
 * @returns {Array<{ kind: 'prop', key: string, title: string, type: string, node: object, parentKey: string }>}
 */
export function buildNestedArrayAvailableParts(registry, used, nestedArrayKey, userProps) {
  const coreProps = coreNestedArrayProps(registry, nestedArrayKey);
  if (!coreProps?.length) return [];

  /** @type {Array<{ propKey: string, node: object, parentKey: string }>} */
  const candidates = [];
  flattenNestedArrayPropParts(coreProps, nestedArrayKey, candidates);

  const usedAtScope = Array.isArray(userProps)
    ? collectNestedArrayUsedPropKeys(userProps, nestedArrayKey)
    : used.propKeys;

  /** @type {Array<{ kind: 'prop', key: string, title: string, type: string, node: object, parentKey: string }>} */
  const parts = [];

  for (const candidate of candidates) {
    if (usedAtScope.has(candidate.propKey)) continue;
    const node = registry.props?.[candidate.propKey] || candidate.node;
    parts.push({
      kind: 'prop',
      key: candidate.propKey,
      title: String(node.title || node.label || candidate.propKey),
      type: String(node.type || 'string'),
      node,
      parentKey: nestedArrayKey,
    });
  }

  parts.sort((a, b) => a.title.localeCompare(b.title, undefined, { sensitivity: 'base' }));
  return parts;
}

/**
 * Unused immediate column props for a flat array (or prop-tree section).
 *
 * @param {{ props: Record<string, object> }} registry
 * @param {{ fieldKeys: Set<string>, propKeys: Set<string> }} used
 * @param {string} scopeKey array field key or array prop_key
 * @param {{ excludeSections?: boolean }} [options]
 * @returns {Array<{ kind: 'prop', key: string, title: string, type: string, node: object, parentKey: string }>}
 */
export function buildAvailableArrayPropParts(registry, used, scopeKey, options = {}) {
  if (!scopeKey || !registry?.props) return [];
  const { excludeSections = false } = options;

  /** @type {Array<{ kind: 'prop', key: string, title: string, type: string, node: object, parentKey: string }>} */
  const parts = [];
  const prefix = `${scopeKey}.`;

  for (const [propKey, node] of Object.entries(registry.props)) {
    if (!propKey.startsWith(prefix)) continue;
    const rest = propKey.slice(prefix.length);
    if (!rest || rest.includes('.')) continue;
    if (excludeSections && (node.type === 'section' || node.type === 'section_container')) continue;
    if (used.propKeys.has(propKey)) continue;
    parts.push({
      kind: 'prop',
      key: propKey,
      title: String(node.title || node.label || propKey),
      type: String(node.type || 'string'),
      node,
      parentKey: scopeKey,
    });
  }

  parts.sort((a, b) => a.title.localeCompare(b.title, undefined, { sensitivity: 'base' }));
  return parts;
}

/**
 * @param {ReturnType<typeof buildAvailableArrayPropParts>} parts
 * @param {string} query
 */
export function filterAvailableArrayPropParts(parts, query) {
  const q = query.trim().toLowerCase();
  if (!q) return parts;
  return parts.filter((p) => {
    const title = String(p.title || '').toLowerCase();
    const key = String(p.key || '').toLowerCase();
    return title.includes(q) || key.includes(q);
  });
}
