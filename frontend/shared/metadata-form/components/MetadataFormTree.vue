<script setup>
import { computed } from 'vue';
import MetadataFormTreeNode from './MetadataFormTreeNode.vue';
import { nodeKey } from '../utils/nodeIds';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const labels = useMetadataFormLabels();
const roots = computed(() => (props.items || []).filter((x) => x && typeof x === 'object'));
</script>

<template>
  <nav class="mf-tree" :aria-label="labels.treeNav">
    <div class="mf-tree-body" role="tree">
      <MetadataFormTreeNode
        v-for="(item, idx) in roots"
        :key="nodeKey(item, `root-${idx}`)"
        :node="item"
        :depth="0"
      />
    </div>
  </nav>
</template>

<style scoped>
.mf-tree {
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100%;
}
.mf-tree-body {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
  overscroll-behavior: contain;
  padding-bottom: 16px;
}
</style>
