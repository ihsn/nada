import { computed, inject, provide, unref } from 'vue';

const LABELS_KEY = Symbol('metadataFormLabels');

export const DEFAULT_METADATA_FORM_LABELS = {
  selectSection: 'Select a section or field from the tree.',
  addFromList: 'Add from list',
  addRow: 'Add row',
  add: 'Add',
  deleteRow: 'Delete row',
  deleteRowConfirm: 'Delete this row?',
  noRows: 'No rows yet. Use “Add row” below.',
  noItems: 'No items yet.',
  showHelp: 'Show help',
  hideHelp: 'Hide help',
  showAllHelp: 'Show all help',
  hideAllHelp: 'Hide all help',
  filterAll: 'All',
  filterRequired: 'Required',
  filterRecommended: 'Recommended',
  searchFields: 'Search fields',
  noMatchingFields: 'No matching fields.',
  containerOverview:
    'Overview of this group. Select a section in the tree (or below) to edit fields.',
  sectionsInGroup: 'Sections in this group',
  nothingEntered: 'Nothing entered here yet. Open a section to add metadata.',
  noFields: 'This section has no fields.',
  noPreview: 'No metadata entered in this group yet. Open a section in the tree to start editing.',
  editSection: 'Edit section',
  treeNav: 'Metadata form navigation',
  item: 'Item',
  trueLabel: 'True',
  falseLabel: 'False',
  drawBoundingBox: 'Draw on map',
  cancelDrawBoundingBox: 'Cancel draw',
  clearBoundingBox: 'Clear box',
  boundingBoxHint: 'Click and drag on the map to draw a box, or enter coordinates.',
  boundingBoxDrawHint: 'Drag on the map to set the bounding box.',
};

/**
 * @param {import('vue').MaybeRefOrGetter<Record<string, string>>} labels
 */
export function provideMetadataFormLabels(labels) {
  const resolved = computed(() => ({
    ...DEFAULT_METADATA_FORM_LABELS,
    ...unref(labels),
  }));
  provide(LABELS_KEY, resolved);
  return resolved;
}

export function useMetadataFormLabels() {
  const labels = inject(LABELS_KEY, null);
  return computed(() => labels?.value ?? DEFAULT_METADATA_FORM_LABELS);
}
