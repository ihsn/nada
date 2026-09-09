<script setup>
/**
 * Full-width stacked sections (no tree). Used by deposit; catalog keeps the tree.
 */
import { computed } from 'vue';
import { isSectionType } from '../utils/enumOptions';
import { nodeKey } from '../utils/nodeIds';
import MetadataFormNode from './MetadataFormNode.vue';
import MetadataFieldInput from './MetadataFieldInput.vue';
import MetadataArrayField from './MetadataArrayField.vue';
import MetadataSimpleArrayField from './MetadataSimpleArrayField.vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const panels = computed(() => collectPanels(props.items || []));

function collectPanels(items, groupTitle = '') {
  const out = [];
  (items || []).forEach((item) => {
    if (!item || typeof item !== 'object') return;
    if (item.type === 'section_container') {
      const title = item.title || groupTitle;
      out.push(...collectPanels(item.items || [], title));
      return;
    }
    if (isSectionType(item.type)) {
      out.push({
        key: nodeKey(item, 'panel'),
        title: item.title || item.key || 'Section',
        group: groupTitle,
        node: item,
        children: Array.isArray(item.items) ? item.items.filter((x) => x && typeof x === 'object') : [],
      });
      return;
    }
    out.push({
      key: nodeKey(item, 'field'),
      title: item.title || item.key || 'Field',
      group: groupTitle,
      node: item,
      children: [],
      leaf: true,
    });
  });
  return out;
}

function fieldPath(field) {
  return field.key || '';
}

function isScalar(field) {
  const t = field.type;
  return !isSectionType(t) && t !== 'array' && t !== 'nested_array' && t !== 'simple_array';
}
</script>

<template>
  <div class="mf-stacked">
    <section v-for="(panel, idx) in panels" :key="panel.key" class="mf-stacked-panel">
      <h3 class="text-subtitle-1 font-weight-medium mb-3">{{ panel.title }}</h3>
      <template v-if="panel.leaf">
        <MetadataArrayField
          v-if="panel.node.type === 'array' || panel.node.type === 'nested_array'"
          :field="panel.node"
          :path="fieldPath(panel.node)"
        />
        <MetadataSimpleArrayField
          v-else-if="panel.node.type === 'simple_array'"
          :field="panel.node"
          :path="fieldPath(panel.node)"
        />
        <MetadataFieldInput
          v-else-if="isScalar(panel.node) && panel.node.key"
          :field="panel.node"
          :path="fieldPath(panel.node)"
        />
      </template>
      <MetadataFormNode
        v-for="(child, cidx) in panel.children"
        v-else
        :key="nodeKey(child, `s-${idx}-${cidx}`)"
        :field="child"
        :depth="0"
        :embedded="true"
      />
    </section>
  </div>
</template>

<style scoped>
.mf-stacked {
  width: 100%;
}
.mf-stacked-panel + .mf-stacked-panel {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}
</style>
