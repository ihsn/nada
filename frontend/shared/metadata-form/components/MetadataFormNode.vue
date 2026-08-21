<script setup>
import { computed, ref } from 'vue';
import { isSectionType } from '../utils/enumOptions';
import { fieldDomId, nodeKey } from '../utils/nodeIds';
import MetadataFieldInput from './MetadataFieldInput.vue';
import MetadataArrayField from './MetadataArrayField.vue';
import MetadataSimpleArrayField from './MetadataSimpleArrayField.vue';

const props = defineProps({
  field: { type: Object, required: true },
  depth: { type: Number, default: 0 },
  /** When true, nested sections stay expanded by default (active pane content). */
  embedded: { type: Boolean, default: false },
});

const open = ref(props.embedded || props.field.expanded !== false);

const isSection = computed(() => isSectionType(props.field.type));
const children = computed(() =>
  Array.isArray(props.field.items) ? props.field.items.filter((x) => x && typeof x === 'object') : []
);

const anchorKey = computed(() => nodeKey(props.field, ''));
const domId = computed(() => fieldDomId(anchorKey.value));

function fieldPath(field) {
  return field.key || '';
}

function isScalar(field) {
  const t = field.type;
  return !isSectionType(t) && t !== 'array' && t !== 'nested_array' && t !== 'simple_array';
}
</script>

<template>
  <div
    v-if="isSection"
    :id="domId || undefined"
    class="mf-section"
    :class="`mf-depth-${depth}`"
  >
    <button type="button" class="mf-section-header" @click="open = !open">
      <v-icon size="small" class="me-1">{{ open ? 'mdi-chevron-down' : 'mdi-chevron-right' }}</v-icon>
      <span class="text-subtitle-2 font-weight-semibold">{{ field.title || field.key }}</span>
    </button>
    <div v-show="open" class="mf-section-body">
      <MetadataFormNode
        v-for="(child, idx) in children"
        :key="nodeKey(child, `c-${depth}-${idx}`)"
        :field="child"
        :depth="depth + 1"
        :embedded="embedded"
      />
    </div>
  </div>

  <div
    v-else-if="field.type === 'array' || field.type === 'nested_array'"
    :id="domId || undefined"
    class="mf-anchor"
  >
    <MetadataArrayField :field="field" :path="fieldPath(field)" />
  </div>
  <div
    v-else-if="field.type === 'simple_array'"
    :id="domId || undefined"
    class="mf-anchor"
  >
    <MetadataSimpleArrayField :field="field" :path="fieldPath(field)" />
  </div>
  <div
    v-else-if="isScalar(field) && field.key"
    :id="domId || undefined"
    class="mf-anchor"
  >
    <MetadataFieldInput :field="field" :path="fieldPath(field)" />
  </div>
</template>

<style scoped>
.mf-section {
  margin-bottom: 12px;
}
.mf-section-header {
  display: flex;
  align-items: center;
  width: 100%;
  text-align: left;
  border: 0;
  background: rgba(var(--v-theme-on-surface), 0.04);
  padding: 10px 12px;
  border-radius: 8px;
  cursor: pointer;
}
.mf-section-header:hover {
  background: rgba(var(--v-theme-on-surface), 0.07);
}
.mf-section-body {
  padding: 12px 4px 4px 12px;
  border-left: 2px solid rgba(var(--v-theme-on-surface), 0.08);
  margin-left: 8px;
}
.mf-depth-0 > .mf-section-header {
  background: rgba(var(--v-theme-primary), 0.06);
}
.mf-anchor {
  scroll-margin-top: 16px;
}
</style>
