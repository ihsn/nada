/** Dataset type icons — mirrors legacy surveys.php $type_icons (Font Awesome → MDI). */

const DATASET_TYPE_ICONS = {
  survey:        'mdi-database',
  microdata:     'mdi-database',
  datasets:      'mdi-database',
  geospatial:    'mdi-earth',
  timeseries:    'mdi-chart-timeline-variant',
  timeseriesdb:  'mdi-database',
  'timeseries-db': 'mdi-database',
  document:      'mdi-file-document-outline',
  table:         'mdi-table',
  visualization: 'mdi-chart-pie',
  script:        'mdi-file-code-outline',
  image:         'mdi-image',
  video:         'mdi-video',
};

/**
 * @param {string|undefined|null} type - surveys.type / dtype
 * @returns {string} MDI icon name
 */
export function datasetTypeIcon(type) {
  const key = String(type || 'survey').toLowerCase();
  return DATASET_TYPE_ICONS[key] ?? 'mdi-database';
}
