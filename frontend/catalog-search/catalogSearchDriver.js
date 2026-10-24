/** Map the site search_engine to one of three UI driver groups. */
export function searchDriverGroup(engine) {
  const e = String(engine || 'database').toLowerCase();
  if (e === 'nada_ai') return 'semantic';
  if (e === 'solr') return 'fulltext';
  return 'db';
}

/** Human-readable engine name for tooltips. */
export function searchDriverLabel(engine) {
  const e = String(engine || 'database').toLowerCase();
  if (e === 'nada_ai') return 'NADA-AI';
  if (e === 'solr') return 'Solr';
  return 'Database';
}
