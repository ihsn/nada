/**
 * SVG icons for the public catalog search UI (no @mdi/font webfont).
 * Paths are vendored in mdiSvgPaths.js; registered as Vuetify `$mdi-*` aliases.
 */
import { aliases as vuetifyMdiAliases, mdi } from 'vuetify/iconsets/mdi-svg';
import {
  mdiAccountCheck,
  mdiArrowLeft,
  mdiChartPie,
  mdiChartTimelineVariant,
  mdiChevronDown,
  mdiChevronRight,
  mdiChevronUp,
  mdiClose,
  mdiCloseCircle,
  mdiCreativeCommons,
  mdiDatabase,
  mdiDatabaseArrowDown,
  mdiEarth,
  mdiEyeOutline,
  mdiFileCodeOutline,
  mdiFileDocumentOutline,
  mdiFilePdfBox,
  mdiFilterOutline,
  mdiFlaskOutline,
  mdiFormatListBulleted,
  mdiImage,
  mdiLinkVariant,
  mdiLock,
  mdiLockOpenVariant,
  mdiMagnify,
  mdiMapMarkerOutline,
  mdiMinusCircleOutline,
  mdiOpenInNew,
  mdiRefresh,
  mdiShieldLock,
  mdiStar,
  mdiTable,
  mdiTextBoxOutline,
  mdiTextSearchVariant,
  mdiVideo,
  mdiViewGrid,
} from './mdiSvgPaths.js';

/** @type {Record<string, string>} alias key (no leading $) → SVG path with svg: prefix */
export const catalogMdiAliases = {
  'mdi-account-check': `svg:${mdiAccountCheck}`,
  'mdi-arrow-left': `svg:${mdiArrowLeft}`,
  'mdi-chart-pie': `svg:${mdiChartPie}`,
  'mdi-chart-timeline-variant': `svg:${mdiChartTimelineVariant}`,
  'mdi-chevron-down': `svg:${mdiChevronDown}`,
  'mdi-chevron-right': `svg:${mdiChevronRight}`,
  'mdi-chevron-up': `svg:${mdiChevronUp}`,
  'mdi-close': `svg:${mdiClose}`,
  'mdi-close-circle': `svg:${mdiCloseCircle}`,
  'mdi-creative-commons': `svg:${mdiCreativeCommons}`,
  'mdi-database': `svg:${mdiDatabase}`,
  'mdi-database-arrow-down': `svg:${mdiDatabaseArrowDown}`,
  'mdi-earth': `svg:${mdiEarth}`,
  'mdi-eye-outline': `svg:${mdiEyeOutline}`,
  'mdi-file-code-outline': `svg:${mdiFileCodeOutline}`,
  'mdi-file-document-outline': `svg:${mdiFileDocumentOutline}`,
  'mdi-file-pdf-box': `svg:${mdiFilePdfBox}`,
  'mdi-filter-outline': `svg:${mdiFilterOutline}`,
  'mdi-flask-outline': `svg:${mdiFlaskOutline}`,
  'mdi-format-list-bulleted': `svg:${mdiFormatListBulleted}`,
  'mdi-image': `svg:${mdiImage}`,
  'mdi-link-variant': `svg:${mdiLinkVariant}`,
  'mdi-lock': `svg:${mdiLock}`,
  'mdi-lock-open-variant': `svg:${mdiLockOpenVariant}`,
  'mdi-magnify': `svg:${mdiMagnify}`,
  'mdi-map-marker-outline': `svg:${mdiMapMarkerOutline}`,
  'mdi-minus-circle-outline': `svg:${mdiMinusCircleOutline}`,
  'mdi-open-in-new': `svg:${mdiOpenInNew}`,
  'mdi-refresh': `svg:${mdiRefresh}`,
  'mdi-shield-lock': `svg:${mdiShieldLock}`,
  'mdi-star': `svg:${mdiStar}`,
  'mdi-table': `svg:${mdiTable}`,
  'mdi-text-box-outline': `svg:${mdiTextBoxOutline}`,
  'mdi-text-search-variant': `svg:${mdiTextSearchVariant}`,
  'mdi-video': `svg:${mdiVideo}`,
  'mdi-view-grid': `svg:${mdiViewGrid}`,
};

/** Vuetify `icons` option for createVuetify (SVG only, no webfont CSS). */
export const catalogSearchIcons = {
  defaultSet: 'mdi',
  aliases: {
    ...vuetifyMdiAliases,
    ...catalogMdiAliases,
  },
  sets: { mdi },
};

/**
 * Resolve a legacy `mdi-*` name to a Vuetify alias token (`$mdi-*`).
 * @param {string} name
 * @returns {string}
 */
export function mdiAlias(name) {
  if (!name) return '$mdi-database';
  const key = String(name).startsWith('$') ? String(name).slice(1) : String(name);
  return catalogMdiAliases[key] ? `$${key}` : '$mdi-database';
}
