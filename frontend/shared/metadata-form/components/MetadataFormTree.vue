<script setup>
import { computed } from 'vue';
import MetadataFormTreeNode from './MetadataFormTreeNode.vue';
import { nodeKey } from '../utils/nodeIds';
import { nodeVisibleInTree } from '../utils/fieldFlags';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';
import { useMetadataFormUi } from '../composables/useMetadataFormUi';

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const labels = useMetadataFormLabels();
const ui = useMetadataFormUi();

const treeQuery = computed({
  get: () => ui?.treeQuery.value || '',
  set: (v) => {
    if (ui) ui.treeQuery.value = v ?? '';
  },
});
const fieldFilter = computed({
  get: () => ui?.fieldFilter.value || 'all',
  set: (v) => {
    if (ui) ui.fieldFilter.value = v || 'all';
  },
});
const showAllHelp = computed(() => !!ui?.showAllHelp.value);

function toggleHelp() {
  if (ui) ui.showAllHelp.value = !ui.showAllHelp.value;
}

const roots = computed(() => {
  const all = (props.items || []).filter((x) => x && typeof x === 'object');
  if (!ui) return all;
  const state = { mode: fieldFilter.value, query: treeQuery.value };
  if (state.mode === 'all' && !String(state.query || '').trim()) return all;
  return all.filter((node) => nodeVisibleInTree(node, state));
});
</script>

<template>
  <nav class="mf-tree" :aria-label="labels.treeNav">
    <div v-if="ui" class="mf-tree-tools">
      <v-text-field
        v-model="treeQuery"
        :placeholder="labels.searchFields"
        density="compact"
        variant="outlined"
        hide-details
        clearable
        prepend-inner-icon="mdi-magnify"
        class="mf-tree-search"
      />
      <div class="mf-tree-tools-row">
        <v-btn-toggle
          v-model="fieldFilter"
          density="compact"
          variant="outlined"
          divided
          mandatory
          class="mf-tree-filter"
        >
          <v-btn value="all" size="x-small" class="text-none px-2">{{ labels.filterAll }}</v-btn>
          <v-btn value="required" size="x-small" class="text-none px-2">{{ labels.filterRequired }}</v-btn>
          <v-btn value="recommended" size="x-small" class="text-none px-2">{{ labels.filterRecommended }}</v-btn>
        </v-btn-toggle>
        <v-btn
          icon
          size="x-small"
          variant="text"
          :color="showAllHelp ? 'primary' : undefined"
          :aria-label="showAllHelp ? labels.hideAllHelp : labels.showAllHelp"
          :title="showAllHelp ? labels.hideAllHelp : labels.showAllHelp"
          @click="toggleHelp"
        >
          <v-icon size="18">
            {{ showAllHelp ? 'mdi-help-circle' : 'mdi-help-circle-outline' }}
          </v-icon>
        </v-btn>
      </div>
    </div>
    <div class="mf-tree-body" role="tree">
      <MetadataFormTreeNode
        v-for="(item, idx) in roots"
        :key="nodeKey(item, `root-${idx}`)"
        :node="item"
        :depth="0"
      />
      <div v-if="!roots.length" class="text-caption text-medium-emphasis px-2 py-3">
        {{ labels.noMatchingFields }}
      </div>
    </div>
  </nav>
</template>

<style scoped>
.mf-tree {
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100%;
  background: transparent;
}
.mf-tree-tools {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 0 4px 10px;
}
.mf-tree-tools-row {
  display: flex;
  align-items: center;
  gap: 4px;
}
.mf-tree-filter {
  flex: 1 1 auto;
  min-width: 0;
}
.mf-tree-filter :deep(.v-btn) {
  min-width: 0;
  flex: 1 1 auto;
}
.mf-tree-body {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
  overscroll-behavior: contain;
  padding-bottom: 16px;
}
</style>
