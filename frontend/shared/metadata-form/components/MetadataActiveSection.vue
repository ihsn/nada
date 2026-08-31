<script setup>
/**
 * Main pane for the active tree node (ME form-main behavior):
 * - section_container → read-only preview of filled content
 * - section → editable children
 * - field → single editable control
 */
import { computed, ref } from 'vue';
import { isEditableSection, isSectionContainer, isSectionType } from '../utils/enumOptions';
import { nodeVisibleInForm } from '../utils/fieldFlags';
import { fieldDomId, nodeKey } from '../utils/nodeIds';
import { useMetadataNav } from '../composables/useMetadataNav';
import { hasDisplayableContent } from '../utils/previewContent';
import { useMetadataFormStore } from '../composables/useMetadataFormStore';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';
import { useMetadataFormUi } from '../composables/useMetadataFormUi';
import MetadataFieldInput from './MetadataFieldInput.vue';
import MetadataArrayField from './MetadataArrayField.vue';
import MetadataSimpleArrayField from './MetadataSimpleArrayField.vue';
import MetadataFormNode from './MetadataFormNode.vue';
import MetadataContainerPreview from './MetadataContainerPreview.vue';

const props = defineProps({
  node: { type: Object, required: true },
});

const nav = useMetadataNav();
const store = useMetadataFormStore();
const labels = useMetadataFormLabels();
const ui = useMetadataFormUi();

const title = computed(() => props.node.title || props.node.key || '');
const helpText = computed(() => props.node.help_text || props.node.help || '');
const helpOpen = ref(false);
const helpShown = computed(() => !!(helpText.value && (ui?.showAllHelp?.value || helpOpen.value)));
const isContainer = computed(() => isSectionContainer(props.node.type));
const isSection = computed(() => isEditableSection(props.node.type));
const filterState = computed(() => ({
  mode: ui?.fieldFilter?.value || 'all',
  query: ui?.treeQuery?.value || '',
}));
const children = computed(() => {
  const all = Array.isArray(props.node.items)
    ? props.node.items.filter((x) => x && typeof x === 'object')
    : [];
  const state = filterState.value;
  if (state.mode === 'all' && !String(state.query || '').trim()) return all;
  return all.filter((child) => nodeVisibleInForm(child, state));
});
const childSections = computed(() => children.value.filter((c) => isSectionType(c.type)));
const domId = computed(() => fieldDomId(nodeKey(props.node, '')));
const hasPreviewContent = computed(() =>
  hasDisplayableContent(props.node.items || [], store.state.data)
);

function fieldPath(field) {
  return field.key || '';
}

function isScalar(field) {
  const t = field.type;
  return !isSectionType(t) && t !== 'array' && t !== 'nested_array' && t !== 'simple_array';
}

function openChild(child) {
  nav?.setActiveNodeFromObject?.(child);
}
</script>

<template>
  <div :id="domId || undefined" class="mf-active-section">
    <div class="mf-active-header mb-4">
      <div class="d-flex align-center ga-2 mb-1">
        <v-icon size="22" color="primary">
          {{ isContainer ? 'mdi-dresser' : isSection ? 'mdi-folder-open' : 'mdi-file-document-outline' }}
        </v-icon>
        <h2 class="text-h6 font-weight-semibold mb-0">{{ title }}</h2>
        <button
          v-if="helpText"
          type="button"
          class="mf-active-help-btn"
          :class="{ 'mf-active-help-btn--open': helpShown }"
          :aria-label="helpShown ? labels.hideHelp : labels.showHelp"
          @click="helpOpen = !helpOpen"
        >
          <v-icon size="18">{{ helpShown ? 'mdi-help-circle' : 'mdi-help-circle-outline' }}</v-icon>
        </button>
      </div>
      <div
        v-if="helpShown"
        class="text-body-2 text-medium-emphasis mb-0 mf-active-help-text"
        v-html="helpText"
      />
      <p v-if="isContainer" class="text-caption text-medium-emphasis mt-1 mb-0">
        {{ labels.containerOverview }}
      </p>
    </div>

    <!-- section_container: ME-style preview + quick links to child sections -->
    <template v-if="isContainer">
      <div v-if="childSections.length" class="mf-child-section-links mb-4">
        <div class="text-caption text-medium-emphasis mb-2">{{ labels.sectionsInGroup }}</div>
        <div class="d-flex flex-wrap ga-2">
          <v-btn
            v-for="(child, idx) in childSections"
            :key="nodeKey(child, `link-${idx}`)"
            size="small"
            variant="tonal"
            class="text-none"
            prepend-icon="mdi-folder-outline"
            @click="openChild(child)"
          >
            {{ child.title || child.key }}
          </v-btn>
        </div>
      </div>

      <v-alert
        v-if="!hasPreviewContent"
        type="info"
        variant="tonal"
        density="compact"
        class="mb-3"
      >
        {{ labels.nothingEntered }}
      </v-alert>

      <MetadataContainerPreview :items="node.items || []" :depth="0" />
    </template>

    <!-- section: editable form -->
    <template v-else-if="isSection">
      <div v-if="!children.length" class="text-caption text-medium-emphasis">
        {{ labels.noFields }}
      </div>
      <MetadataFormNode
        v-for="(child, idx) in children"
        :key="nodeKey(child, `active-${idx}`)"
        :field="child"
        :depth="0"
        :embedded="true"
      />
    </template>

    <!-- Leaf field -->
    <template v-else>
      <MetadataArrayField
        v-if="node.type === 'array' || node.type === 'nested_array'"
        :field="node"
        :path="fieldPath(node)"
      />
      <MetadataSimpleArrayField
        v-else-if="node.type === 'simple_array'"
        :field="node"
        :path="fieldPath(node)"
      />
      <MetadataFieldInput
        v-else-if="isScalar(node) && node.key"
        :field="node"
        :path="fieldPath(node)"
      />
    </template>
  </div>
</template>

<style scoped>
.mf-active-section {
  width: 100%;
  max-width: none;
}
.mf-active-header {
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}
.mf-active-help-btn {
  border: 0;
  background: transparent;
  padding: 2px;
  cursor: pointer;
  color: rgba(var(--v-theme-on-surface), 0.45);
  border-radius: 50%;
  line-height: 1;
}
.mf-active-help-btn:hover,
.mf-active-help-btn--open {
  color: rgb(var(--v-theme-primary));
}
.mf-active-help-text :deep(p) {
  margin: 0 0 0.5em;
}
.mf-active-help-text :deep(p:last-child) {
  margin-bottom: 0;
}
.mf-active-help-text :deep(ul),
.mf-active-help-text :deep(ol) {
  margin: 0.25em 0 0.5em;
  padding-left: 1.25em;
}
.mf-child-section-links {
  padding: 10px 12px;
  border-radius: 8px;
  background: rgba(var(--v-theme-primary), 0.04);
}
</style>
