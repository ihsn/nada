<template>
  <div class="dt-field-json-tab">
    <template v-if="selectionKind === 'node' && isFieldSelection && selectedNode">
      <div class="d-flex align-center flex-wrap ga-2 mb-3">
        <div class="text-subtitle-1 font-weight-semibold">Field JSON</div>
        <v-spacer />
        <v-btn
          size="small"
          variant="text"
          prepend-icon="mdi-content-copy"
          :disabled="!jsonPreview"
          @click="copyJson"
        >
          Copy
        </v-btn>
      </div>
      <p class="text-body-2 text-medium-emphasis mb-4">
        Snapshot of the selected field as stored in <code class="text-body-2">template_json</code> (tree
        <code class="text-body-2">_tid</code> and metadata-editor-only keys omitted).
      </p>
      <v-textarea
        :model-value="jsonPreview"
        variant="outlined"
        rows="22"
        hide-details
        readonly
        auto-grow
        class="font-monospace text-body-2 dt-field-json-preview"
      />
    </template>
    <template v-else>
      <div class="text-body-2 text-medium-emphasis pa-2">
        Select a <strong>field</strong> or <strong>array column</strong> in the layout tree to view its JSON.
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { cloneJson, stripTreeIds } from '../utils/displayTemplateTree';
import { sanitizeDisplayLayoutFieldNode } from '../utils/displayFieldOptions';

defineOptions({ name: 'DisplayTemplateFieldJsonTab' });

const props = defineProps({
  selectionKind: { type: String, default: '' },
  selectedNode: { type: Object, default: null },
});

const isFieldSelection = computed(() => {
  const n = props.selectedNode;
  if (!n || props.selectionKind !== 'node') return false;
  const t = n.type;
  if (t === 'section' || t === 'section_container') return false;
  return true;
});

const jsonPreview = computed(() => {
  const n = props.selectedNode;
  if (!isFieldSelection.value || !n) return '';
  try {
    return JSON.stringify(sanitizeDisplayLayoutFieldNode(stripTreeIds(cloneJson(n))), null, 2);
  } catch {
    return '';
  }
});

async function copyJson() {
  const text = jsonPreview.value;
  if (!text) return;
  try {
    await navigator.clipboard.writeText(text);
  } catch {
    /* ignore */
  }
}
</script>

<style scoped>
.font-monospace :deep(textarea) {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
  font-size: 0.8rem;
  line-height: 1.4;
}
.dt-field-json-preview :deep(textarea) {
  opacity: 1;
}
</style>
