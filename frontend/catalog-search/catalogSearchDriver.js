/** Map site search_provider to one of three UI driver groups. */
export function searchDriverGroup(provider) {
  const p = String(provider || 'db').toLowerCase();
  if (p === 'semantic') return 'semantic';
  if (p === 'solr' || p === 'opensearch') return 'fulltext';
  return 'db';
}

/** Human-readable driver name for tooltips (includes Solr vs OpenSearch). */
export function searchDriverLabel(provider) {
  const p = String(provider || 'db').toLowerCase();
  if (p === 'semantic') return 'Semantic';
  if (p === 'opensearch') return 'OpenSearch';
  if (p === 'solr') return 'Solr';
  return 'Database';
}
