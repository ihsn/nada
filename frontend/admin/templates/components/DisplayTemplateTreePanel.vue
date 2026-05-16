<template>
  <div class="dt-tree-panel border rounded-lg bg-surface">
    <div class="dt-tree-with-actions">
      <div class="dt-tree-main">
        <div class="pa-2 dt-tree-scroll">
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
              :aria-expanded="expanded[templateRootTid] ? 'true' : 'false'"
              @click.stop="$emit('toggle', templateRootTid)"
            >
              <v-icon size="20">{{ expanded[templateRootTid] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}</v-icon>
            </v-btn>
            <v-icon class="mr-2 flex-shrink-0" size="small" color="primary">mdi-file-document-edit-outline</v-icon>
            <span class="text-body-2 font-weight-medium text-truncate">Template</span>
          </div>

          <div v-show="expanded[templateRootTid]">
            <div
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

            <div v-if="!nodes.length" class="text-caption text-medium-emphasis pa-2 dt-tree-structure-hint">
              No layout sections yet. Use <strong>+ group</strong> or <strong>+ section</strong> in the actions column.
            </div>
            <DisplayTemplateTreeItem
              v-for="node in nodes"
              :key="node._tid"
              :node="node"
              :depth="1"
              :expanded="expanded"
              :selected-tid="selectedTid"
              :drag-active="!!draggingTid"
              :drop-target-tid="hoverDrop?.targetTid || ''"
              :drop-zone="hoverDrop?.zone || ''"
              @toggle="$emit('toggle', $event)"
              @select="$emit('select-node', $event)"
              @drag-start="$emit('drag-start', $event)"
              @drag-end="$emit('drag-end')"
              @drag-hover="$emit('drag-hover', $event)"
              @node-drop="$emit('node-drop', $event)"
            />
          </div>
        </div>
      </div>

      <DisplayTemplateVerticalActions v-bind="actionFlags" @action="$emit('tree-action', $event)" />
    </div>
  </div>
</template>

<script setup>
import DisplayTemplateTreeItem from './DisplayTemplateTreeItem.vue';
import DisplayTemplateVerticalActions from './DisplayTemplateVerticalActions.vue';
import { VIRTUAL_DESCRIPTION_TID, VIRTUAL_TEMPLATE_ROOT_TID } from '../utils/displayTemplateTree';

defineOptions({ name: 'DisplayTemplateTreePanel' });

defineProps({
  nodes: { type: Array, default: () => [] },
  expanded: { type: Object, required: true },
  selectedTid: { type: String, default: '' },
  draggingTid: { type: String, default: '' },
  hoverDrop: { type: Object, default: null },
  actionFlags: {
    type: Object,
    default: () => ({
      canAddGroup: false,
      canAddSection: false,
      canAddField: false,
      canClone: false,
      canRemove: false,
      canMoveUp: false,
      canMoveDown: false,
    }),
  },
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
]);

const templateRootTid = VIRTUAL_TEMPLATE_ROOT_TID;
</script>

<style scoped>
.dt-tree-panel {
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
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
.dt-tree-structure-hint {
  padding-left: 36px;
  max-width: 18rem;
  line-height: 1.35;
}
</style>
