<template>
  <div class="dt-tree-panel border rounded-lg">
    <div class="dt-tree-with-actions">
      <div class="dt-tree-main">
        <div class="dt-tree-search px-2 pt-2 pb-3">
          <v-text-field
            :model-value="treeSearch"
            density="compact"
            variant="outlined"
            hide-details
            placeholder="Search layout tree…"
            prepend-inner-icon="mdi-magnify"
            clearable
            @update:model-value="onTreeSearchUpdate"
          />
          <v-btn-toggle
            :model-value="treeViewMode"
            mandatory
            density="compact"
            variant="outlined"
            divided
            class="dt-tree-mode-toggle w-100"
            @update:model-value="$emit('update:treeViewMode', $event)"
          >
            <v-btn value="structure" size="small" class="flex-grow-1">Structure</v-btn>
            <v-btn value="preview" size="small" class="flex-grow-1">Preview</v-btn>
          </v-btn-toggle>
        </div>
        <div class="pa-2 pt-2 dt-tree-scroll">
          <div
            class="dt-tree-row dt-tree-root-row d-flex align-center rounded"
            :class="{ 'dt-tree-row--active': selectedTid === VIRTUAL_TEMPLATE_ROOT_TID }"
            role="treeitem"
            @click="$emit('select-template-root')"
          >
            <v-btn
              icon
              variant="text"
              size="x-small"
              class="mr-0 flex-shrink-0"
              :aria-expanded="rootExpanded ? 'true' : 'false'"
              @click.stop="$emit('toggle', templateRootTid)"
            >
              <v-icon size="20">{{ rootExpanded ? 'mdi-chevron-down' : 'mdi-chevron-right' }}</v-icon>
            </v-btn>
            <v-icon class="mr-2 flex-shrink-0" size="small" color="primary">mdi-file-document-edit-outline</v-icon>
            <span class="text-body-2 font-weight-medium text-truncate">Template</span>
          </div>

          <div v-show="rootExpanded">
            <div
              v-if="showDescriptionRow"
              class="dt-tree-row dt-tree-child-row d-flex align-center rounded"
              :class="{ 'dt-tree-row--active': selectedTid === VIRTUAL_DESCRIPTION_TID }"
              role="button"
              tabindex="0"
              @click="$emit('select-description')"
              @keydown.enter="$emit('select-description')"
            >
              <span class="dt-tree-indent" />
              <v-icon class="mr-2 flex-shrink-0" size="small" color="primary">mdi-ballot-outline</v-icon>
              <span class="text-body-2 font-weight-medium">Description</span>
            </div>

            <div v-if="!filteredNodes.length && treeSearchNorm" class="text-caption text-medium-emphasis pa-2 dt-tree-structure-hint">
              No layout nodes match your search.
            </div>
            <div v-else-if="!nodes.length" class="text-caption text-medium-emphasis pa-2 dt-tree-structure-hint">
              No layout nodes yet. Select <strong>Template</strong>, a section, or a section container, then use <strong>Add section</strong>.
            </div>
            <DisplayTemplateTreeItem
              v-for="node in filteredNodes"
              :key="`${node._tid}:${treeSearchNorm}:${treeViewMode}`"
              :root-items="rootItems"
              :dragging-tid="draggingTid"
              :node="node"
              :depth="1"
              :expanded="expanded"
              :selected-tids="selectedTids"
              :cut-tids="cutTids"
              :drag-active="!!draggingTid"
              :drop-target-tid="hoverDrop?.targetTid || ''"
              :drop-zone="hoverDrop?.zone || ''"
              :search-query="treeSearchNorm"
              :readonly="treeReadonly"
              @toggle="$emit('toggle', $event)"
              @select="(n, e) => $emit('select-node', n, e)"
              @drag-start="$emit('drag-start', $event)"
              @drag-end="$emit('drag-end')"
              @drag-hover="$emit('drag-hover', $event)"
              @node-drop="$emit('node-drop', $event)"
            />
          </div>
        </div>
      </div>

      <DisplayTemplateVerticalActions
        v-if="treeViewMode === 'structure'"
        v-bind="actionFlags"
        :readonly="readonly"
        @action="$emit('tree-action', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import DisplayTemplateTreeItem from './DisplayTemplateTreeItem.vue';
import DisplayTemplateVerticalActions from './DisplayTemplateVerticalActions.vue';
import {
  flattenPreviewTreeItems,
  nodeMatchesTreeSearch,
  subtreeMatchesTreeSearch,
  VIRTUAL_DESCRIPTION_TID,
  VIRTUAL_TEMPLATE_ROOT_TID,
} from '../utils/displayTemplateTree';

defineOptions({ name: 'DisplayTemplateTreePanel' });

const props = defineProps({
  rootItems: { type: Array, default: () => [] },
  nodes: { type: Array, default: () => [] },
  expanded: { type: Object, required: true },
  selectedTid: { type: String, default: '' },
  selectedTids: { type: Array, default: () => [] },
  cutTids: { type: Array, default: () => [] },
  draggingTid: { type: String, default: '' },
  hoverDrop: { type: Object, default: null },
  actionFlags: {
    type: Object,
    default: () => ({
      canAddSection: false,
      canAddWidget: false,
      canAddField: false,
      canAddCustom: false,
      addCustomTitle: 'Add custom field',
      canCut: false,
      canPaste: false,
      hasClipboard: false,
      canRemove: false,
      canMoveUp: false,
      canMoveDown: false,
    }),
  },
  readonly: { type: Boolean, default: false },
  treeViewMode: { type: String, default: 'structure' },
});

defineEmits([
  'select-template-root',
  'select-description',
  'select-node',
  'toggle',
  'drag-start',
  'drag-end',
  'drag-hover',
  'node-drop',
  'tree-action',
  'update:treeViewMode',
]);

const treeSearch = ref('');

function onTreeSearchUpdate(value) {
  treeSearch.value = value == null ? '' : String(value);
}

const treeSearchNorm = computed(() => treeSearch.value.trim().toLowerCase());

const structureNodes = computed(() => props.nodes || []);

const previewNodes = computed(() => flattenPreviewTreeItems(structureNodes.value));

const displayNodes = computed(() =>
  props.treeViewMode === 'preview' ? previewNodes.value : structureNodes.value
);

const treeReadonly = computed(() => props.readonly || props.treeViewMode === 'preview');

const filteredNodes = computed(() => {
  if (!treeSearchNorm.value.length) return displayNodes.value;
  return displayNodes.value.filter((n) => subtreeMatchesTreeSearch(n, treeSearchNorm.value));
});

const rootExpanded = computed(() => {
  if (treeSearchNorm.value.length > 0) return true;
  return !!props.expanded[templateRootTid];
});

const showDescriptionRow = computed(() => {
  if (props.treeViewMode === 'preview') return false;
  if (!treeSearchNorm.value.length) return true;
  return nodeMatchesTreeSearch({ title: 'Description', key: 'description', type: 'description' }, treeSearchNorm.value);
});

const templateRootTid = VIRTUAL_TEMPLATE_ROOT_TID;
</script>

<style scoped>
.dt-tree-panel {
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  background: var(--dt-panel-bg, #fff);
}
.dt-tree-with-actions {
  display: flex;
  flex-direction: row;
  flex: 1 1 0;
  min-height: 0;
  min-width: 0;
  overflow: hidden;
}
.dt-tree-main {
  display: flex;
  flex-direction: column;
  flex: 1 1 0;
  min-width: 0;
  min-height: 0;
  overflow: hidden;
}
.dt-tree-search {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.dt-tree-search :deep(.v-field) {
  font-size: 0.8125rem;
}
.dt-tree-mode-toggle {
  margin-top: 0;
}
.dt-tree-scroll {
  flex: 1 1 0;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
.dt-tree-row {
  cursor: pointer;
  min-height: 34px;
  padding-right: 6px;
  border: 1px solid transparent;
}
.dt-tree-row:hover {
  background: rgba(var(--v-theme-primary), 0.06);
}
.dt-tree-row--active {
  background: rgba(var(--v-theme-primary), 0.12);
}
.dt-tree-indent {
  display: inline-block;
  width: 28px;
  flex-shrink: 0;
}
.dt-tree-child-row {
  padding-left: 2px;
}
.dt-tree-mode-toggle :deep(.v-btn) {
  text-transform: none;
  letter-spacing: normal;
}
.dt-tree-structure-hint {
  padding-left: 36px;
  max-width: 18rem;
  line-height: 1.35;
}
</style>
