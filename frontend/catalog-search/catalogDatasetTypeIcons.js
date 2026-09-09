/** Dataset type icons — mirrors legacy surveys.php $type_icons (Font Awesome → MDI SVG). */

import { mdiAlias } from './mdiIcons';

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
  script:        'mdi-file-code-outline',
  image:         'mdi-image',
  video:         'mdi-video',
};

/**
 * @param {string|undefined|null} type - surveys.type / dtype
 * @returns {string} Vuetify SVG alias token (e.g. `$mdi-database`)
 */
export function datasetTypeIcon(type) {
  const key = String(type || 'survey').toLowerCase();
  return mdiAlias(DATASET_TYPE_ICONS[key] ?? 'mdi-database');
}
