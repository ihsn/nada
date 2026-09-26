/** Map the site search_engine to one of three UI driver groups. */
export function searchDriverGroup(engine) {
  const e = String(engine || 'database').toLowerCase();
  if (e === 'nada_ai') return 'semantic';
  if (e === 'solr' || e === 'opensearch_native') return 'fulltext';
  return 'db';
}

/** Human-readable engine name for tooltips (includes Solr vs OpenSearch). */
export function searchDriverLabel(engine) {
  const e = String(engine || 'database').toLowerCase();
  if (e === 'nada_ai') return 'nada-ai';
  if (e === 'opensearch_native') return 'OpenSearch';
  if (e === 'solr') return 'Solr';
  return 'Database';
}
