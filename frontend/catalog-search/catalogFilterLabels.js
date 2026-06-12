/** Split comma-separated facet URL values (multi-select). */
export function parseMultiFilterValue(raw) {
  if (raw == null || raw === '') return [];
  return String(raw).split(',').map((s) => s.trim()).filter(Boolean);
}

function resolveCountry(id, facets) {
  return facets?.countries?.[id]?.title ?? null;
}

function resolveRegion(id, facets) {
  return facets?.regions?.[id]?.title ?? null;
}

function resolveCollection(id, facets) {
  const repos = facets?.repositories;
  if (!repos) return null;
  if (repos[id]?.title) return repos[id].title;
  for (const item of Object.values(repos)) {
    if (String(item.repositoryid) === String(id)) return item.title;
  }
  return null;
}

function resolveDtype(id, facets, translate) {
  const types = facets?.da_types;
  if (!types) return null;
  const item = types[id] ?? Object.values(types).find((t) => String(t.code) === String(id));
  if (!item) return null;
  return translate ? translate(item.title, item.code) : item.title;
}

function resolveDataClass(id, facets, translate) {
  const item = facets?.data_class?.[id];
  if (!item) return null;
  return translate ? translate(item.title, item.code) : item.title;
}

function resolveDatasetType(id, facets, translate) {
  const item = facets?.types?.[id];
  if (!item) return null;
  return translate ? translate(item.title, id) : item.title;
}

function resolveUserFacet(facetKey, id, facets) {
  const facet = facets?.[facetKey];
  if (!facet?.values) return null;
  return facet.values[id]?.title ?? null;
}

const RESOLVERS = {
  country: (id, facets) => resolveCountry(id, facets),
  region: (id, facets) => resolveRegion(id, facets),
  collection: (id, facets) => resolveCollection(id, facets),
  dtype: (id, facets, translate) => resolveDtype(id, facets, translate),
  data_class: (id, facets, translate) => resolveDataClass(id, facets, translate),
  type: (id, facets, translate) => resolveDatasetType(id, facets, translate),
  tag: (id) => id,
};

/**
 * Resolve one filter value id to a display label.
 */
export function formatSingleFilterDisplayValue(key, id, facets, translate) {
  const resolver = RESOLVERS[key];
  if (resolver) {
    return resolver(id, facets, translate) ?? id;
  }
  const facet = facets?.[key];
  if (facet?.type === 'user') {
    return resolveUserFacet(key, id, facets) ?? id;
  }
  return id;
}

/**
 * Turn raw URL filter value(s) into human-readable label(s) using facet metadata.
 * @param {string} key - filter query key
 * @param {string} rawValue
 * @param {object|null} facets - API facets payload
 * @param {(key: string, fallback?: string) => string} [translate]
 * @returns {string}
 */
export function formatFilterDisplayValue(key, rawValue, facets, translate) {
  if (rawValue == null || rawValue === '') return '';

  const parts = parseMultiFilterValue(rawValue);
  if (!parts.length) return String(rawValue);

  return parts
    .map((id) => formatSingleFilterDisplayValue(key, id, facets, translate))
    .join(', ');
}

