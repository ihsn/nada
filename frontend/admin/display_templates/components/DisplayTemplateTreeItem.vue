<template>
  <div v-show="visibleInSearch" class="dt-tree-item">
    <div
      class="dt-tree-row d-flex align-center rounded"
      :class="rowClass"
      :style="{ paddingLeft: `${6 + depth * 14}px` }"
      role="treeitem"
      :draggable="!readonly"
      @dragstart="onDragStart"
      @dragend="onDragEnd"
      @dragover.prevent="onDragOver"
      @dragleave="onDragLeave"
      @drop.prevent="onDrop"
      @click="onSelect($event)"
    >
      <v-icon v-if="!readonly" class="dt-drag-hint mr-1 text-medium-emphasis" size="18" @click.stop>mdi-drag</v-icon>
      <v-btn
        v-if="isContainer"
        icon
        variant="text"
        size="x-small"
        class="mr-0"
        :aria-expanded="showChildren ? 'true' : 'false'"
        @click.stop="$emit('toggle', node._tid)"
      >
        <v-icon size="20">{{ showChildren ? 'mdi-chevron-down' : 'mdi-chevron-right' }}</v-icon>
      </v-btn>
      <span v-else class="dt-tree-spacer" />
      <v-icon size="small" class="mr-2" :class="iconClass">{{ icon }}</v-icon>
      <span
        class="text-body-2 text-truncate flex-grow-1"
        :class="{
          'text-medium-emphasis': isProp && !isCustom && !isWidget,
          'dt-tree-label--custom': isCustom,
          'dt-tree-label--widget': isWidget,
          'dt-tree-row--search-hit': searchHit,
        }"
      >{{ displayLabel }}</span>
    </div>
    <div v-if="isContainer && showChildren">
      <DisplayTemplateTreeItem
        v-for="c in kids"
        :key="c._tid"
        :root-items="rootItems"
        :dragging-tid="draggingTid"
        :node="c"
        :depth="depth + 1"
        :expanded="expanded"
        :selected-tids="selectedTids"
        :cut-tids="cutTids"
        :drag-active="dragActive"
        :drop-target-tid="dropTargetTid"
        :drop-zone="dropZone"
        :search-query="searchQuery"
        :readonly="readonly"
        @toggle="$emit('toggle', $event)"
        @select="$emit('select', $event)"
        @drag-start="$emit('drag-start', $event)"
        @drag-end="$emit('drag-end')"
        @drag-hover="$emit('drag-hover', $event)"
        @node-drop="$emit('node-drop', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  dropZoneFromPointer,
  isContainerNode,
  isCustomLayoutField,
  isPropNode,
  nodeChildrenArray,
  nodeIcon,
  nodeLabel,
  nodeMatchesTreeSearch,
  subtreeMatchesTreeSearch,
} from '../utils/displayTemplateTree';
import { resolveAllowedDropZone } from '../utils/templateFieldRegistry';

defineOptions({ name: 'DisplayTemplateTreeItem' });

const props = defineProps({
  rootItems: { type: Array, default: () => [] },
  draggingTid: { type: String, default: '' },
  node: { type: Object, required: true },
  depth: { type: Number, default: 0 },
  expanded: { type: Object, required: true },
  selectedTids: { type: Array, default: () => [] },
  cutTids: { type: Array, default: () => [] },
  dragActive: { type: Boolean, default: false },
  dropTargetTid: { type: String, default: '' },
  dropZone: { type: String, default: '' },
  searchQuery: { type: String, default: '' },
  readonly: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle', 'select', 'drag-start', 'drag-end', 'drag-hover', 'node-drop']);

const kids = computed(() => nodeChildrenArray(props.node) || []);
const isContainer = computed(() => isContainerNode(props.node));
const isProp = computed(() => isPropNode(props.node));
const isCustom = computed(() => isCustomLayoutField(props.node));
const isWidget = computed(() => props.node?.type === 'widget');
const iconClass = computed(() => {
  if (isCustom.value) return 'dt-tree-icon--custom';
  if (isWidget.value) return 'dt-tree-icon--widget';
  return 'text-medium-emphasis';
});
const icon = computed(() => nodeIcon(props.node));
const label = computed(() => nodeLabel(props.node));
const isCut = computed(() => props.cutTids.includes(props.node._tid));
const displayLabel = computed(() => (isCut.value ? `${label.value} *` : label.value));

const visibleInSearch = computed(() =>
  !props.searchQuery.length || subtreeMatchesTreeSearch(props.node, props.searchQuery)
);

const searchHit = computed(() =>
  props.searchQuery.length > 0 && nodeMatchesTreeSearch(props.node, props.searchQuery)
);

const showChildren = computed(() => {
  if (!isContainer.value) return false;
  if (props.searchQuery.length > 0) return visibleInSearch.value;
  return !!props.expanded[props.node._tid];
});

const rowClass = computed(() => {
  const cls = [];
  if (isCustom.value) cls.push('dt-tree-row--custom');
  if (isWidget.value) cls.push('dt-tree-row--widget');
  if (isCut.value) cls.push('dt-tree-row--cut');
  if (props.selectedTids.includes(props.node._tid)) cls.push('dt-tree-row--active');
  if (props.selectedTids.length > 1 && props.selectedTids.includes(props.node._tid)) {
    cls.push('dt-tree-row--multi');
  }
  if (props.dragActive && props.dropTargetTid === props.node._tid && props.dropZone) {
    cls.push(`dt-drop--${props.dropZone}`);
  }
  return cls;
});

function onSelect(e) {
  emit('select', props.node, e);
}

function onDragStart(e) {
  if (props.readonly) {
    e.preventDefault();
    return;
  }
  emit('drag-start', props.node._tid);
  try {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', props.node._tid);
  } catch {
    /* ignore */
  }
}

function onDragEnd() {
  emit('drag-end');
}

function onDragOver(e) {
  if (props.readonly || !props.dragActive || !props.draggingTid) return;
  try {
    e.dataTransfer.dropEffect = 'move';
  } catch {
    /* ignore */
  }
  const preferred = dropZoneFromPointer(e, props.node);
  const zone = resolveAllowedDropZone(
    props.rootItems,
    props.draggingTid,
    props.node._tid,
    preferred
  );
  if (!zone) return;
  emit('drag-hover', { targetTid: props.node._tid, zone });
}

function onDragLeave() {}

function onDrop(e) {
  if (props.readonly) return;
  const from = e.dataTransfer?.getData('text/plain') || props.draggingTid;
  if (!from) return;
  const preferred = dropZoneFromPointer(e, props.node);
  const zone = resolveAllowedDropZone(props.rootItems, from, props.node._tid, preferred);
  if (!zone) return;
  emit('node-drop', { dragTid: from, targetTid: props.node._tid, zone });
}
</script>

<style scoped>
.dt-tree-spacer {
  display: inline-block;
  width: 28px;
  flex-shrink: 0;
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
.dt-tree-row--custom .dt-tree-icon--custom,
.dt-tree-label--custom {
  color: #7b1fa2;
}
.dt-tree-row--custom:hover {
  background: rgba(123, 31, 162, 0.08);
}
.dt-tree-row--custom.dt-tree-row--active {
  background: rgba(123, 31, 162, 0.14);
}
.dt-tree-label--custom.dt-tree-row--search-hit {
  color: #6a1b9a;
}
.dt-tree-row--widget .dt-tree-icon--widget,
.dt-tree-label--widget {
  color: #e64a19;
}
.dt-tree-row--widget:hover {
  background: rgba(230, 74, 25, 0.08);
}
.dt-tree-row--widget.dt-tree-row--active {
  background: rgba(230, 74, 25, 0.14);
}
.dt-tree-label--widget.dt-tree-row--search-hit {
  color: #d84315;
}
.dt-tree-row--active {
  background: rgba(var(--v-theme-primary), 0.12);
}
.dt-tree-row--cut {
  background: rgba(33, 150, 243, 0.18);
}
.dt-tree-row--cut.dt-tree-row--active {
  background: rgba(33, 150, 243, 0.28);
}
.dt-tree-row--multi {
  box-shadow: inset 3px 0 0 rgb(var(--v-theme-primary));
}
.dt-drag-hint {
  cursor: grab;
  opacity: 0.55;
}
.dt-tree-row:active .dt-drag-hint {
  cursor: grabbing;
}
.dt-drop--before {
  border-top: 2px solid rgb(var(--v-theme-primary));
}
.dt-drop--after {
  border-bottom: 2px solid rgb(var(--v-theme-primary));
}
.dt-drop--into {
  outline: 2px dashed rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
}
.dt-tree-row--search-hit {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
}
</style>
