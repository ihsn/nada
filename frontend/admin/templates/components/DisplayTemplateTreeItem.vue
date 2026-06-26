<template>
  <div class="dt-tree-item">
    <div
      class="dt-tree-row d-flex align-center rounded"
      :class="rowClass"
      :style="{ paddingLeft: `${6 + depth * 14}px` }"
      role="treeitem"
      draggable="true"
      @dragstart="onDragStart"
      @dragend="onDragEnd"
      @dragover.prevent="onDragOver"
      @dragleave="onDragLeave"
      @drop.prevent="onDrop"
      @click="$emit('select', node)"
    >
      <v-icon class="dt-drag-hint mr-1 text-medium-emphasis" size="18" @click.stop>mdi-drag</v-icon>
      <v-btn
        v-if="isContainer"
        icon
        variant="text"
        size="x-small"
        class="mr-0"
        :aria-expanded="expanded[node._tid] ? 'true' : 'false'"
        @click.stop="$emit('toggle', node._tid)"
      >
        <v-icon size="20">{{ expanded[node._tid] ? 'mdi-chevron-down' : 'mdi-chevron-right' }}</v-icon>
      </v-btn>
      <span v-else class="dt-tree-spacer" />
      <v-icon size="small" class="mr-2 text-medium-emphasis">{{ icon }}</v-icon>
      <span class="text-body-2 text-truncate flex-grow-1" :class="{ 'text-medium-emphasis': node.is_prop }">{{ label }}</span>
    </div>
    <div v-if="isContainer && expanded[node._tid]">
      <DisplayTemplateTreeItem
        v-for="c in kids"
        :key="c._tid"
        :node="c"
        :depth="depth + 1"
        :expanded="expanded"
        :selected-tid="selectedTid"
        :drag-active="dragActive"
        :drop-target-tid="dropTargetTid"
        :drop-zone="dropZone"
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
  nodeChildrenArray,
  nodeIcon,
  nodeLabel,
} from '../utils/displayTemplateTree';

defineOptions({ name: 'DisplayTemplateTreeItem' });

const props = defineProps({
  node: { type: Object, required: true },
  depth: { type: Number, default: 0 },
  expanded: { type: Object, required: true },
  selectedTid: { type: String, default: '' },
  dragActive: { type: Boolean, default: false },
  dropTargetTid: { type: String, default: '' },
  dropZone: { type: String, default: '' },
});

const emit = defineEmits(['toggle', 'select', 'drag-start', 'drag-end', 'drag-hover', 'node-drop']);

const kids = computed(() => nodeChildrenArray(props.node) || []);
const isContainer = computed(() => isContainerNode(props.node));
const icon = computed(() => nodeIcon(props.node));
const label = computed(() => nodeLabel(props.node));

const rowClass = computed(() => {
  const cls = [];
  if (props.selectedTid === props.node._tid) cls.push('dt-tree-row--active');
  if (props.dragActive && props.dropTargetTid === props.node._tid && props.dropZone) {
    cls.push(`dt-drop--${props.dropZone}`);
  }
  return cls;
});

function onDragStart(e) {
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
  if (!props.dragActive) return;
  try {
    e.dataTransfer.dropEffect = 'move';
  } catch {
    /* ignore */
  }
  const zone = dropZoneFromPointer(e, props.node);
  emit('drag-hover', { targetTid: props.node._tid, zone });
}

function onDragLeave() {}

function onDrop(e) {
  const from = e.dataTransfer?.getData('text/plain');
  if (!from) return;
  const zone = dropZoneFromPointer(e, props.node);
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
.dt-tree-row--active {
  background: rgba(var(--v-theme-primary), 0.12);
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
</style>
