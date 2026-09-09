<script setup>
/**
 * Section (or section_container) living in a nested_array props[] tree.
 * Child keys are relative to parentPath (the array row), not the section key.
 */
import { computed, ref } from 'vue';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';
import { useMetadataFormUi } from '../composables/useMetadataFormUi';
import {
  isBoundingBoxDisplay,
  resolveBoundingBoxSides,
  sectionChildDefs,
} from '../utils/enumOptions';
import MetadataBoundingBoxField from './MetadataBoundingBoxField.vue';
import MetadataPropNode from './MetadataPropNode.vue';

const props = defineProps({
  field: { type: Object, required: true },
  parentPath: { type: String, required: true },
});

const labels = useMetadataFormLabels();
const ui = useMetadataFormUi();
const open = ref(true);
const helpOpen = ref(false);
const isBbox = computed(() => isBoundingBoxDisplay(props.field));
const children = computed(() => {
  const all = sectionChildDefs(props.field);
  if (!isBbox.value) return all;
  const used = new Set(Object.values(resolveBoundingBoxSides(props.field)).map((s) => s.key));
  return all.filter((p) => p.key && !used.has(p.key));
});
const helpText = computed(() => props.field.help_text || props.field.help || '');
const helpShown = computed(() => !!(helpText.value && (ui?.showAllHelp?.value || helpOpen.value)));
const label = computed(() => props.field.title || props.field.key || '');

function toggleHelp() {
  if (ui?.showAllHelp?.value) return;
  helpOpen.value = !helpOpen.value;
}
</script>

<template>
  <div class="mf-nested-section">
    <div class="mf-nested-section-header">
      <button type="button" class="mf-nested-section-toggle" @click="open = !open">
        <v-icon size="small" class="me-1">{{ open ? 'mdi-chevron-down' : 'mdi-chevron-right' }}</v-icon>
        <span class="text-body-2 font-weight-semibold">{{ label }}</span>
      </button>
      <button
        v-if="helpText"
        type="button"
        class="mf-nested-section-help-btn"
        :class="{ 'mf-nested-section-help-btn--open': helpShown }"
        :aria-label="helpShown ? labels.hideHelp : labels.showHelp"
        @click="toggleHelp"
      >
        <v-icon size="16">{{ helpShown ? 'mdi-help-circle' : 'mdi-help-circle-outline' }}</v-icon>
      </button>
    </div>
    <div v-show="open" class="mf-nested-section-body">
      <p v-if="helpShown" class="mf-nested-section-help text-caption text-medium-emphasis" v-html="helpText" />
      <MetadataBoundingBoxField
        v-if="isBbox"
        :field="field"
        :parent-path="parentPath"
      />
      <MetadataPropNode
        v-for="child in children"
        :key="child.key"
        :prop="child"
        :parent-path="parentPath"
      />
    </div>
  </div>
</template>

<style scoped>
.mf-nested-section {
  margin-bottom: 12px;
}
.mf-nested-section-header {
  display: flex;
  align-items: center;
  width: 100%;
  background: rgba(var(--v-theme-on-surface), 0.04);
  padding: 8px 10px;
  border-radius: 8px;
}
.mf-nested-section-header:hover {
  background: rgba(var(--v-theme-on-surface), 0.07);
}
.mf-nested-section-toggle {
  display: flex;
  align-items: center;
  flex: 1 1 auto;
  min-width: 0;
  text-align: left;
  border: 0;
  background: transparent;
  padding: 0;
  cursor: pointer;
  color: inherit;
}
.mf-nested-section-help-btn {
  display: inline-flex;
  flex-shrink: 0;
  margin-left: 6px;
  border: 0;
  background: transparent;
  padding: 2px;
  cursor: pointer;
  color: rgba(var(--v-theme-on-surface), 0.45);
  line-height: 1;
  border-radius: 50%;
}
.mf-nested-section-help-btn:hover,
.mf-nested-section-help-btn--open {
  color: rgb(var(--v-theme-primary));
}
.mf-nested-section-help {
  white-space: pre-wrap;
  margin: 0 0 10px;
  line-height: 1.4;
}
.mf-nested-section-body {
  padding: 10px 4px 4px 10px;
  border-left: 2px solid rgba(var(--v-theme-on-surface), 0.08);
  margin-left: 8px;
}
</style>
