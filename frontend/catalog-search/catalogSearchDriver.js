/** Map the site search_engine to one of three UI driver groups. */
export function searchDriverGroup(engine) {
  const e = String(engine || 'database').toLowerCase();
  if (e === 'nada_ai_opensearch' || e === 'nada_ai_qdrant') return 'semantic';
  if (e === 'solr' || e === 'opensearch') return 'fulltext';
  return 'db';
}

/** Human-readable engine name for tooltips (includes Solr vs OpenSearch). */
export function searchDriverLabel(engine) {
  const e = String(engine || 'database').toLowerCase();
  if (e === 'nada_ai_opensearch') return 'nada-ai (OpenSearch)';
  if (e === 'nada_ai_qdrant') return 'nada-ai (Qdrant)';
  if (e === 'opensearch') return 'OpenSearch';
  if (e === 'solr') return 'Solr';
  return 'Database';
}
