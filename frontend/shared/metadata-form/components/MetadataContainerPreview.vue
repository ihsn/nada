<script setup>
/**
 * ME-style read-only preview for a section_container (and nested sections).
 * Only shows fields/sections that already have values.
 */
import { computed } from 'vue';
import { get as lodashGet } from 'lodash';
import { useMetadataFormStore } from '../composables/useMetadataFormStore';
import { useMetadataNav } from '../composables/useMetadataNav';
import { isSectionType } from '../utils/enumOptions';
import { nodeKey } from '../utils/nodeIds';
import {
  formatPreviewArrayRows,
  formatPreviewScalar,
  hasDisplayableContent,
  isFieldEmpty,
} from '../utils/previewContent';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';

const props = defineProps({
  items: { type: Array, default: () => [] },
  depth: { type: Number, default: 0 },
});

const store = useMetadataFormStore();
const nav = useMetadataNav();
const labels = useMetadataFormLabels();
const formData = computed(() => store.state.data);

const visibleItems = computed(() => {
  return (props.items || []).filter((item) => {
    if (!item || typeof item !== 'object') return false;
    if (isSectionType(item.type)) {
      return hasDisplayableContent(item.items || [], formData.value);
    }
    if (!item.key) return false;
    return !isFieldEmpty(lodashGet(formData.value, item.key));
  });
});

function fieldValue(item) {
  return lodashGet(formData.value, item.key);
}

function previewScalar(value) {
  return formatPreviewScalar(value, labels.value);
}

function previewArrayRows(value, itemProps) {
  return formatPreviewArrayRows(value, itemProps, labels.value);
}

function openNode(item) {
  nav?.setActiveNodeFromObject?.(item);
}

function isScalarType(type) {
  return ['text', 'string', 'textarea', 'dropdown', 'integer', 'number', 'boolean'].includes(type);
}
</script>

<template>
  <div class="mf-preview" :class="`mf-preview-depth-${depth}`">
    <div v-if="!visibleItems.length" class="text-body-2 text-medium-emphasis py-4">
      {{ labels.noPreview }}
    </div>

    <template v-for="(item, idx) in visibleItems" :key="nodeKey(item, `pv-${depth}-${idx}`)">
      <!-- Nested container -->
      <div v-if="item.type === 'section_container'" class="mf-preview-container mb-4">
        <div class="mf-preview-container-title">{{ item.title }}</div>
        <MetadataContainerPreview :items="item.items || []" :depth="depth + 1" />
      </div>

      <!-- Nested section: titled card + link to edit -->
      <div v-else-if="item.type === 'section'" class="mf-preview-section mb-4">
        <div class="d-flex align-center justify-space-between mb-2 flex-wrap ga-2">
          <h3 class="text-subtitle-1 font-weight-semibold mb-0">{{ item.title }}</h3>
          <v-btn size="small" variant="tonal" class="text-none" @click="openNode(item)">
            {{ labels.editSection }}
          </v-btn>
        </div>
        <MetadataContainerPreview :items="item.items || []" :depth="depth + 1" />
      </div>

      <!-- Scalar -->
      <div
        v-else-if="isScalarType(item.type) || (!item.type && item.key)"
        class="mf-preview-field mb-3"
      >
        <div class="text-caption text-medium-emphasis mb-1">{{ item.title || item.key }}</div>
        <div class="mf-preview-value text-body-2">{{ previewScalar(fieldValue(item)) }}</div>
      </div>

      <!-- Arrays -->
      <div v-else-if="item.type === 'array' || item.type === 'nested_array'" class="mf-preview-field mb-3">
        <div class="text-caption text-medium-emphasis mb-1">{{ item.title || item.key }}</div>
        <ul class="mf-preview-list">
          <li v-for="(row, rIdx) in previewArrayRows(fieldValue(item), item.props)" :key="rIdx">
            {{ row.text }}
          </li>
        </ul>
      </div>

      <div v-else-if="item.type === 'simple_array'" class="mf-preview-field mb-3">
        <div class="text-caption text-medium-emphasis mb-1">{{ item.title || item.key }}</div>
        <ul class="mf-preview-list">
          <li v-for="(val, rIdx) in fieldValue(item) || []" :key="rIdx">
            {{ previewScalar(val) }}
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<style scoped>
.mf-preview-container-title {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 8px;
}
.mf-preview-section {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 8px;
  padding: 12px 14px;
  background: rgba(var(--v-theme-on-surface), 0.02);
}
.mf-preview-value {
  white-space: pre-wrap;
  word-break: break-word;
}
.mf-preview-list {
  margin: 0;
  padding-left: 1.1rem;
}
.mf-preview-list li {
  margin-bottom: 4px;
}
</style>
