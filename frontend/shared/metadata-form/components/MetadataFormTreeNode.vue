<script setup>
import { computed, ref, watch } from 'vue';
import { normalizeNodeId, nodeKey } from '../utils/nodeIds';
import { isRequiredField, nodeVisibleInTree } from '../utils/fieldFlags';
import { useMetadataNav } from '../composables/useMetadataNav';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';
import { useMetadataFormUi } from '../composables/useMetadataFormUi';

const props = defineProps({
  node: { type: Object, required: true },
  depth: { type: Number, default: 0 },
});

const nav = useMetadataNav();
const labels = useMetadataFormLabels();
const ui = useMetadataFormUi();
const key = computed(() => nodeKey(props.node, `d${props.depth}`));
const nodeId = computed(() => normalizeNodeId(key.value));
const filterState = computed(() => ({
  mode: ui?.fieldFilter?.value || 'all',
  query: ui?.treeQuery?.value || '',
}));
const filtering = computed(
  () => filterState.value.mode !== 'all' || String(filterState.value.query).trim() !== ''
);
const children = computed(() => {
  const all = (props.node.items || []).filter((x) => x && typeof x === 'object');
  if (!filtering.value) return all;
  return all.filter((child) => nodeVisibleInTree(child, filterState.value));
});
const hasChildren = computed(() => children.value.length > 0);
const required = computed(() => isRequiredField(props.node));
const title = computed(
  () => props.node.title || props.node.key || props.node.id || labels.value.item
);

/** Collapsed by default; ancestors of the active node auto-expand via containsActive. */
const showChildren = ref(false);

const branchIcon = computed(() => {
  if (props.node.type === 'section_container') return 'mdi-dresser';
  return showChildren.value ? 'mdi-folder-open' : 'mdi-folder';
});

const leafIcon = computed(() => {
  const t = props.node.type;
  const dt = props.node.display_type;
  if (t === 'nested_array') return 'mdi-file-tree';
  if (t === 'array') return 'mdi-table';
  if (t === 'simple_array') return 'mdi-table-column';
  if (dt === 'dropdown' || dt === 'dropdown-custom') return 'mdi-file-document';
  if (dt === 'date' || t === 'date') return 'mdi-book-clock-outline';
  return 'mdi-file-document-outline';
});

const isActive = computed(() => {
  if (!nav?.activeNodeId?.value || !nodeId.value) return false;
  return nav.activeNodeId.value === nodeId.value;
});

const containsActive = computed(() => {
  if (!nav?.activeNodeId?.value || !hasChildren.value) return false;
  return nodeContainsId(props.node, nav.activeNodeId.value);
});

watch(
  [containsActive, filtering],
  ([active, filtered]) => {
    if (active || filtered) showChildren.value = true;
  },
  { immediate: true }
);

function nodeContainsId(node, targetNormId) {
  const k = nodeKey(node, '');
  if (k && normalizeNodeId(k) === targetNormId) return true;
  const items = node.items;
  if (!Array.isArray(items)) return false;
  for (let i = 0; i < items.length; i++) {
    if (items[i] && nodeContainsId(items[i], targetNormId)) return true;
  }
  return false;
}

function onToggle(e) {
  if (!hasChildren.value) return;
  e.stopPropagation();
  showChildren.value = !showChildren.value;
}

function onClick(e) {
  e.stopPropagation();
  nav?.setActiveNodeFromObject?.(props.node);
}
</script>

<template>
  <div class="mf-tree-node" :class="[`mf-tree-lvl-${depth}`, { 'mf-tree-node--active': isActive }]">
    <div
      class="mf-tree-row"
      :class="{
        'mf-tree-row--active': isActive,
        'mf-tree-row--branch': hasChildren,
        'mf-tree-row--contains-active': containsActive && !isActive,
      }"
      role="treeitem"
      :aria-expanded="hasChildren ? showChildren : undefined"
      @click="onClick"
    >
      <button
        v-if="hasChildren"
        type="button"
        class="mf-tree-toggle"
        tabindex="-1"
        @click="onToggle"
      >
        <v-icon size="16">{{ branchIcon }}</v-icon>
      </button>
      <v-icon v-else size="16" class="mf-tree-leaf-icon">{{ leafIcon }}</v-icon>
      <span class="mf-tree-label" :class="{ 'mf-tree-label--required': required }">{{ title }}</span>
    </div>

    <div v-if="hasChildren && showChildren" class="mf-tree-children" role="group">
      <MetadataFormTreeNode
        v-for="(child, idx) in children"
        :key="nodeKey(child, `c-${depth}-${idx}`)"
        :node="child"
        :depth="depth + 1"
      />
    </div>
  </div>
</template>

<style scoped>
.mf-tree-row {
  display: flex;
  align-items: flex-start;
  gap: 4px;
  padding: 5px 8px;
  border-radius: 6px;
  cursor: pointer;
  color: rgba(var(--v-theme-on-surface), 0.75);
  font-size: 0.8125rem;
  line-height: 1.35;
}
.mf-tree-row:hover {
  background: rgba(var(--v-theme-on-surface), 0.06);
}
.mf-tree-row--active {
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.1);
  font-weight: 600;
}
.mf-tree-row--contains-active {
  color: rgba(var(--v-theme-on-surface), 0.9);
}
.mf-tree-toggle {
  border: 0;
  background: transparent;
  padding: 0;
  margin: 0;
  cursor: pointer;
  color: inherit;
  line-height: 1;
  flex-shrink: 0;
}
.mf-tree-leaf-icon {
  flex-shrink: 0;
  opacity: 0.65;
  margin-top: 1px;
}
.mf-tree-label {
  word-break: break-word;
}
.mf-tree-label--required {
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.92);
}
.mf-tree-children {
  margin-left: 14px;
  border-left: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  padding-left: 2px;
}
</style>
